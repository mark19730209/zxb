<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Exporter extends Model
{
    protected $fillable = [
        'company_name_cn',
        'company_name_en',
        'company_address',
        'contact_tel',
        'tax_id',
        'customs_code',
        'bank_name',
        'bank_account',
        'swift_code',
        'bank_address',
    ];

    // 一个出口商主体可以签订多份合同
    public function contracts() {
        return $this->hasMany(Contract::class);
    }
}