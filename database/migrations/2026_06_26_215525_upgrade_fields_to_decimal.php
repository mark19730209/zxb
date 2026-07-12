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
        // 1. 升级合同货物明细表：数量和件数（部分称重货物件数也可能允许小数，数量必须允许）
        Schema::table('contract_items', function (Blueprint $table) {
            // decimal(15, 4) 代表总长15位，小数点后保留4位，完美支持大宗称重
            $table->decimal('quantity', 15, 4)->change(); 
            $table->decimal('net_weight', 15, 3)->change(); // 净重保留3位小数（克级精度）
            $table->decimal('gross_weight', 15, 3)->change(); // 毛重保留3位小数
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contract_items', function (Blueprint $table) {
        //
        });
    }
};
