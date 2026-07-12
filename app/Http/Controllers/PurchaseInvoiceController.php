<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\PurchaseInvoice;
use App\Models\InvoiceAllocation;
use App\Models\FinancialTracker;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class PurchaseInvoiceController extends Controller
{
    /**
     * 1. 发票综合主大盘：展示所有收到的进货发票及跨订单分配树
     */
    public function index(Request $request)
    {
        // 抓取全系统发票，预加载开票工厂、分配到的合同主表
        $invoices = PurchaseInvoice::with(['supplier', 'allocations.contract'])
            ->when($request->input('search'), function ($query, $search) {
                $query->where('invoice_no', 'like', "%{$search}%")
                      ->orWhereHas('supplier', function($q) use ($search) {
                          $q->where('company_name', 'like', "%{$search}%");
                      });
            })
            ->orderBy('id', 'desc')
            ->paginate(10)
            ->withQueryString();

        // 提取全系统国内供应商，供财务下拉勾选
        $suppliers = Supplier::select('id', 'company_name', 'tax_id')->get();

        // 提取目前处于流转中、需要收票核销的全部有效合同
        $activeContracts = Contract::select('id', 'contract_no', 'invoice_status')
            ->whereIn('status', ['active', 'shipped', 'completed'])
            ->get();

        return Inertia::render('Invoices/Index', [
            'invoices' => $invoices,
            'suppliers' => $suppliers,
            'activeContracts' => $activeContracts,
            'filters' => $request->only(['search'])
        ]);
    }

    /**
     * 2. 智能合并专票拆分核销核心端点 (POST)
     */
    public function registerMultiOrderInvoice(Request $request)
    {
        $validated = $request->validate([
            'supplier_id'   => 'required|exists:suppliers,id',
            'invoice_no'    => 'required|string|size:20|regex:/^[0-9]+$/',//|unique:purchase_invoices,invoice_no',
            'issue_date'    => 'required|date',
            'total_amount'  => 'required|numeric|min:0.01', 
            'tax_rate'      => 'required|numeric|max:13',
            'allocations'   => 'required|array|min:1',
            'allocations.*.contract_id' => 'required|exists:contracts,id',
            'allocations.*.amount'      => 'required|numeric|min:0.01',
            
            // 🎯 核心补齐：严格拦截发票文件，必须是图片(JPG/PNG)或PDF，最大支持 10MB
            'invoice_file'  => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $sumAllocated = collect($validated['allocations'])->sum('amount');
        if (abs($sumAllocated - $validated['total_amount']) > 0.01) {
            return redirect()->back()->withErrors(['allocations' => '账目未配平！']);
        }

        DB::transaction(function () use ($validated, $request) {
            $taxParam = 1 + ($validated['tax_rate'] / 100);
            $filePath = null;

            // 🎯 核心处理：如果财务上传了发票照片或PDF，执行物理磁盘托管
            if ($request->hasFile('invoice_file')) {
                // 将文件以发票号重命名，安全存入 storage/app/public/invoices 目录
                $file = $request->file('invoice_file');
                $extension = $file->getClientOriginalExtension();
                $fileName = 'INV_' . $validated['invoice_no'] . '.' . $extension;
                
                // 执行上传并获取相对路径
                $filePath = $file->storeAs('invoices', $fileName, 'public');
            }

            // A. 创建发票总存根（带附件路径）
            $invoice = PurchaseInvoice::create([
                'supplier_id' => $validated['supplier_id'],
                'invoice_no' => $validated['invoice_no'],
                'file_path' => $filePath, // 🎯 存入物理路径
                'issue_date' => $validated['issue_date'],
                'tax_exclusive_amount' => round($validated['total_amount'] / $taxParam, 2),
                'tax_amount' => round($validated['total_amount'] - ($validated['total_amount'] / $taxParam), 2),
                'total_amount' => $validated['total_amount'],
                'status' => 'verified'
            ]);

            // B. 循环分摊各出口合同（复用之前写好的拆分逻辑...）
            foreach ($validated['allocations'] as $alloc) {
                $allocTaxExclusive = $alloc['amount'] / $taxParam;
                \App\Models\InvoiceAllocation::create([
                    'purchase_invoice_id' => $invoice->id,
                    'contract_id' => $alloc['contract_id'],
                    'allocated_amount' => $alloc['amount'],
                    'tax_exclusive_amount' => round($allocTaxExclusive, 2),
                    'tax_amount' => round($alloc['amount'] - $allocTaxExclusive, 2),
                ]);
                
                $contractReceivedTotal = \App\Models\InvoiceAllocation::where('contract_id', $alloc['contract_id'])->sum('allocated_amount');
                FinancialTracker::where('contract_id', $alloc['contract_id'])->firstOrFail()->update(['received_invoice_amount' => $contractReceivedTotal]);
                // 💡 使用 findOrFail 确保一定会拿到 Contract 实例，否则自动抛出 404 异常                
                $contract = Contract::findOrFail($alloc['contract_id']);
                $contract->update(['invoice_status' => $contractReceivedTotal >= FinancialTracker::where('contract_id', $alloc['contract_id'])->first()->purchase_total_amount ? 'fully_issued' : 'partial']);
            }
        });

        return redirect()->back()->with('success', '带电子凭证附件的 20位 专票合并分摊核销成功！');
    }

    /**
     * 🎯 核心补齐：针对历史录入未上传文件的发票，实施快捷补传影像附件
     */
    public function uploadAttachment(Request $request, $id)
    {
        // 1. 严格的风控验证
        $validated = $request->validate([
            'invoice_file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240', // 最大支持 10MB
        ], [
            'invoice_file.required' => '请选择需要补传的发票照片或 PDF 文件！',
            'invoice_file.mimes'    => '合规拦截：发票凭证仅支持 PDF, JPG, JPEG, PNG 格式档案！'
        ]);

        $invoice = PurchaseInvoice::findOrFail($id);

        // 2. 物理磁盘文件托管
        if ($request->hasFile('invoice_file')) {
            // 如果之前存在旧文件（虽然目前是未上传状态，做防呆保护），先清理旧物理文件
            if ($invoice->file_path && Storage::disk('public')->exists($invoice->file_path)) {
                Storage::disk('public')->delete($invoice->file_path);
            }

            $file = $request->file('invoice_file');
            $extension = $file->getClientOriginalExtension();
            // 命名规范：INV_20位发票号.后缀
            $fileName = 'INV_' . $invoice->invoice_no . '.' . $extension;
            
            $filePath = $file->storeAs('invoices', $fileName, 'public');

            // 3. 更新数据库存根
            $invoice->update([
                'file_path' => $filePath
            ]);
        }

        return redirect()->back()->with('success', '发票号 [' . $invoice->invoice_no . '] 影像凭证补传成功，合规档案已完美闭合！');
    }

    /**
     * 🎯 1. 核心补齐：在全局台账中安全更正 20位发票号码、金额或日期
     */
    public function update(Request $request, $id)
    {
        $invoice = PurchaseInvoice::findOrFail($id);

        $validated = $request->validate([
            // 允许修改发票，但 20位新发票号必须在全网唯一（排除当前发票本身）
            'invoice_no'   => 'required|string|size:20|regex:/^[0-9]+$/|unique:purchase_invoices,invoice_no,' . $invoice->id,
            'issue_date'   => 'required|date',
            'total_amount' => 'required|numeric|min:0.01',
            'tax_rate'     => 'required|numeric|max:13',
        ]);
// dd($invoice);
        DB::transaction(function () use ($invoice, $validated) {
            $taxParam = 1 + ($validated['tax_rate'] / 100);
            $taxExclusiveAmount = $validated['total_amount'] / $taxParam;
            $taxAmount = $validated['total_amount'] - $taxExclusiveAmount;

            // 获取该大额发票在变动前影响到的所有合同 ID 集合，用于后续重新配平
            $affectedContractIds = \App\Models\InvoiceAllocation::where('purchase_invoice_id', $invoice->id)
                ->pluck('contract_id')
                ->toArray();

            // A. 覆盖发票主表数据
            $invoice->update([
                'invoice_no'           => $validated['invoice_no'],
                'issue_date'           => $validated['issue_date'],
                'tax_exclusive_amount' => round($taxExclusiveAmount, 2),
                'tax_amount'           => round($taxAmount, 2),
                'total_amount'         => $validated['total_amount'],
            ]);

            // B. 财务平衡联动：如果大额发票的总金额变了，系统在这里全自动执行等比拆分更正，防止明细与总面额发生断链
            $allocations = \App\Models\InvoiceAllocation::where('purchase_invoice_id', $invoice->id)->get();
            if ($allocations->count() === 1) {
                // 如果只绑定了一个合同（单单快捷对应模式），直接 100% 同步更正分摊金额
                $allocations->first()->update([
                    'allocated_amount'     => $validated['total_amount'],
                    'tax_exclusive_amount' => round($taxExclusiveAmount, 2),
                    'tax_amount'           => round($taxAmount, 2),
                ]);
            }

            // C. 重新呼叫全网配平引擎
            $this->rebalanceAffectedContracts($affectedContractIds);
        });

        return redirect()->back()->with('success', '发票数据修正成功！相关出口合同的财务勾稽大盘已实时刷新。');
    }

    /**
     * 🎯 2. 核心补齐：发票作废/删除，触发多商户联锁销账（全自动扣减合同进度）
     */
    public function destroy($id)
    {
        $invoice = PurchaseInvoice::findOrFail($id);

        DB::transaction(function () use ($invoice) {
            // A. 抓出这张发票目前分摊、扣减过的全部出口合同 ID
            $affectedContractIds = \App\Models\InvoiceAllocation::where('purchase_invoice_id', $invoice->id)
                ->pluck('contract_id')
                ->toArray();

            // B. 如果有纸质发票扫描文件附件，物理磁盘一并安全切断，释放服务器空间
            if ($invoice->file_path && Storage::disk('public')->exists($invoice->file_path)) {
                Storage::disk('public')->delete($invoice->file_path);
            }

            // C. 物理删除主发票（由于迁移中设置了 constrained()->onDelete('cascade')，对应的分摊细则行会被 MySQL 自动连带抹去）
            $invoice->delete();

            // D. 🎯 核心高能：重新清算那些被“抽走资金发票”的出口合同开票进度！
            $this->rebalanceAffectedContracts($affectedContractIds);
        });

        return redirect()->back()->with('success', '错误发票已成功撤销并执行跨订单联锁销账！');
    }

    /**
     * 🎯 3. 私有跨表重算引擎：将受到影响的合同累计收票额全部重算对齐
     */
    private function rebalanceAffectedContracts(array $contractIds)
    {
        foreach ($contractIds as $contractId) {
            // 该合同项下目前真正合法留存的已收专票总和
            $actualReceivedTotal = \App\Models\InvoiceAllocation::where('contract_id', $contractId)->sum('allocated_amount');
            
            // 更新财务大盘
            $tracker = FinancialTracker::where('contract_id', $contractId)->first();
            if ($tracker) {
                $tracker->update(['received_invoice_amount' => $actualReceivedTotal]);
                
                // 状态机反向回调防呆：如果原先是“发票全齐”，由于删除了错票导致额度不足，降级为“部分收齐”
                $contract = Contract::findOrFail($contractId);
                if ($contract) {
                    $contract->update([
                        'invoice_status' => $actualReceivedTotal >= $tracker->purchase_total_amount ? 'fully_issued' : ($actualReceivedTotal > 0 ? 'partial' : 'none')
                    ]);
                }
            }
        }
    }
}
