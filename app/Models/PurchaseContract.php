<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseContract extends Model
{
    use HasFactory;

    // 🎯 核心防注入：严格配置白名单，允许安全批量入账更新
    protected $fillable = [
        'supplier_id',
        'purchase_contract_no',
        'signing_date',
        'total_rmb_amount',
        'invoice_status',
        'pdf_path', // 🎯 记得加上它
        'delivery_terms'
    ];

    /**
     * 正向关联：该采购合同所属的国内供货工厂 (如: 桐庐华艺)
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    /**
     * 1对N级联：该采购合同下面并入的所有大货具体款式 SKU 货物明细
     */
    public function purchaseContractItems()
    {
        return $this->hasMany(PurchaseContractItem::class, 'purchase_contract_id');
    }

    /**
     * 1对N级联：针对该特定人民币采购合同，财务实际寄来并完成勾稽核销的所有 20位 进项专用发票
     */
    public function invoices()
    {
        return $this->hasMany(PurchaseInvoice::class, 'purchase_contract_id');
    }
}
