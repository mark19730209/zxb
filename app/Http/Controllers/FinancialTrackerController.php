<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\PurchaseInvoice;
use App\Models\FinancialTracker;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Models\InvoiceAllocation;


class FinancialTrackerController extends Controller
{
    /**
     * 1. 财务大盘首页：加载当前合同的所有发票核销流水、已绑定的工厂明细
     */
    public function index2($contractId)
    {
        // 多表联合检索
        $contract = Contract::with(['financialTracker'])->findOrFail($contractId);
        
        // 拉出当前合同下已经录入的所有历史发票流水，并附带供应商名称
        $existingInvoices = PurchaseInvoice::with('supplier')
            ->where('contract_id', $contractId)
            ->orderBy('id', 'desc')
            ->get();

        // 拉出系统中所有的国内供应商，供财务录入时下拉勾选（如桐庐华艺）
        $suppliers = Supplier::select('id', 'company_name', 'tax_id')->get();

        return Inertia::render('Financials/Index', [
            'contract' => $contract,
            'tracker' => $contract->financialTracker,
            'existingInvoices' => $existingInvoices,
            'suppliers' => $suppliers
        ]);
    }

    /**
     * 1. 财务大盘首页：精准捕获分配给当前合同的合并发票拆分细节
     */
    public function index3($contractId)
    {
        // 多表联合检索合同及大盘
        $contract = Contract::with(['financialTracker'])->findOrFail($contractId);
        
        // 🎯 核心补齐与重构：改从【分摊明细表】切入，穿透拉出属于当前合同的合并发票流水
        $existingAllocations = InvoiceAllocation::with(['purchaseInvoice.supplier']) // 级联预加载发票主指纹及工厂
            ->where('contract_id', $contractId)
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($alloc) {
                // 🎯 完美桥接算法：为了不改动您前端的变量渲染习惯，我们将分摊数据扁平化伪装成 Invoice 对象送给前端
                return [
                    'id'           => $alloc->id,
                    'invoice_no'   => $alloc->purchaseInvoice->invoice_no ?? '未知发票号', // 20位长数字流水
                    'issue_date'   => $alloc->purchaseInvoice->issue_date ?? '',
                    'tax_amount'   => $alloc->tax_amount,           // 本次分摊部分的税额
                    'total_amount' => $alloc->allocated_amount,     // 🎯 本次分配给该合同的【实际到账含税面额】
                    'supplier'     => $alloc->purchaseInvoice->supplier ?? null,
                    'file_path'    => $alloc->purchaseInvoice->file_path ?? null // 电子附件原件
                ];
            });

        // 拉出系统内所有的国内供应商，供大盘快捷单发票登记时下拉勾选
        $suppliers = Supplier::select('id', 'company_name', 'tax_id')->get();

        return Inertia::render('Financials/Index', [
            'contract'         => $contract,
            'tracker'          => $contract->financialTracker,
            'existingInvoices' => $existingAllocations, // 🎯 传入桥接对齐后的分摊账目流水
            'suppliers'        => $suppliers
        ]);
    }
    // app/Http/Controllers/FinancialTrackerController.php -> index()

