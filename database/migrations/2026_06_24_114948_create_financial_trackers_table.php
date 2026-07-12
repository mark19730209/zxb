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
        // database/migrations/xxxx_xx_xx_create_financial_trackers_table.php
        Schema::create('financial_trackers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained();
            
            // 进货发票部分 (国内供应商开给出口商的增值税专用发票)
            $table->decimal('purchase_total_amount', 15, 2)->default(0); // 应收国内进货发票总额
            $table->decimal('received_invoice_amount', 15, 2)->default(0); // 已收发票总额
            
            // 退税部分
            $table->decimal('estimated_refund', 15, 2)->default(0);  // 预计退税金额 = 进货不含税价 * 退税率
            $table->decimal('actual_refund_received', 15, 2)->default(0); // 实际到账退税
            $table->date('refund_apply_date')->nullable();            // 申报退税日期
            $table->date('refund_receive_date')->nullable();          // 退税到账日期
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_trackers');
    }
};
