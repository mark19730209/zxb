<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_contracts', function (Blueprint $table) {
            // 🎯 在工厂催收状态后面追加可为空的 PDF 物理存储相对路径字段
            $table->string('pdf_path', 255)
                  ->nullable()
                  ->after('invoice_status')
                  ->comment('高保真横向合同 PDF 服务端归档相对路径');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_contracts', function (Blueprint $table) {
            $table->dropColumn('pdf_path');
        });
    }
};