public function index($contractId)
{
    $contract = Contract::with(['financialTracker'])->findOrFail($contractId);
    
// app/Http/Controllers/FinancialTrackerController.php -> index() 内部合流修正

    // 🎯 轨道 A：从【分摊细则表】切入，拉出多订单拆分来的发票数组
    $allocatedInvoices = InvoiceAllocation::with(['purchaseInvoice.supplier'])
        ->where('contract_id', $contractId)
        ->get()
        ->map(function ($alloc) {
            return [
                'id'           => 'alloc_' . $alloc->id, 
                'invoice_no'   => $alloc->purchaseInvoice->invoice_no ?? '未知票号',
                'issue_date'   => $alloc->purchaseInvoice->issue_date ?? '',
                'tax_amount'   => $alloc->tax_amount,
                'total_amount' => $alloc->allocated_amount, 
                'supplier'     => $alloc->purchaseInvoice->supplier ?? null,
                'file_path'    => $alloc->purchaseInvoice->file_path ?? null,
                'type'         => 'multi_allocation'
            ];
        }); // 👈 这里 map 完出来是一个纯 Array 数组

    // 🎯 轨道 B：从【发票主表】切入，拉出快捷录入的单笔发票数组
    $directInvoices = \App\Models\PurchaseInvoice::with('supplier')
        ->where('contract_id', $contractId) 
        ->get()
        ->map(function ($inv) {
            return [
                'id'           => 'direct_' . $inv->id,
                'invoice_no'   => $inv->invoice_no, 
                'issue_date'   => $inv->issue_date,
                'tax_amount'   => $inv->tax_amount,
                'total_amount' => $inv->total_amount, 
                'supplier'     => $inv->supplier,
                'file_path'    => $inv->file_path,
                'type'         => 'direct_single'
            ];
        }); // 👈 这里 map 完出来也是一个纯 Array 数组

    // =====================================================================
    // 🎯 核心修正点：使用 collect() 强行转化为通用数据集合，彻底绝缘 Eloquent 的 getKey() 报错！
    // =====================================================================
    $mergedInvoices = collect($allocatedInvoices)
        ->merge(collect($directInvoices))
        ->sortByDesc('issue_date')
        ->values()
        ->toArray(); // 🎯 固化为干净的纯二维数组向前端投递

    $suppliers = Supplier::select('id', 'company_name', 'tax_id')->get();

    return Inertia::render('Financials/Index', [
        'contract'         => $contract,
        'tracker'          => $contract->financialTracker,
        'existingInvoices' => $mergedInvoices, // 🎯 吐给前端合流后无暇的专票列表
        'suppliers'        => $suppliers
    ]);

}


    /**
     * 2. 核心补齐：登记收到的国内供货商进货增值税专用发票（严格 fillable 白名单）
     */
    public function registerInvoice(Request $request, $contractId)
    {
        $contract = Contract::findOrFail($contractId);

        // 数电票、纸质专票统一使用 invoice_no 字段存储完整发票号码，例如：26332000005417048116
        $validated = $request->validate([
            'supplier_id'   => 'required|exists:suppliers,id',
            'invoice_no'    => 'required|string|max:20',//|unique:purchase_invoices,invoice_no', // 数电票/专票统一发票号码
            'issue_date'    => 'required|date',
            'total_amount'  => 'required|numeric|min:0.01', // 价税合计含税总额
            'tax_rate'      => 'required|numeric|in:13,9,6,0', // 绝大多数服装外贸为13%
        ], [
            'invoice_no.unique' => '系统审计拦截：该增值税发票号码已被登记过，切勿重复报销！'
        ]);

        DB::transaction(function () use ($contract, $validated) {
            // 依据国家税务总局规则，自动倒推不含税价与税额
            // 13%税率下：不含税金额 = 价税合计 / 1.13
            $taxParam = 1 + ($validated['tax_rate'] / 100);
            $taxExclusiveAmount = $validated['total_amount'] / $taxParam;
            $taxAmount = $validated['total_amount'] - $taxExclusiveAmount;

            // 1. 写入发票流水明细（发票代码与号码已合并为统一发票号码）
            PurchaseInvoice::create([
                'contract_id'          => $contract->id,
                'supplier_id'          => $validated['supplier_id'],
                'invoice_no'           => $validated['invoice_no'],
                'issue_date'           => $validated['issue_date'],
                'tax_exclusive_amount' => round($taxExclusiveAmount, 2),
                'tax_amount'           => round($taxAmount, 2),
                'total_amount'         => $validated['total_amount'],
                'status'               => 'verified' // 录入即视作已在单一窗口勾馨认证
            ]);

            // 2. 重新统计该合同项下，累计收到的全部国内工厂发票总额
            $totalReceived = PurchaseInvoice::where('contract_id', $contract->id)->sum('total_amount');

            // 3. 动态推进主追踪大盘的开票进度百分比
            $tracker = FinancialTracker::where('contract_id', $contract->id)->firstOrFail();
            $tracker->update([
                'received_invoice_amount' => $totalReceived
            ]);

            // 4. 状态机平滑流转：如果发票总额已经达到或超过采购预算，合同标记为专票全齐
            if ($totalReceived >= $tracker->purchase_total_amount) {
                $contract->update(['invoice_status' => 'fully_issued']);
            } else {
                $contract->update(['invoice_status' => 'partial']);
            }
        });

        return redirect()->back()->with('success', '发票录入成功！财务追踪数据已自动更新。');
    }

    /**
     * 3. 核心补齐：出口退税实际到账核销
     */
    public function receiveTaxRefund(Request $request, $contractId)
    {
        $contract = Contract::findOrFail($contractId);
        
        $validated = $request->validate([
            'actual_refund_received' => 'required|numeric|min:0',
            'refund_receive_date'    => 'required|date'
        ]);

        $tracker = FinancialTracker::where('contract_id', $contract->id)->firstOrFail();
        
        // 累加本次到账的退税款（支持税务局分批打款入账）
        $newTotalRefund = $tracker->actual_refund_received + $validated['actual_refund_received'];
        
        $tracker->update([
            'actual_refund_received' => $newTotalRefund,
            'refund_receive_date'    => $validated['refund_receive_date'],
            'refund_apply_date'      => $tracker->refund_apply_date ?? now()->toDateString() // 若未填则默认当天申报
        ]);

        // 状态变迁
        if ($newTotalRefund >= $tracker->estimated_refund) {
            $contract->update(['refund_status' => 'received']);
        } else {
            $contract->update(['refund_status' => 'processing']);
        }

        return redirect()->back()->with('success', '国家税务局国库退税到账资金核销成功！');
    }

}
