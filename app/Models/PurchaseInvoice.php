<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseInvoice extends Model
{
    use HasFactory;

    /**
     * 🎯 显式允许进货发票明细流水的字段进行批量赋值写入
     */
    protected $fillable = [
        'contract_id',           // 👈 核心补齐：必须允许绑定核心合同号
        'supplier_id',           // 绑定国内工厂（如桐庐华艺）
        'invoice_code',          // 发票代码
        'invoice_no',            // 发票号码
        'issue_date',            // 开票日期
        'tax_exclusive_amount',   // 逆推的不含税金额
        'tax_amount',            // 逆推的税额
        'total_amount',          // 价税合计含税总额
        'status',                // 发票状态：verified / audit_failed
        'file_path'
    ];

    /**
     * 正向关联对应的国内供货工厂
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * 反向关联主外贸合同
     */
    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    // 一张大额发票可以分摊核销多个出口合同
    public function allocations() {
        return $this->hasMany(InvoiceAllocation::class);
    }

    /**
     * 🎯 核心补齐：当前发票所归属、冲抵核销的特定国内采购合同
     */
    public function purchaseContract()
    {
        return $this->belongsTo(PurchaseContract::class, 'purchase_contract_id');
    }
}
