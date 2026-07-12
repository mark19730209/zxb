<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractItem extends Model
{
    use HasFactory;

    /**
     * 🎯 显式允许外贸单据明细在制单工作台进行批量赋值写入
     */
    protected $fillable = [
        'contract_id',
        'item_id',
        'unit',
        'supplier_id',       // 步骤② 绑定的国内工厂ID
        'quantity',          // 签约数量
        'unit_price',        // 外销单价
        'purchase_price_snapshot',
        'total_amount',      // 外销总价
        'packages',          // 件数
        'package_type',      // 包装种类
        'net_weight',        // 净重
        'gross_weight',      // 毛重
        'volume',            // 体积
        'declared_elements', // 拼接好的海关“|”要素串
    ];

    /**
     * 正向关联基础商品档案
     */
    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * 正向关联国内供货商
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}
