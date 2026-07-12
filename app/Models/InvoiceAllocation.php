<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_invoice_id',
        'contract_id',
        'allocated_amount',
        'tax_exclusive_amount',
        'tax_amount',
    ];

    /**
     * 🎯 核心修正点：补齐正向关联发票主表的方法
     * 方法名必须为驼峰法的 purchaseInvoice，与 with(['purchaseInvoice']) 完全对应
     */
    public function purchaseInvoice()
    {
        return $this->belongsTo(PurchaseInvoice::class, 'purchase_invoice_id');
    }

    /**
     * 正向关联出口合同
     */
    public function contract()
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }
}
