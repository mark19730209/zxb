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
        // database/migrations/2026_07_06_000001_create_purchase_contracts_table.php
        Schema::create('purchase_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('cascade'); // 关联供货工厂
            $table->string('purchase_contract_no', 50)->unique()->comment('国内采购合同号，如：CG-2026-001');
            $table->date('signing_date')->comment('国内签约日期');
            $table->decimal('total_rmb_amount', 15, 2)->default(0.00)->comment('采购合同含税总金额(RMB)');
            $table->enum('invoice_status', ['none', 'partial', 'fully_issued'])->default('none')->comment('该工厂专票催收状态');
            $table->text('delivery_terms')->nullable()->comment('工厂交货与包装条款');
            $table->timestamps();
        });

        // database/migrations/2026_07_06_000002_create_purchase_contract_items_table.php
        Schema::create('purchase_contract_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_contract_id')->constrained('purchase_contracts')->onDelete('cascade');
            $table->foreignId('item_id')->constrained('items')->onDelete('cascade'); // 级联具体款式 SKU
            $table->decimal('quantity', 15, 4)->comment('含小数的高精度采购称重数量/计件数量');
            $table->decimal('purchase_price', 15, 4)->comment('跟工厂定死的本次实际下单人民币含税采购单价');
            $table->decimal('total_amount', 15, 2)->comment('单项采购总额(Quantity * Purchase_Price)');
            $table->timestamps();
        });

        // database/migrations/2026_07_06_000003_add_purchase_contract_id_to_purchase_invoices_table.php
        Schema::table('purchase_invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_invoices', 'purchase_contract_id')) {
                // 🎯 核心升级：让 20 位专票能笔直扣减特定工厂的采购合同
                $table->foreignId('purchase_contract_id')->nullable()->after('supplier_id')->constrained('purchase_contracts')->onDelete('set null');
            }
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_contracts');
    }
};
