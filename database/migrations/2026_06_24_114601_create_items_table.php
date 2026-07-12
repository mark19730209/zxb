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
        // database/migrations/xxxx_xx_xx_create_items_table.php
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            // $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->string('sku')->unique();          // 自定义货号
            $table->string('name_cn');                // 中文品名（报关用）
            $table->string('name_en');                // 英文品名（Invoice用）
            $table->string('hs_code', 10);            // H.S. 编码 (10位)
            $table->text('elements_template');        // 申报要素模板/提示
            $table->string('unit');                   // 法定计量单位（如：千克/个）
            $table->decimal('tax_refund_rate', 5, 2); // 退税率（例如：13.00）
            $table->decimal('purchase_price', 15, 2); // 默认含税进货价
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
