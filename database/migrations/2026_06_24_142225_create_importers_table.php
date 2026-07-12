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
        // database/migrations/xxxx_xx_xx_create_importers_table.php
        Schema::create('importers', function (Blueprint $table) {
            $table->id();
            $table->string('company_name_en')->unique(); // 境外买方英文全称（发票抬头）
            $table->text('company_address_en');          // 境外法定注册地址（Invoice、装箱单必填）
            $table->string('country_code', 3);           // 贸易国别代码（报关单必填，如：USA, DEU）
            $table->string('contact_email')->nullable(); // 联系邮箱
            $table->string('tax_no')->nullable();        // 境外税号（部分国家清关强制要求）
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('importers');
    }
};
