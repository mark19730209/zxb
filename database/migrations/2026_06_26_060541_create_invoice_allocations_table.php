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
        // 1. 改造发票主表：去掉原有的单个 contract_id 限制，让发票独立存在
        Schema::table('purchase_invoices', function (Blueprint $table) {
            // 如果历史数据有 contract_id，先将其改为 nullable，或者新建纯净发票主表
            $table->foreignId('contract_id')->nullable()->change(); 
        });

        // 2. 🎯 核心核心补齐：创建“发票多订单分摊核销表”
        Schema::create('invoice_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_invoice_id')->constrained()->onDelete('cascade'); // 关联发票
            $table->foreignId('contract_id')->constrained()->onDelete('cascade');         // 关联被扣减的合同
            
            // 🎯 本张发票分摊给该具体合同的核销金额（含税价税合计）
            $table->decimal('allocated_amount', 15, 2); 
            
            $table->decimal('tax_exclusive_amount', 15, 2); // 该分摊额对应的不含税基数
            $table->decimal('tax_amount', 15, 2);           // 该分摊额对应的税额
            $table->timestamps();
            
            // 物理联合约束：防止在同一张发票里对同一个合同重复核销
            $table->unique(['purchase_invoice_id', 'contract_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_allocations');
    }
};
