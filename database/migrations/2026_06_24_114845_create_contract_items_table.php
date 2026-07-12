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
        // database/migrations/xxxx_xx_xx_create_contract_items_table.php
        Schema::create('contract_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->onDelete('cascade');
            $table->foreignId('item_id');
            $table->foreignId('supplier_id')->nullable();  
            $table->integer('quantity');               // 签约数量
            $table->decimal('unit_price', 15, 2);      // 外币外销单价
            $table->decimal('total_amount', 15, 2);    // 外币总价
            
            // 包装与重量数据（直接影响 Packing List 和报关）
            $table->integer('packages');               // 件数
            $table->string('package_type');            // 包装种类 (如: Cartons)
            $table->decimal('net_weight', 10, 2);      // 净重 (KG)
            $table->decimal('gross_weight', 10, 2);    // 毛重 (KG)
            $table->decimal('volume', 10, 3);          // 体积 (CBM)
            
            // 申报要素数据存根
            $table->text('declared_elements')->nullable(); // 该批次具体的申报要素拼接文本
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_items');
    }
};
