<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\FinancialTracker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ExportAnalyticsController extends Controller
{
    /**
     * 获取全系统涉外合规供应链核心财务统计大盘
     */
    public function index(Request $request)
    {
        // 🎯 设定 Proxy 基础业务假设（2026年外贸统计核心：外币统一折算汇率定为 7.20）
        $exchangeRate = 6.70;

        // 1. 出口统计：所有有效合同（除草稿和作废外）的外币总销售额，并折算人民币
        $activeContractIds = Contract::whereIn('status', ['active', 'shipped', 'completed'])->pluck('id');
        
        $totalExportUSD = ContractItem::whereIn('contract_id', $activeContractIds)->sum('total_amount');
        $totalExportRMB = $totalExportUSD * $exchangeRate;

        // 2. 进货统计：统计国内供应链应付工厂（如桐庐华艺）的含税总采购款、以及财务实际已收票面额
        $totalPurchaseAmount = FinancialTracker::sum('purchase_total_amount');
        $totalReceivedInvoice = FinancialTracker::sum('received_invoice_amount');

        // 3. 退税额统计：统计系统内由商品退税率推演得出的预计可退总额、以及国库实际已到账退税
        $totalEstimatedRefund = FinancialTracker::sum('estimated_refund');
        $totalActualRefund = FinancialTracker::sum('actual_refund_received');

        // 4. 利润统筹：综合外贸毛利公式 = 出口总额(RMB) - 采购总额(含税) + 预计退税额
        $totalGrossProfit = $totalExportRMB - $totalPurchaseAmount + $totalEstimatedRefund;

        // 5. 穿透单据：拉出各合同项下的利润与发票核销明细台账，用于前端穿透透视
        $contractPerformanceList = Contract::with(['financialTracker'])
            ->whereIn('status', ['active', 'shipped', 'completed'])
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($contract) use ($exchangeRate) {
                $usdAmount = $contract->contractItems()->sum('total_amount');
                $rmbAmount = $usdAmount * $exchangeRate;
                
                $purchase = $contract->financialTracker?->purchase_total_amount ?? 0;
                $refund = $contract->financialTracker?->estimated_refund ?? 0;
                
                // 单个订单毛利润
                $contractProfit = $rmbAmount - $purchase + $refund;

                return [
                    'id' => $contract->id,
                    'contract_no' => $contract->contract_no,
                    'status' => $contract->status,
                    'export_amount_usd' => $usdAmount,
                    'export_amount_rmb' => $rmbAmount,
                    'purchase_total_amount' => $purchase,
                    'invoice_status' => $contract->invoice_status,
                    'estimated_refund' => $refund,
                    'actual_refund_received' => $contract->financialTracker?->actual_refund_received ?? 0,
                    'net_profit' => $contractProfit
                ];
            });

        return Inertia::render('Analytics/Index', [
            'metrics' => [
                'total_export_usd' => round($totalExportUSD, 2),
                'total_export_rmb' => round($totalExportRMB, 2),
                'total_purchase_amount' => round($totalPurchaseAmount, 2),
                'total_received_invoice' => round($totalReceivedInvoice, 2),
                'total_estimated_refund' => round($totalEstimatedRefund, 2),
                'total_actual_refund' => round($totalActualRefund, 2),
                'total_gross_profit' => round($totalGrossProfit, 2),
                'invoice_recovery_rate' => $totalPurchaseAmount > 0 ? round(($totalReceivedInvoice / $totalPurchaseAmount) * 100, 1) : 0,
                'refund_recovery_rate' => $totalEstimatedRefund > 0 ? round(($totalActualRefund / $totalEstimatedRefund) * 100, 1) : 0,
            ],
            'performanceList' => $contractPerformanceList
        ]);
    }
}
