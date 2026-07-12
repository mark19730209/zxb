<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseContractItem extends Model
{
    use HasFactory;

    // 🎯 核心防注入：严格配置明细行行项级批量写入白名单
    protected $fillable = [
        'purchase_contract_id',
        'item_id',
        'quantity',
        'purchase_price',
        'total_amount'
    ];

    /**
     * 反向关联：所属的国内采购合同主存根
     */
    public function purchaseContract()
    {
        return $this->belongsTo(PurchaseContract::class, 'purchase_contract_id');
    }

    /**
     * 正向级联：当前采购明细行对应的具体大货款式 SKU 基础档案
     */
    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
}
