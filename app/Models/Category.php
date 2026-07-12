<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['hs_code', 'category_name', 'unit', 'elements_template'];
    protected $casts = ['elements_template' => 'array'];

    // 1个海关品类下可衍生出无限个具体大货款式(SKU)
    public function items() {
        return $this->hasMany(Item::class);
    }
}
