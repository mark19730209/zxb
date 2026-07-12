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
        // database/migrations/xxxx_xx_xx_create_contracts_table.php
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_no')->unique();   // 合同号 (如: CT20260601)
            $table->foreignId('exporter_id');          // 出口商ID (关联公司表)
            $table->foreignId('importer_id');          // 进货商/境外进口商ID
            $table->date('contract_date');             // 签约日期
            $table->string('currency', 3)->default('USD'); // 币种
            $table->string('incoterms', 10);           // 贸易术语 (FOB, CIF, EXW)
            $table->string('payment_terms');           // 付款方式 (如: T/T 30% deposit)
            
            // 业务生命周期状态机
            $table->enum('status', ['draft', 'active', 'shipped', 'completed', 'cancelled'])->default('draft');
            $table->enum('refund_status', ['none', 'processing', 'received'])->default('none'); // 退税状态
            $table->enum('invoice_status', ['none', 'partial', 'fully_issued'])->default('none'); // 进货发票状态
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
