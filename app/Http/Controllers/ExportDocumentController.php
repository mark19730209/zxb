<?php

// app/Http/Controllers/ExportDocumentController.php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\FinancialTracker;
use App\Models\Importer;
use App\Models\Item;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportDocumentController extends Controller
{
    /**
     * 1. 展现工作台：一次性组装并输出五个维度的全套单据数据（适配 Category/Item 分割完全体版）
     */
    public function show(int $id)
    {
        // 🎯 核心高能升级：引入三层穿透级联关联，将款式和其所属的海关法定大类一并打包载入
        $contract = Contract::with([
            'contractItems.item.category',
            'financialTracker',
        ])->findOrFail($id);

        // 格式化处理明细中的要素模板与已填写的数据，方便前端循环读取
        $contract->contractItems->each(function ($ci) {
            // 🎯 核心修正：要素模板（elements_template）现在必须穿透从具体的大类（category）中获取
            if ($ci->item && $ci->item->category) {
                $template = $ci->item->category->elements_template;
                // 健壮性防呆：确保已经是 PHP 数组格式，方便前端渲染标签
                $ci->item->elements_template = is_string($template) ? json_decode($template, true) : $template;
            } else {
                if ($ci->item) {
                    $ci->item->elements_template = [];
                }
            }
            // 将数据库中存储的 "A|B|C|" 字符串还原为数组，方便前端双向绑定
            $ci->element_values_array = $ci->declared_elements ? explode('|', rtrim($ci->declared_elements, '|')) : [];
        });

        // 动态构建五个维度的数据（Invoice/Packing List/报关/要素）
        $invoice = [
            'invoice_no' => 'INV-'.$contract->contract_no,
            'date' => $contract->contract_date,
            'currency' => $contract->currency,
            'incoterms' => $contract->incoterms,
            'items' => $contract->contractItems->map(fn ($ci) => [
                'name' => $ci->item->name_en ?? 'Unknown Item',
                'qty' => $ci->quantity,
                'price' => $ci->unit_price,
                'total' => $ci->total_amount,
            ]),
            'grand_total' => $contract->contractItems->sum('total_amount'),
        ];

        $packingList = [
            'packing_no' => 'PL-'.$contract->contract_no,
            'total_packages' => $contract->contractItems->sum('packages'),
            'total_net_weight' => $contract->contractItems->sum('net_weight'),
            'total_gross_weight' => $contract->contractItems->sum('gross_weight'),
            'total_volume' => $contract->contractItems->sum('volume'),
            'items' => $contract->contractItems->map(fn ($ci) => [
                'name' => $ci->item->name_en ?? 'Unknown Item',
                'packages' => $ci->packages,
                'package_type' => $ci->package_type,
                'nw' => $ci->net_weight,
                'gw' => $ci->gross_weight,
            ]),
        ];

        // 🎯 核心修正：报关单草单中的 H.S. 编码与单位，必须跨表从其母体 category 中点出
        $customsDeclaration = [
            'items' => $contract->contractItems->map(fn ($ci) => [
                'hs_code' => $ci->item->category->hs_code ?? '',
                'name_cn' => $ci->item->name_cn ?? '',
                'qty' => $ci->quantity,
                'unit' => $ci->unit ?: ($ci->item->category->unit ?? 'PCS'), // 优先使用合同明细中固化的单位快照
                'amount_usd' => $ci->total_amount,
            ]),
        ];

        // 🎯 核心修正：规范申报要素串预览中的 H.S. 编码，同样平滑穿透获取
        $declarationElements = [
            'items' => $contract->contractItems->map(fn ($ci) => [
                'hs_code' => $ci->item->category->hs_code ?? '',
                'name_cn' => $ci->item->name_cn ?? '',
                'declared_elements' => $ci->declared_elements ?? '未填写申报要素',
            ]),
        ];

        // 库中可选商品数据集调取（保持您优雅的分割穿透逻辑）
        $availableItems = Item::with('category')->where('is_actived', true)->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'category_id' => $item->category_id, // 🎯 核心补充：穿透分类ID用于前端过滤
                'sku' => $item->sku,
                'name_cn' => $item->name_cn,
                'name_en' => $item->name_en,
                'purchase_price' => $item->purchase_price,
                'image_path' => $item->image_path,
                // 🎯 核心分割穿透：从所属的 category 表中点出海关法理数据
                'hs_code' => $item->category->hs_code ?? '',
                'unit' => $item->category->unit ?? 'PCS',
                'elements_template' => $item->category->elements_template ?? [],
            ];
        });

        return Inertia::render('Documents/Show', [
            'contract' => $contract,
            'invoice' => $invoice,
            'packingList' => $packingList,
            'customsDeclaration' => $customsDeclaration,
            'declarationElements' => $declarationElements,
            'availableItems' => $availableItems,
            'availableCategories' => Category::select('id', 'hs_code', 'category_name')->get(),
            'availableSuppliers' => Supplier::select('id', 'company_name')->get(),
        ]);
    }

    /**
     * 2. 核心补齐：在工作台直接覆盖更新全套物流包装与动态海关要素串
     * 🎯 完全体更新配平引擎：完美接管 Category/Item 物理解耦后的数据对流与退税重算
     */
    public function update(Request $request, int $id)
    {
        $contract = Contract::findOrFail($id);

        $validated = $request->validate([
            // 1. 合同主表的四个高频变更商务要素
            'contract_no' => 'required|string',
            'contract_date' => 'required|date',
            'currency' => 'required|string|max:3',
            'incoterms' => 'required|string|max:10',
            'payment_terms' => 'required|string',
            // 🎯 核心补齐：三大涉外物流核心参数硬约束
            'transport_mode' => 'required|string|max:10',
            'port_of_loading' => 'required|string|max:50',
            'port_of_destination' => 'required|string|max:100', // 目的港设为强必填

            // 2. 货物明细大包深度校验
            'items' => 'nullable|array',
            'items.*.id' => 'nullable',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.supplier_id' => 'required|exists:suppliers,id',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.unit_price' => 'required|numeric|min:0',

            // 核心实际下单含税采购单价约束
            'items.*.purchase_price_snapshot' => 'required|numeric|min:0',

            'items.*.packages' => 'required|integer|min:0',
            'items.*.package_type' => 'required|string',
            'items.*.net_weight' => 'required|numeric|min:0',
            'items.*.gross_weight' => 'required|numeric|min:0',
            'items.*.volume' => 'required|numeric|min:0',
            'items.*.element_values' => 'required|array',
        ]);

        DB::transaction(function () use ($contract, $validated) {
            // 📝 步骤一：一键覆盖更新合同主表本身的属性
            $contract->update([
                'contract_no' => $validated['contract_no'],
                'contract_date' => $validated['contract_date'],
                'currency' => $validated['currency'],
                'incoterms' => $validated['incoterms'],
                'payment_terms' => $validated['payment_terms'],
                // 🎯 核心补齐：三大涉外物流核心参数硬约束
                'transport_mode' => $validated['transport_mode'],
                'port_of_loading' => $validated['port_of_loading'],
                'port_of_destination' => $validated['port_of_destination'], // 目的港设为强必填

            ]);

            // 🗑️ 步骤二：物理安全拦截——如果操作员在前端点击移除了某一行大货，后端同步从 MySQL 切断
            $keepIds = collect($validated['items'])->pluck('id')->filter()->toArray();
            $contract->contractItems()->whereNotIn('id', $keepIds)->delete();

            $totalPurchaseAmount = 0;
            $totalEstimatedRefund = 0;

            // 🚀 步骤三：循环重整、降维注入货物明细矩阵
            foreach ($validated['items'] as $itemData) {
                // 🎯 核心修正 1：显式强力加载所属的 category 大类，防止单位读取发生 Null 崩溃
                $itemObj = Item::with('category')->findOrFail($itemData['item_id']);

                // 🎯 核心修正 2：从所属的母体大类中，笔直取出法定的单位快照
                $categoryUnit = $itemObj->category->unit ?? 'PCS';

                $elementString = implode('|', $itemData['element_values']).'|';
                $totalAmount = $itemData['quantity'] * $itemData['unit_price'];

                $saveData = [
                    'item_id' => $itemData['item_id'],
                    'unit' => $categoryUnit, // 👈 成功将大类的单位固化存根入库！
                    'supplier_id' => $itemData['supplier_id'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'purchase_price_snapshot' => $itemData['purchase_price_snapshot'],
                    'total_amount' => $totalAmount,
                    'packages' => $itemData['packages'],
                    'package_type' => $itemData['package_type'],
                    'net_weight' => $itemData['net_weight'],
                    'gross_weight' => $itemData['gross_weight'],
                    'volume' => $itemData['volume'],
                    'declared_elements' => $elementString,
                ];

                if (! empty($itemData['id'])) {
                    ContractItem::findOrFail($itemData['id'])->update($saveData);
                } else {
                    ContractItem::create(array_merge(['contract_id' => $contract->id], $saveData));
                }

                // 📝 步骤四：终极业财税联动重算——完全以本次下单实际进价为准核算税款
                $estimatedPurchaseCost = $itemData['quantity'] * $itemData['purchase_price_snapshot'];
                $totalPurchaseAmount += $estimatedPurchaseCost;
                $totalEstimatedRefund += ($estimatedPurchaseCost / 1.13) * ($itemObj->tax_refund_rate / 100);
            }

            // 🔒 步骤五：将最精准的应收发票总额、应申报退税额归档写入财务大盘追踪表
            FinancialTracker::updateOrCreate(
                ['contract_id' => $contract->id],
                [
                    'purchase_total_amount' => round($totalPurchaseAmount, 2),
                    'estimated_refund' => round($totalEstimatedRefund, 2),
                ]
            );
        });

        // 🎯 笔直触发前端母版雷达监视，在屏幕右上角弹出一枚极其丝滑的绿色 Sonner 喜报
        return redirect()->back()->with('success', '合同主体条款、高精度件毛净物流链与多商户退税底账已全网动态配平完成！');
    }

    /**
     * 🎯 终极必杀：加载海关官方格式 Excel 模板，精准填空并输出二进制流
     */
    public function exportExcelTemplate(int $id): StreamedResponse
    {
        $unitMap = [
            'ctns' => '箱',
            'plts' => '托盘',
            'pcs' => '件',
            'bags' => '袋',
            'bbls' => '桶',
        ];
        // 1. 深度多表联查：拉出合同、商品档案、境外进口商、国内工厂
        $contract = Contract::with(['contractItems.item', 'financialTracker', 'exporter'])->findOrFail($id);
        $importer = Importer::find($contract->importer_id);
        // 🎯 核心修正：规范申报要素串预览中的 H.S. 编码，同样平滑穿透获取
        // $declarationElements = [
        //     'items' => $contract->contractItems->map(fn ($ci) => [
        //         'hs_code' => $ci->item->category->hs_code ?? '',
        //         'name_cn' => $ci->item->name_cn ?? '',
        //         'declared_elements' => $ci->declared_elements ?? '未填写申报要素',
        //     ]),
        // ];
        // 2. 定位并安全读取本地存放的 Excel 模板
        $templatePath = storage_path('app/templates/customs-template.xlsx');
        if (! file_exists($templatePath)) {
            abort(404, '制单系统提示：未找到海关官方报关单 Excel 模板！');
        }

        // 🎯 加载整个 Spreadsheet 实体，包含其内部所有的 5 个 Sheet 结构
        $spreadsheet = IOFactory::load($templatePath);
        $items = $contract->contractItems;
        $itemCount = count($items);

        // 定义通用边框样式
        $borderStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D3D3D3'],
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ];
        // =====================================================================
        // 🛠️ 核心补齐模块 1：精准填充并循环追加 INVOICE 工作表
        // =====================================================================
        $invoiceSheet = $spreadsheet->getSheetByName('INVOICE') ?? $spreadsheet->getSheet(0);
        $transportText = $contract->transport_mode === '2' ? 'SEA' : 'AIR';
        // 基础信息精准填空
        $invoiceSheet->setCellValue('B8', $importer ? $importer->company_name_en : 'GLOBAL INDUSTRIAL CO., LTD.'); // 🎯 补齐买方名称
        $invoiceSheet->setCellValue('B9', $importer ? $importer->company_address_en : 'Korea'); // 🎯 补齐买方名称
        $invoiceSheet->setCellValue('E8', 'INV-'.$contract->contract_no);
        $invoiceSheet->setCellValue('E9', $contract->contract_date);
        $invoiceSheet->setCellValue('A12', '1. PORT OF LOADING: '.strtoupper($contract->port_of_loading));
        $invoiceSheet->setCellValue('A13', '2. PORT OF DESTINATION: '.strtoupper($contract->port_of_destination));
        $invoiceSheet->setCellValue('A14', '3. TERMS OF PAYMENT: '.strtoupper($contract->payment_terms));
        $invoiceSheet->setCellValue('A15', '4. DELIVERY: BY: '.$transportText);

        // 🎯 补齐大货明细：从 A18 航道极速追加
        $invoiceStartRow = 18;
        // 💡 痛点 1 解决：如果商品超过1件，在初始行（18行）后面动态插入空行，避免覆盖底层模板
        // if ($itemCount > 1) {
        //     $invoiceSheet->insertNewRowBefore($invoiceStartRow + 1, $itemCount - 1);
        // }
        foreach ($items as $index => $ci) {
            $invRow = $invoiceStartRow + $index;
            $itemObj = $ci->item;

            // 依据您的 INVOICE 原生表头布局，横向精准填空（通常为：品名/数量/单价/总金额）
            $invoiceSheet->setCellValue("A{$invRow}", $index + 1); // 项号
            $invoiceSheet->setCellValue("B{$invRow}", $itemObj ? $itemObj->name_en : 'Unknown Item'); // 🎯 商业发票严格使用英文品名
            $invoiceSheet->setCellValue("C{$invRow}", (float) $ci->unit_price);

            // 1. 获取并确定单位（优先使用合规快照，其次是档案关联单位，兜底为 PCS）
            $rawUnit = $ci->unit ?: ($itemObj && $itemObj->category ? $itemObj->category->unit : 'PCS');
            $unitDisplay = $unitMap[strtolower($rawUnit)] ?? $rawUnit; // 转换成中文单位如 "箱/件"，未匹配则保留原样

            // 2. 初始化数量、净重、毛重（确保转换为数值型）
            $quantity = (float) $ci->quantity;

            // 🎯 核心逻辑翻译：判断单位是否包含千克、米、升、吨
            $isWeightOrLengthUnit = false;
            foreach (['千克', 'kg', '米', 'm', '升', 'l', '吨', 't'] as $keyword) {
                if (mb_stripos($unitDisplay, $keyword) !== false || mb_stripos($rawUnit, $keyword) !== false) {
                    $isWeightOrLengthUnit = true;
                    break;
                }
            }

            if (! $isWeightOrLengthUnit) {
                // 如果不是重量/长度单位：数量强制向下取整（非计重物资不能有小数，如箱、件）
                $quantity = floor($quantity);
                $invoiceSheet->setCellValue("D{$invRow}", $quantity);
            } else {
                // 如果是计重/散装物资：数量直接作为净重
                $netWeight = $quantity;
                // 毛重不能低于净重的 1.03 倍（加 3% 包装皮重兜底）
                $invoiceSheet->setCellValue("D{$invRow}", $quantity);
                $invoiceSheet->getStyle("D{$invRow}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                $invoiceSheet->getStyle('D25')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
            }

            // 方案 A：使用内置常量（格式为 $#,##0.00，保留两位小数，带千分位）
            $invoiceSheet->getStyle("E{$invRow}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_CURRENCY_USD);
            // 💡 痛点 2 解决：使用 Excel 原生公式自动计算单行总价（C列 * E列），比 PHP 计算更专业
            $invoiceSheet->setCellValue("E{$invRow}", "=C{$invRow}*D{$invRow}");

            // 方案 B：自定义格式（如果想要更精准的控制，比如负数显示为红色）
            // $invoiceSheet->getStyle("E{$invRow}:F{$invRow}")->getNumberFormat()->setFormatCode('$#,##0.00');
            // 💡 痛点 3 解决：动态应用单元格边框与对齐样式
            // $invoiceSheet->getStyle("A{$invRow}:F{$invRow}")->applyFromArray($borderStyle);

        }

        // =====================================================================
        // 🛠️ 核心补齐模块 2：精准填充并循环追加 PACKINGLIST 工作表
        // =====================================================================
        $packinglistSheet = $spreadsheet->getSheetByName('PACKINGLIST') ?? $spreadsheet->getSheet(1);

        $packinglistSheet->setCellValue('B8', $importer ? $importer->company_name_en : 'GLOBAL INDUSTRIAL CO., LTD.'); // 🎯 补齐买方名称
        $packinglistSheet->setCellValue('B9', $importer ? $importer->company_address_en : 'Korea'); // 🎯 补齐买方名称
        $packinglistSheet->setCellValue('F8', 'INV-'.$contract->contract_no);
        $packinglistSheet->setCellValue('F9', $contract->contract_date);
        // 🎯 补齐物流装箱明细：从 A18 航道极速追加
        $packingStartRow = 18;
        foreach ($items as $index => $ci) {
            $pkgRow = $packingStartRow + $index;
            $itemObj = $ci->item;

            // 严格对照标准的装箱单格式纵向铺设参数（项号/英文品名/箱数与包装/净重/毛重）
            $packinglistSheet->setCellValue("A{$pkgRow}", $index + 1); // 项号
            $packinglistSheet->setCellValue("B{$pkgRow}", $itemObj ? $itemObj->name_en : 'Unknown Item'); // 英文品名
            // 1. 获取并确定单位（优先使用合规快照，其次是档案关联单位，兜底为 PCS）
            $rawUnit = $ci->unit ?: ($itemObj && $itemObj->category ? $itemObj->category->unit : 'PCS');
            $unitDisplay = $unitMap[strtolower($rawUnit)] ?? $rawUnit; // 转换成中文单位如 "箱/件"，未匹配则保留原样

            // 2. 初始化数量、净重、毛重（确保转换为数值型）
            $quantity = (float) $ci->quantity;
            $netWeight = (float) $ci->net_weight;
            $grossWeight = (float) $ci->gross_weight;

            // 🎯 核心逻辑翻译：判断单位是否包含千克、米、升、吨
            $isWeightOrLengthUnit = false;
            foreach (['千克', 'kg', '米', 'm', '升', 'l', '吨', 't'] as $keyword) {
                if (mb_stripos($unitDisplay, $keyword) !== false || mb_stripos($rawUnit, $keyword) !== false) {
                    $isWeightOrLengthUnit = true;
                    break;
                }
            }

            if (! $isWeightOrLengthUnit) {
                // 如果不是重量/长度单位：数量强制向下取整（非计重物资不能有小数，如箱、件）
                $quantity = floor($quantity);
                $packinglistSheet->setCellValue("C{$pkgRow}", round($quantity));
            } else {
                $packinglistSheet->setCellValue("C{$pkgRow}", $ci->quantity);
                $packinglistSheet->getStyle("C{$pkgRow}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                $packinglistSheet->getStyle('C25')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

                // 如果是计重/散装物资：数量直接作为净重
                $netWeight = $quantity;
                // 毛重不能低于净重的 1.03 倍（加 3% 包装皮重兜底）
                $grossWeight = max($grossWeight, $netWeight * 1.03);
            }
            // 包装件数与包装种类（件数采用整数格式化）

            $packinglistSheet->setCellValue("D{$pkgRow}", (int) $ci->packages);

            // 🎯 保持称重物品的高精度：显式强制转换为 float 浮点数存入，保留小数点
            $packinglistSheet->setCellValue("E{$pkgRow}", (float) $ci->volume);   // 体积
            $packinglistSheet->setCellValue("F{$pkgRow}", (float) $ci->net_weight);   // 净重
            $packinglistSheet->setCellValue("G{$pkgRow}", (float) $ci->gross_weight); // 毛重
        }
        // =====================================================================
        // 🛠️ 核心补齐模块 3：精准填充并循环追加 合同 工作表
        // =====================================================================
        $contractSheet = $spreadsheet->getSheetByName('合同') ?? $spreadsheet->getSheet(2);

        // 🎯 补齐物流装箱明细：从 A18 航道极速追加
        $contractStartRow = 11;
        foreach ($items as $index => $ci) {
            $conRow = $contractStartRow + $index;
            $itemObj = $ci->item;

            // 严格对照标准的装箱单格式纵向铺设参数（项号/英文品名/箱数与包装/净重/毛重）
            $contractSheet->setCellValue("A{$conRow}", $index + 1); // 项号
            $contractSheet->setCellValue("B{$conRow}", $itemObj ? $itemObj->name_en : 'Unknown Item'); // 英文品名

            // 包装件数与包装种类（件数采用整数格式化）
            $contractSheet->setCellValue("C{$conRow}", round($ci->quantity));
            $contractSheet->setCellValue("D{$conRow}", $ci->unit_price);

            // 🎯 保持称重物品的高精度：显式强制转换为 float 浮点数存入，保留小数点
            $contractSheet->setCellValue("E{$conRow}", (float) $ci->total_amount);
        }
        // =====================================================================
        // 🛠️ 重构补齐模块 3：精准填充出口货物报关单工作表（严格契合海关单一窗口矩阵）
        // =====================================================================
        $ws = $spreadsheet->getSheetByName('出口货物报关单') ?? $spreadsheet->getSheet(3);
$exporterObj = $contract->exporter;
$shipperText = $exporterObj 
    ? "{$exporterObj->company_name_cn} ({$exporterObj->tax_id})" 
    : '青岛张秀彬国际贸易有限公司 (91370281MAD83N0A9X)'; // 备用保底

$ws->setCellValue('A3', $shipperText); 

// 动态填充海关申报关别
// $customsText = $exporterObj && $exporterObj->customs_code 
//     ? "烟台海关 ({$exporterObj->customs_code})" 
//     : '烟台海关 (4201)';
// $ws->setCellValue('F3', $customsText);
        // ===== 3.1 合同主体与涉外贸易基础参数 精准填空 =====
        $ws->setCellValue('A6', $importer ? $importer->company_name_en : 'GLOBAL INDUSTRIAL CO., LTD.'); // 境外收货人
        $ws->setCellValue('A10', $contract->contract_no); // 合同协议号
        $ws->setCellValue('G12', strtoupper($contract->incoterms)); // 成交方式 (如 FOB)

        // 计算全套物流包装总件数、总毛净重
        $totalPkgs = $contract->contractItems->sum('packages');
        $totalNW = $contract->contractItems->sum('net_weight');
        $totalGW = $contract->contractItems->sum('gross_weight');

        $ws->setCellValue('A12', '纸箱'); // 包装种类
        $ws->setCellValue('D12', $totalPkgs); // 总件数
        $ws->setCellValue('E12', $totalGW); // 总毛重
        $ws->setCellValue('F12', $totalNW); // 总净重
        // // 🎯 核心高能：海关标准报关单中，第5行通常为物流表头行：
        // // 比如：B5 填合同号，D5 填最终目的国（韩国），F5 填监管方式
        // // 我们现在将 F6 / H6 / G5 等格子彻底改写为动态抓取刚才加进来的参数！
        // $ws->setCellValue('D5', $contract->port_of_destination); // 最终目的港/国

        // // 假设在您的标准模板中，E6 格子是“运输方式”，F6 格子是“启运港”
        // // 2代表水路运输，直接翻译拼接输出
        // $transportText = $contract->transport_mode === '2' ? '水路运输 (2)' : '航空运输 (1)';
        // $ws->setCellValue('D6', $transportText); // 运输方式
        // $ws->setCellValue('J10', $contract->port_of_loading); // 启运港
        // ===== 3.2 货物明细数据矩阵 循环追加（高保真对齐图片横向流） =====
        $startRow = 17; // 严格契合海关大货表格的起始项号行

        foreach ($items as $index => $ci) {
            $row = $startRow + $index;
            $itemObj = $ci->item;

            // 1. A列：项号
            $ws->setCellValue("A{$row}", $index + 1);

            // 2. B列：商品编号 (10位 H.S. Code)
            $ws->setCellValue("B{$row}", $itemObj ? $itemObj->category->hs_code : '');

            // 3. C列：法定中文商品名称
            $ws->setCellValue("C{$row}", $itemObj ? $itemObj->name_cn : '');

            // 4. D列：🎯 规范申报要素长串（独立入格，完美呼应图片红色高亮数据流）
            // 例如: "无牌|汽车用|钢制|", 方便报关员双击本列直接批量复制
            $ws->setCellValue("D{$row}", $ci->declared_elements ?? '-|-|');

            // 启用自动换行与左对齐防错
            $ws->getStyle("D{$row}")->getAlignment()->setWrapText(true);
            $ws->getStyle("D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

            // 5. F列：申报数量及法定单位快照（完美适配重量的小数点输出）
            // PLTS 托盘、有的是 PCS 或默认的 CTNS 箱）
            // 2. 转换为小写去匹配字典，匹配不到则用原始值保底
            $pkgTypeLower = strtolower(trim($ci->package_type));
            $chineseUnit = isset($unitMap[$pkgTypeLower]) ? $unitMap[$pkgTypeLower] : ($ci->package_type ?: '箱');

            // 3. 完美写入 Excel 单元格（输出效果如：10 箱）
            $ws->setCellValue("F{$row}", $ci->packages.' '.$chineseUnit);
            // 6. G列 / H列：离岸外销单价 与 项目外销价税总额
            $unitPrice = $ci->quantity > 0 ? ($ci->total_amount / $ci->quantity) : 0;
            $ws->setCellValue("G{$row}", round($unitPrice, 4).'/'.(float) $ci->total_amount.'/USD');
            // $ws->setCellValue("H{$row}", (float)$ci->total_amount);

            // 7. I列 / J列 / K列：涉外通关国别与海关征免性质
            $ws->setCellValue("H{$row}", '中国 (CHN)'); // 原产国
            $ws->setCellValue("I{$row}", '韩国 (KOR)'); // 目的国

            $producer = Supplier::find($ci->supplier_id);

            $ws->setCellValue("J{$row}", $producer ? mb_substr($producer->company_name, 0, 2) : '义乌');  // 目的国 出口商公司名称取前2位
            $ws->setCellValue("K{$row}", '照章征税 (1)'); // 征免
        }

        // =====================================================================
        // 🛠️ 重构补齐模块 4：申报要素 declaration elements
        // =====================================================================    
        $de = $spreadsheet->getSheetByName('申报要素') ?? $spreadsheet->getSheet(4);

        $startRow = 4; // 严格契合海关大货表格的起始项号行
        $items = $contract->contractItems;

        foreach ($items as $index => $ci) {
            $row = $startRow + $index;
            $itemObj = $ci->item;

            // 1. 抓取 H.S. 编码与申报品名
            $hsCode = $itemObj && $itemObj->category ? $itemObj->category->hs_code : '';
            $categoryName = $itemObj && $itemObj->category ? $itemObj->category->category_name : '';


            // 4. 精准写入 Excel 对应的海关要素航道
            $de->setCellValue("A{$row}", $index + 1);          // 项号
            $de->setCellValue("B{$row}", $hsCode);            // H.S. 编码
            $de->setCellValue("C{$row}", $categoryName);      // 商品名称 (中文)
            $de->setCellValue("D{$row}", $ci->declared_elements); // ✅ 修正：写入拼装好的规范申报要素长文本

            // 💡 顺手为其追加边框美化，保持通篇单证风格一致（复用之前定义好的 $borderStyle）
            if (isset($borderStyle)) {
                $de->getStyle("A{$row}:D{$row}")->applyFromArray($borderStyle);
            }
        }


        // ===== 5. 通过二进制 Stream 管道流直接轰向浏览器下载 =====
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $fileName = "青岛张秀彬报关单_{$contract->contract_no}.xlsx";

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
