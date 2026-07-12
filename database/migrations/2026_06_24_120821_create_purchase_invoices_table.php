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
        // 2. 实际收到的增值税专用发票登记表
        Schema::create('purchase_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->onDelete('cascade');
            $table->foreignId('supplier_id')->constrained();
            $table->string('invoice_no',20);            // 发票号码
            $table->date('issue_date');              // 开票日期
            $table->decimal('tax_exclusive_amount', 15, 2); // 不含税金额（退税计算基数）
            $table->decimal('tax_amount', 15, 2);           // 税额
            $table->decimal('total_amount', 15, 2);         // 价税合计（含税总额）
            $table->string('status')->default('verified');   // 状态：verified(已认证), audit_failed(认证失败)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_invoices');
    }
};
