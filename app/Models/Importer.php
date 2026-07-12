<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Importer extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_name_en',
        'company_address_en',
        'country_code',
        'contact_email',
        'tax_no'
    ];

    // 一个进口商可以签订多份出口合同
    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }
}
