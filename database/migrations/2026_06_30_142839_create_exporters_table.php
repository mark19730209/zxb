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
        Schema::create('exporters', function (Blueprint $table) {
            $table->id();
            $table->string('company_name_cn')->unique(); // 报关用中文全称（如：青岛张秀彬国际贸易有限公司）
            $table->string('company_name_en')->unique(); // Invoice用英文全称
            $table->string('tax_id', 18)->unique();       // 统一社会信用代码 (税号)
            $table->string('customs_code', 10)->nullable(); // 10位海关编码
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exporters');
    }
};
