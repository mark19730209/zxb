<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\FinancialTracker;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * 🧠 经营大脑：高度聚合全网资产流，追加工厂退税权重排行
     */
    public function index2()
    {
        $exchangeRate = 6.7000; // 统一大盘基础折算proxy汇率

        // 1. 出口外销统计（排除草稿和废单）
        $activeContractIds = Contract::whereIn('status', ['active', 'shipped', 'completed'])->pluck('id');
        $totalExportUSD = ContractItem::whereIn('contract_id', $activeContractIds)->sum('total_amount');
        $totalExportRMB = $totalExportUSD * $exchangeRate;

        // 2. 进项采购额与发票回收额
        $totalPurchaseAmount = FinancialTracker::sum('purchase_total_amount');
        $totalReceivedInvoice = FinancialTracker::sum('received_invoice_amount');

        // 3. 退税留存与实际到账
        $totalEstimatedRefund = FinancialTracker::sum('estimated_refund');
        $totalActualRefund = FinancialTracker::sum('actual_refund_received');

        // 4. 业财税统筹毛利润核心公式
        $totalGrossProfit = $totalExportRMB - $totalPurchaseAmount + $totalEstimatedRefund;

        // 5. 风控预警防线件数清点
        $pendingInvoiceCount = Contract::whereIn('invoice_status', ['none', 'partial'])->whereIn('status', ['active', 'shipped'])->count();
        $pendingRefundCount = Contract::whereIn('refund_status', ['none', 'processing'])->whereIn('status', ['shipped'])->count();

        // 6. 获取最近 5 笔流转中的核心业务流水
        $recentContracts = Contract::orderBy('id', 'desc')->take(5)->get(['id', 'contract_no', 'contract_date', 'status', 'invoice_status', 'refund_status']);

        // =====================================================================
        // 🎯 核心高能新加：跨表穿透算力——推演各工厂累计可退税金额前 5 名大盘排行
        // =====================================================================
        // 通过合同明细表，抓出所有的真实实际下单采购总额，再结合商品表的退税率算齐每家工厂的退税总重
        $supplierRefundRanking = ContractItem::query()
            ->join('items', 'contract_items.item_id', '=', 'items.id')
            ->join('suppliers', 'contract_items.supplier_id', '=', 'suppliers.id')
            ->select(
                'suppliers.id',
                'suppliers.company_name',
                // 核心计算：SUM(数量 * 实际下单进价) = 厂家的含税采购总额
                DB::raw('SUM(contract_items.quantity * contract_items.purchase_price_snapshot) as total_purchase'),
                // 核心计算：SUM((数量 * 实际下单进价) / 1.13 * 退税率 / 100) = 厂家总贡献退税额
                DB::raw('ROUND(SUM((contract_items.quantity * contract_items.purchase_price_snapshot) / 1.13 * (items.tax_refund_rate / 100)), 2) as computed_refund')
            )
            ->groupBy('suppliers.id', 'suppliers.company_name')
            ->orderBy('computed_refund', 'desc')
            ->take(5) // 🎯 严格锁定前 5 名
            ->get()
            ->map(function ($rank) use ($totalEstimatedRefund) {
                // 🎯 算力配平：计算该生产商在当前全系统可退税资产池里的【核心资金占比 (%)】
                $rank->share_rate = $totalEstimatedRefund > 0 ? round(($rank->computed_refund / $totalEstimatedRefund) * 100, 1) : 0;
                return $rank;
            });

        return Inertia::render('Dashboard', [
            'metrics' => [
                'total_export_usd'                => round($totalExportUSD, 2),
                'total_export_rmb'                => round($totalExportRMB, 2),
                'total_purchase_amount'           => round($totalPurchaseAmount, 2),
                'total_received_invoice'          => round($totalReceivedInvoice, 2),
                'total_estimated_refund'          => round($totalEstimatedRefund, 2),
                'total_actual_refund'             => round($totalActualRefund, 2),
                'total_gross_profit'              => round($totalGrossProfit, 2),
                'pending_invoice_contracts_count' => $pendingInvoiceCount,
                'pending_refund_contracts_count'  => $pendingRefundCount,
            ],
            'recentContracts'       => $recentContracts,
            'supplierRefundRanking' => $supplierRefundRanking // 🎯 吐给前端的强类型排行流水阵列
        ]);
    }

    // app/Http/Controllers/DashboardController.php

    public function index()
    {
        $exchangeRate = 6.7000; // 统一大盘基础折算proxy汇率

        // 1. 基础全局指标计算（保持原有大盘金额算力不动...）
        $activeContractIds = Contract::whereIn('status', ['active', 'shipped', 'completed'])->pluck('id');
        $totalExportUSD = ContractItem::whereIn('contract_id', $activeContractIds)->sum('total_amount');
        $totalExportRMB = $totalExportUSD * $exchangeRate;

        $totalPurchaseAmount = FinancialTracker::sum('purchase_total_amount');
        $totalReceivedInvoice = FinancialTracker::sum('received_invoice_amount');

        // 🎯 注意：这里的全网实际到账退税总额作为基准资产池，用来算百分比占比
        $totalActualRefund = FinancialTracker::sum('actual_refund_received');
        $totalEstimatedRefund = FinancialTracker::sum('estimated_refund');

        $totalGrossProfit = $totalExportRMB - $totalPurchaseAmount + $totalEstimatedRefund;

        $pendingInvoiceCount = Contract::whereIn('invoice_status', ['none', 'partial'])->whereIn('status', ['active', 'shipped'])->count();
        $pendingRefundCount = Contract::whereIn('refund_status', ['none', 'processing'])->whereIn('status', ['shipped'])->count();
        $recentContracts = Contract::orderBy('id', 'desc')->take(5)->get(['id', 'contract_no', 'contract_date', 'status', 'invoice_status', 'refund_status']);

// app/Http/Controllers/DashboardController.php -> index() 内部排行榜降维大合拢

        // =====================================================================
        // 🎯 【终极降维自愈修复】直接从发票主表抓取工厂指纹，100% 召回全网 7 个工厂！
        // =====================================================================
        // 核心修正：彻底抛弃从分摊表切入的思路，改由直接从【发票主表】启动 Group By！
        // 这样管你是走快捷单录入、还是大额跨期分摊，只要发票在库里，对应工厂的金额就绝对无处可逃！
        $supplierRefundRanking = \App\Models\PurchaseInvoice::query()
            ->join('suppliers', 'purchase_invoices.supplier_id', '=', 'suppliers.id')
            ->select(
                'suppliers.id',
                'suppliers.company_name',
                // 💰 指标 1：该工厂在发票主表里沉淀的所有 20位专用发票含税总面额
                // 彻底抛弃 DISTINCT 拦截，直接累加主票面额，速度极快且账目绝对真实
                DB::raw('ROUND(SUM(purchase_invoices.total_amount), 2) as total_purchase'),
                
                // 💰 指标 2：采用全网通用的 13.00% 服装面料平均退税率进行大盘合规推演
                // 公式：(发票面额 / 1.13) * 13% 
                DB::raw('ROUND(SUM(purchase_invoices.total_amount / 1.13 * 0.13), 2) as computed_refund')
            )
            ->groupBy('suppliers.id', 'suppliers.company_name')
            ->orderBy('computed_refund', 'desc')
            ->take(8) // 严格锁定前 8 名
            ->get()
            ->map(function ($rank) use ($totalEstimatedRefund) {
                // 🎯 算力配平：计算该生产商在当前全系统总预计可退税池子里的【真实核心资金比 (%)】
                $rank->share_rate = $totalEstimatedRefund > 0 ? round(($rank->computed_refund / $totalEstimatedRefund) * 100, 1) : 0;
                return $rank;
            });


// dd($supplierRefundRanking);
        return Inertia::render('Dashboard', [
            'metrics' => [
                'total_export_usd'                => round($totalExportUSD, 2),
                'total_export_rmb'                => round($totalExportRMB, 2),
                'total_purchase_amount'           => round($totalPurchaseAmount, 2),
                'total_received_invoice'          => round($totalReceivedInvoice, 2),
                'total_estimated_refund'          => round($totalEstimatedRefund, 2),
                'total_actual_refund'             => round($totalActualRefund, 2),
                'total_gross_profit'              => round($totalGrossProfit, 2),
                'pending_invoice_contracts_count' => $pendingInvoiceCount,
                'pending_refund_contracts_count'  => $pendingRefundCount,
            ],
            'recentContracts'       => $recentContracts,
            'supplierRefundRanking' => $supplierRefundRanking // 🎯 吐给前端真实的实票排行流水
        ]);
    }

}
