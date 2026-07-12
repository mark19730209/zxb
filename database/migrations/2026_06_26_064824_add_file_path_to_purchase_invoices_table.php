<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table) {
            // 🎯 核心补齐：在唯一发票号码后面追加附件凭证存储路径
            $table->string('file_path', 500)->nullable()->after('invoice_no');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->dropColumn('file_path');
        });
    }
};
