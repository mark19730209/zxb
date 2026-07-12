<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. 国内供应商/商户基本信息
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('company_name')->unique(); // 供应商名称（开票抬头）
            $table->string('tax_id')->unique();       // 纳税人识别号
            $table->string('company_address')->nullable();   // 企业地址
            $table->string('company_phone')->nullable(); // 企业电话
            $table->string('bank_account');           // 开户行及账号
            $table->string('bank_name')->nullable();   // 独立开户行名字
            $table->string('bank_code', 12)->nullable();  // 12位大额支付行号（联行号）
            $table->string('contact_person')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
