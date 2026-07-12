<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancialTracker extends Model
{
    use HasFactory;

    /**
     * 🎯 显式允许外贸财务与退税追踪大盘在制单工作台更新时批量赋值写入
     */
    protected $fillable = [
        'contract_id',
        'purchase_total_amount',    // 国内供应链采购应开专票总额
        'received_invoice_amount',   // 各家工厂实际寄回并认证的总额
        'estimated_refund',          // 智能推演预算出的预计可退税额
        'actual_refund_received',    // 国家税务局实际到账的出口退税款
        'refund_apply_date',         // 向税局发起退税申报的日期
        'refund_receive_date',       // 退税款正式落袋的日期
    ];

    /**
     * 正向反向关联出口合同主表
     */
    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }
}
