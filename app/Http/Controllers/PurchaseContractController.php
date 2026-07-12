<?php

namespace App\Http\Controllers;

use App\Models\PurchaseContract;
use App\Models\PurchaseContractItem;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Models\Item;
use Illuminate\Http\Request;
use Spatie\Browsershot\Browsershot;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class PurchaseContractController extends Controller
{
    /**
     * 1. 采购合同总大盘：展示所有跟国内工厂签的合约及专票收交率
     */
    public function index(Request $request)
    {
        // 1. 抓取采购合同台账主盘（包含预加载关联...保持原有逻辑）
        $purchaseContracts = PurchaseContract::with(['supplier', 'purchaseContractItems.item.category'])
            ->withCount([
                'invoices as received_amount' => function ($query) {
                    $query->select(DB::raw('SUM(total_amount)'));
                }
            ])
            ->orderBy('id', 'desc')
            ->paginate(15);

        // 2. 🎯 核心补齐：拉取全网海关大类（Category），供第一级下拉勾选
        $categories = \App\Models\Category::select('id', 'category_name', 'hs_code', 'unit')->get();

        // 3. 🎯 核心补齐：拉取全网具体款式 SKU（Item），包含大类外键，供第二级级联过滤
        $items = \App\Models\Item::select('id', 'category_id', 'sku', 'name_cn', 'purchase_price', 'image_path')->where('is_actived',true)->orderBy('id', 'desc')->get();

        return Inertia::render('PurchaseContracts/Index', [
            'purchaseContracts' => $purchaseContracts,
            'suppliers'         => Supplier::select('id', 'company_name')->get(),
            'categories'        => $categories, // 👈 笔直送给第一级
            'items'             => $items       // 👈 笔直送给第二级
        ]);
    }

    /**
     * 2. 保存新采购合同并级联并入工厂大货货品
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_contract_no' => 'required|string|unique:purchase_contracts,purchase_contract_no',
            'signing_date' => 'required|date',
            'delivery_terms' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.purchase_price' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($validated) {
            // A. 创建采购合同主根
            $contract = PurchaseContract::create([
                'supplier_id' => $validated['supplier_id'],
                'purchase_contract_no' => $validated['purchase_contract_no'],
                'signing_date' => $validated['signing_date'],
                'delivery_terms' => $validated['delivery_terms'],
                'total_rmb_amount' => 0
            ]);

            $grandTotalRmb = 0;

            // B. 循环灌入合同明细货物
            foreach ($validated['items'] as $itemData) {
                $lineTotal = $itemData['quantity'] * $itemData['purchase_price'];
                $grandTotalRmb += $lineTotal;

                PurchaseContractItem::create([
                    'purchase_contract_id' => $contract->id,
                    'item_id' => $itemData['item_id'],
                    'quantity' => $itemData['quantity'],
                    'purchase_price' => $itemData['purchase_price'],
                    'total_amount' => round($lineTotal, 2)
                ]);
            }

            // C. 回刷更新含税总额
            $contract->update(['total_rmb_amount' => round($grandTotalRmb, 2)]);
        });

        return redirect()->back()->with('success', '📥 与国内供货工厂的人民币采购合同签署成功！');
    }

    /**
     * 3. 🎯 核心高能：20位专票笔直对齐扣减特定采购合同
     */
    public function linkInvoice(Request $request)
    {
        $validated = $request->validate([
            'purchase_contract_id' => 'required|exists:purchase_contracts,id',
            'invoice_no' => 'required|string|size:20|regex:/^[0-9]+$/|unique:purchase_invoices,invoice_no',
            'issue_date' => 'required|date',
            'total_amount' => 'required|numeric|min:0.01',
            'tax_rate' => 'required|numeric|default:13'
        ]);

        $purchaseContract = PurchaseContract::findOrFail($validated['purchase_contract_id']);

        DB::transaction(function () use ($purchaseContract, $validated) {
            $taxParam = 1 + ($validated['tax_rate'] / 100);

            // 1. 创建发票并强力绑定采购合同外键
            PurchaseInvoice::create([
                'purchase_contract_id' => $purchaseContract->id,
                'supplier_id' => $purchaseContract->supplier_id,
                'invoice_no' => $validated['invoice_no'],
                'issue_date' => $validated['issue_date'],
                'tax_exclusive_amount' => round($validated['total_amount'] / $taxParam, 2),
                'tax_amount' => round($validated['total_amount'] - ($validated['total_amount'] / $taxParam), 2),
                'total_amount' => $validated['total_amount'],
                'status' => 'verified'
            ]);

            // 2. 统计当前采购合同已收到的所有发票总面额
            $totalReceived = PurchaseInvoice::where('purchase_contract_id', $purchaseContract->id)->sum('total_amount');

            // 3. 联动刷新发票催收状态机
            if ($totalReceived >= $purchaseContract->total_rmb_amount) {
                $purchaseContract->update(['invoice_status' => 'fully_issued']);
            } else {
                $purchaseContract->update(['invoice_status' => 'partial']);
            }
        });

        return redirect()->back()->with('success', '✔️ 20位专用发票已精准挂账并核销目标采购合同！');
    }

    /**
     * 🎯 核心补齐：修改国内采购合同条款，并联动重整货品明细与含税总额
     */
    public function update(Request $request, $id)
    {
        $purchaseContract = PurchaseContract::findOrFail($id);

        $validated = $request->validate([
            'supplier_id'            => 'required|exists:suppliers,id',
            'purchase_contract_no'   => 'required|string|max:50|unique:purchase_contracts,purchase_contract_no,' . $purchaseContract->id,
            'signing_date'           => 'required|date',
            'delivery_terms'         => 'nullable|string',
            
            // 货物明细动态数组校验
            'items'                  => 'required|array|min:1',
            'items.*.id'             => 'nullable',
            'items.*.item_id'        => 'required|exists:items,id',
            'items.*.quantity'       => 'required|numeric|min:0.0001',
            'items.*.purchase_price' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($purchaseContract, $validated) {
            // A. 覆盖更新合同主表属性
            $purchaseContract->update([
                'supplier_id'          => $validated['supplier_id'],
                'purchase_contract_no' => $validated['purchase_contract_no'],
                'signing_date'         => $validated['signing_date'],
                'delivery_terms'       => $validated['delivery_terms'],
            ]);

            // B. 物理安全拦截：如果外贸员在前端删除了某一行采购商品，后端同步从 MySQL 切断
            $keepIds = collect($validated['items'])->pluck('id')->filter()->toArray();
            $purchaseContract->purchaseContractItems()->whereNotIn('id', $keepIds)->delete();

            $grandTotalRmb = 0;

            // C. 循环重整大货行项
            foreach ($validated['items'] as $itemData) {
                $lineTotal = $itemData['quantity'] * $itemData['purchase_price'];
                $grandTotalRmb += $lineTotal;

                $saveData = [
                    'item_id'        => $itemData['item_id'],
                    'quantity'       => $itemData['quantity'],
                    'purchase_price' => $itemData['purchase_price'],
                    'total_amount'   => round($lineTotal, 2)
                ];

                if (!empty($itemData['id'])) {
                    PurchaseContractItem::findOrFail($itemData['id'])->update($saveData);
                } else {
                    PurchaseContractItem::create(array_merge(['purchase_contract_id' => $purchaseContract->id], $saveData));
                }
            }

            // D. 重新回刷含税总额
            $purchaseContract->update(['total_rmb_amount' => round($grandTotalRmb, 2)]);

            // E. 重新勾稽已收到的20位发票，平滑重整状态机
            $totalReceived = PurchaseInvoice::where('purchase_contract_id', $purchaseContract->id)->sum('total_amount');
            $purchaseContract->update([
                'invoice_status' => $totalReceived >= round($grandTotalRmb, 2) ? 'fully_issued' : ($totalReceived > 0 ? 'partial' : 'none')
            ]);
        });

        return redirect()->back()->with('success', '✔️ 成功：国内采购合同条款、大货采购额与发票催收雷达已实时重新配平！');
    }

    public function exportPdf($id, Request $request)
    {
        // 1. 🎯 解除注释！严格按照你台账主盘的关联结构查出当前的单条数据
        $contract = PurchaseContract::with(['supplier', 'purchaseContractItems.item.category'])
            ->findOrFail($id);

        // 2. 将真正的 $contract 实体通过数组喂给 Blade 模板
        $html = view('pdf.contract', [
            'contract' => $contract // 🎯 必须确保这里传了变量，否则 Blade 找不到 $contract
        ])->render();

        // 3. 召唤 Browsershot 渲染
        $pdfBinary = Browsershot::html($html)
            ->setNodeBinary('/usr/local/bin/node')
            ->setNpmBinary('/usr/local/bin/npm')
            ->setIncludePath('/usr/local/bin:/usr/bin:/bin:/usr/sbin:/sbin')
            ->landscape() // 横向
            ->margins(10, 10, 10, 10)
            ->format('A4')
            ->emulateMedia('screen')
            ->showBackground()
            ->noSandbox()         // 🎯 绝杀：允许无头 Chrome 自由穿透读取你 Mac 本地磁盘的商品图片
            ->pdf();
        // 3. 🎯 核心新增：将 PDF 保存到服务端指定的物理磁盘路径下
        // 构造文件名，如: contracts/ZXB26052801.pdf
        $fileName = 'contracts/ZXB_Contract_' . $contract->purchase_contract_no . '.pdf';
        
        // 默认保存到 storage/app/private/contracts/ 目录下（更加安全，外部无法通过URL盲猜下载）
        // Storage::put($fileName, $pdfBinary);
        Storage::disk('public')->put($fileName, $pdfBinary);
        /* 💡 如果你需要让这个文件在服务器上能被外网直接通过网址访问，请改用 public 磁盘：
         * Storage::disk('public')->put($fileName, $pdfBinary);
         * 存储后路径为：storage/app/public/contracts/xxx.pdf
         * 对应的外网访问网址为：asset('storage/' . $fileName)
         */

        // 4. 🎯 可选：将文件路径物理覆写到当前采购合同的数据库字段中，方便以后随时调取
        $contract->update(['pdf_path' => $fileName]);

        // 4. 返回 PDF 二进制数据响应
        return response($pdfBinary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="ZXB-Contract-' . $contract->purchase_contract_no . '.pdf"',
            'Cache-Control' => 'no-cache, private',
        ]);
    }
    /**
     * 2. 🎯 核心新增：后期直接秒速下载的历史归档路由（无需再消耗无头 Chrome 性能）
     */
    public function downloadArchivedPdf($id)
    {
        $contract = PurchaseContract::findOrFail($id);

        // 稳妥防护：如果该合同从来没生成过 PDF，或者物理文件在磁盘被删了，自动降级去实时重新生成
        if (!$contract->pdf_path || !Storage::exists($contract->pdf_path)) {
            // 优雅重定向到上面的实时导出方法，自动补齐历史漏洞
            return $this->exportPdf($id, request());
        }

        // ⚡ 直接调取服务端现成文件流返回，速度极快（几毫秒级别）
        return Storage::download(
            $contract->pdf_path, 
            "ZXB_购销合同_{$contract->purchase_contract_no}_历史归档.pdf"
        );
    }

}
