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
        Schema::table('exporters', function (Blueprint $table) {
            $table->string('company_address')->after('company_name_en'); // 出口商英文地址（用于Invoice/提单抬头）
            $table->string('contact_tel')->after('company_address');     // 联系电话

            $table->string('bank_name')->after('customs_code');    // 开户行全称
            $table->string('bank_account')->after('bank_name');    // 银行账号
            $table->string('swift_code')->after('bank_account');   // SWIFT CODE
            $table->string('bank_address')->after('swift_code');   // 开户行地址
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exporters', function (Blueprint $table) {
            $table->dropColumn([
                'company_address',
                'contact_tel',
                'bank_name',
                'bank_account',
                'swift_code',
                'bank_address',
            ]);
        });
    }
};