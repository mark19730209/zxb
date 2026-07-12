<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $fillable = ['category_id', 'sku', 'name_cn', 'name_en', 'tax_refund_rate', 'purchase_price', 'image_path', 'is_actived'];
    protected $casts = [
        'tax_refund_rate' => 'decimal:2', // 💡 修正拼写，加上精度
        'purchase_price' => 'decimal:2',   // 💡 修正拼写，加上精度
    ];
    // 正向关联所属的海关申报大类
    public function category() {
        return $this->belongsTo(Category::class);
    }
}
