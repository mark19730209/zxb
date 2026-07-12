<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 第二阶段：彻底切除旧冗余列，并为过渡外键追加严格的 NOT NULL 约束
     */
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            // 🎯 1. 将之前设为 nullable 的过渡列升级为严格的非空硬约束
            $table->foreignId('category_id')->nullable(false)->change();

            // 🎯 2. 安全切除旧冗余列，还数据库一张纯净、解耦的数据网格
            $columnsToDrop = ['hs_code', 'unit', 'elements_template'];
            foreach ($columnsToDrop as $col) {
                if (Schema::hasColumn('items', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }

    public function down(): void
    {
        // 生产环境回滚通常不建议恢复旧数据列，此处可保留为空
    }
};
