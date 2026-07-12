<?php

// app/Models/Contract.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contract extends Model
{
    protected $guarded = [];

    // 关联合同明细（含商品、重量、包装）
    public function contractItems():HasMany {
        return $this->hasMany(ContractItem::class);
    }

    // 关联财务与退税追踪
    public function financialTracker() {
        return $this->hasOne(FinancialTracker::class);
    }
    public function exporter() {
        return $this->belongsTo(Exporter::class, 'exporter_id');
    }

}
