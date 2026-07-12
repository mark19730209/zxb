<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\Item;
use App\Models\Supplier;
use App\Models\FinancialTracker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ContractItemController extends Controller
{
    /**
     * 1. 展现某个合同下的所有物品明细台账（含级联基础数据）
     */
    public function index($contractId)
    {
        $contract = Contract::findOrFail($contractId);
        
        // 抓取该合同下的所有大货物品，级联预加载基础品名和负责开票的国内工厂
        $contractItems = ContractItem::with(['item', 'supplier'])
            ->where('contract_id', $contractId)
            ->get()
            ->map(function($ci) {
                // 反序列化海关要素
                if ($ci->item && is_string($ci->item->category->elements_template)) {
                    $ci->item->category->elements_template = json_decode($ci->item->category->elements_template, true) ?: [];
                }
                $ci->element_values_array = $ci->declared_elements ? explode('|', rtrim($ci->declared_elements, '|')) : [];
                return $ci;
            });

        // 提取系统内所有的商品和工厂，供追加明细时下拉勾选
        $availableItems = Item::all()->map(function($item) {
            $item->category->elements_template = is_string($item->category->elements_template) ? json_decode($item->category->elements_template, true) : $item->category->elements_template;
            return $item;
        });
        $availableSuppliers = Supplier::select('id', 'company_name')->get();

        return Inertia::render('Contracts/Items/Index', [
            'contract' => $contract,
            'contractItems' => $contractItems,
            'availableItems' => $availableItems,
            'availableSuppliers' => $availableSuppliers
        ]);
    }

    /**
     * 2. 单独保存或追加一项合同物品
     */
    public function store_2(Request $request, $contractId)
    {
        $contract = Contract::findOrFail($contractId);

        $validated = $request->validate([
            'item_id' => 'required|exists:items,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'quantity' => 'required|integer|min:1',
            'unit_price' => 'required|numeric|min:0.0001',
            'packages' => 'required|integer|min:0',
            'package_type' => 'required|string',
            'net_weight' => 'required|numeric|min:0',
            'gross_weight' => 'required|numeric|min:0',
            'volume' => 'required|numeric|min:0',
            'element_values' => 'required|array',
        ]);

        DB::transaction(function () use ($contract, $validated) {
            // 组装海关标准规范申报要素“|”长串
            $elementString = implode('|', $validated['element_values']) . '|';
            $totalAmount = $validated['quantity'] * $validated['unit_price'];

            ContractItem::create([
                'contract_id' => $contract->id,
                'item_id' => $validated['item_id'],
                'supplier_id' => $validated['supplier_id'],
                'quantity' => $validated['quantity'],
                'unit_price' => $validated['unit_price'],
                'total_amount' => $totalAmount,
                'packages' => $validated['packages'],
                'package_type' => $validated['package_type'],
                'net_weight' => $validated['net_weight'],
                'gross_weight' => $validated['gross_weight'],
                'volume' => $validated['volume'],
                'declared_elements' => $elementString,
            ]);

            // 🎯 同步配平该合同的采购额与出口退税底账
            $this->recalculateContractFinancials($contract->id);
        });

        return redirect()->back()->with('success', '成功为合同追加出口物品明细！');
    }
    /**
     * 保存或更新合同的商品明细及申报要素
     */
    public function store_org(Request $request, $contractId)
    {
        $contract = Contract::findOrFail($contractId);

        $validated = $request->validate([
            'items' => 'required|array',
            'item_id' => 'required|exists:items,id',
            'quantity' => 'required|integer|min:1',
            'unit_price' => 'required|numeric|min:0',
            'packages' => 'required|integer|min:1',
            'package_type' => 'required|string',
            'net_weight' => 'required|numeric|min:0',
            'gross_weight' => 'required|numeric|min:0',
            'volume' => 'required|numeric|min:0',
            'element_values' => 'required|array', // 前端传来的各项要素键值对
        ]);

        DB::transaction(function () use ($contract, $validated) {
            // 1. 清理原有的合同明细
            $contract->contractItems()->delete();

            $totalEstimatedRefund = 0;
            $totalPurchaseAmount = 0;

            foreach ($validated['items'] as $itemData) {
                $itemObj = Item::find($itemData['item_id']);
                
                // 2. 将数组形式的要素值组装成海关标准的“|”分隔字符串
                // 例如: [0 => "无牌", 1 => "汽车用", 2 => "钢制"] -> "无牌|汽车用|钢制|"
                $elementString = implode('|', $itemData['element_values']) . '|';

                $totalAmount = $itemData['quantity'] * $itemData['unit_price'];

                ContractItem::create([
                    'contract_id' => $contract->id,
                    'item_id' => $itemData['item_id'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'total_amount' => $totalAmount,
                    'packages' => $itemData['packages'],
                    'package_type' => $itemData['package_type'],
                    'net_weight' => $itemData['net_weight'],
                    'gross_weight' => $itemData['gross_weight'],
                    'volume' => $itemData['volume'],
                    'declared_elements' => $elementString,
                ]);

                // 3. 计算国内供应链预算 (计算退税基数)
                // 假设国内进货总总额 = 签约数量 * 商品预设国内含税采购价
                $estimatedPurchaseCost = $itemData['quantity'] * $itemObj->purchase_price;
                $totalPurchaseAmount += $estimatedPurchaseCost;

                // 4. 核心退税公式：不含税进货价 * 退税率
                // 国内增值税率为13%时：不含税价 = 含税价 / 1.13
                $noTaxPrice = $estimatedPurchaseCost / 1.13;
                $totalEstimatedRefund += $noTaxPrice * ($itemObj->tax_refund_rate / 100);
            }

            // 5. 更新或创建财务合规追踪表
            FinancialTracker::updateOrCreate(
                ['contract_id' => $contract->id],
                [
                    'purchase_total_amount' => $totalPurchaseAmount,
                    'estimated_refund' => round($totalEstimatedRefund, 2),
                ]
            );
        });

        return redirect()->back()->with('success', '单据数据与申报要素保存成功，已自动更新退税预算！');
    }
    /**
     * 3. 异步修改单项物品（数量、外销单价及物流体积）
     */
    public function update(Request $request, $contractId, $itemId)
    {
        $ci = ContractItem::where('contract_id', $contractId)->where('id', $itemId)->firstOrFail();

        $validated = $request->validate([
            // ... 其他合同及物品 ID 验证 ...
            'quantity'     => 'required|numeric|min:0.0001',
            'unit_price'   => 'required|numeric|min:0',
            'packages'     => 'required|integer|min:0',
            'net_weight'   => 'required|numeric|min:0',
            'gross_weight' => 'required|numeric|min:0', 
            'volume'       => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($ci, $validated, $contractId) {
            $ci->update([
                'quantity' => $validated['quantity'],
                'unit_price' => $validated['unit_price'],
                'total_amount' => $validated['quantity'] * $validated['unit_price'],
                'packages' => $validated['packages'],
                'net_weight' => $validated['net_weight'],
                'gross_weight' => $validated['gross_weight'],
                'volume' => $validated['volume'],
            ]);

            $this->recalculateContractFinancials($contractId);
        });

        return redirect()->back()->with('success', '物品外销商务及包装数据已成功修正！');
    }

    /**
     * 4. 移除单项合同商品明细
     */
    public function destroy($contractId, $itemId)
    {
        $ci = ContractItem::where('contract_id', $contractId)->where('id', $itemId)->firstOrFail();

        DB::transaction(function () use ($ci, $contractId) {
            $ci->delete();
            $this->recalculateContractFinancials($contractId);
        });

        return redirect()->back()->with('success', '出口物品已成功从合同明细中移除。');
    }

    /**
     * 🎯 私有财务联动引擎：当合同明细发生任何变化，全自动重置财务退税底账
     */
    private function recalculateContractFinancials($contractId)
    {
        $items = ContractItem::where('contract_id', $contractId)->get();
        $totalPurchaseAmount = 0;
        $totalEstimatedRefund = 0;

        foreach ($items as $ci) {
            $itemObj = Item::find($ci->item_id);
            if ($itemObj) {
                $estimatedPurchaseCost = $ci->quantity * $itemObj->purchase_price;
                $totalPurchaseAmount += $estimatedPurchaseCost;
                $totalEstimatedRefund += ($estimatedPurchaseCost / 1.13) * ($itemObj->tax_refund_rate / 100);
            }
        }

        FinancialTracker::updateOrCreate(
            ['contract_id' => $contractId],
            [
                'purchase_total_amount' => $totalPurchaseAmount,
                'estimated_refund' => round($totalEstimatedRefund, 2),
            ]
        );
    }

    private function adjustWeightAndCalculateAmount($itemData, $itemObj)
    {
        // 🎯 核心高能：显式强制转换为 float 浮点数进行算力配平
        $quantity = (float)$itemData['quantity']; 
        $unitPrice = (float)$itemData['unit_price'];
        
        $unit = strtolower($itemObj->unit);
        $isWeightBased = (str_contains($unit, '千克') || str_contains($unit, 'kg') || str_contains($unit, '公斤') || str_contains($unit, '吨'));

        // 称重物资总净重全自动咬合等于带小数的数量
        $netWeight = $isWeightBased ? $quantity : (float)$itemData['net_weight'];
        // 毛重在净重基础上进行包装溢出估算
        $grossWeight = $isWeightBased ? max((float)$itemData['gross_weight'], $netWeight * 1.03) : (float)$itemData['gross_weight'];
        
        return [
            // 🎯 核心修正：货款总额严格保留 2 位财务小数
            'total_amount' => round($quantity * $unitPrice, 2),
            'net_weight' => round($netWeight, 3),   // 净重保留3位
            'gross_weight' => round($grossWeight, 3) // 毛重保留3位
        ];
    }

    /**
     * 2. 保存/追加合同物品（重量结算适配版）
     */
    public function store(Request $request, $contractId)
    {
        $contract = Contract::findOrFail($contractId);
        $itemObj = Item::findOrFail($request->item_id);

        $validated = $request->validate([
            'item_id'        => 'required|exists:items,id',
            'supplier_id'    => 'required|exists:suppliers,id',
            'quantity'       => 'required|numeric|min:0.0001',
            'unit_price'     => 'required|numeric|min:0',
            'packages'       => 'required|integer|min:0',
            'package_type'   => 'required|string',
            'net_weight'     => 'required|numeric|min:0',
            'gross_weight'   => 'required|numeric|min:0',
            'volume'         => 'required|numeric|min:0',
            'element_values' => 'required|array',
        ]);


        DB::transaction(function () use ($contract, $itemObj, $validated) {
            // 🎯 调用重量智能配平算法
            $adjusted = $this->adjustWeightAndCalculateAmount($validated, $itemObj);
            
            $elementString = implode('|', $validated['element_values']) . '|';

            ContractItem::create([
                'contract_id' => $contract->id,
                'item_id' => $validated['item_id'],
                'supplier_id' => $validated['supplier_id'],
                'quantity' => $validated['quantity'],
                'unit_price' => $validated['unit_price'],
                'total_amount' => $adjusted['total_amount'],
                'packages' => $validated['packages'],
                'package_type' => $validated['package_type'],
                'net_weight' => $adjusted['net_weight'],       // 自动对齐后的净重
                'gross_weight' => $adjusted['gross_weight'],   // 自动对齐后的毛重
                'volume' => $validated['volume'],
                'declared_elements' => $elementString,
            ]);

            $this->recalculateContractFinancials($contract->id);
        });

        return redirect()->back()->with('success', '商品明细已成功并入！重量与单证包装净重已自动咬合。');
    }
}
