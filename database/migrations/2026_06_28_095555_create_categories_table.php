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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('hs_code', 10)->unique()->comment('10位中国海关标准编码');
            $table->string('category_name', 100)->comment('品类通关名称，如：针织帽子');
            $table->string('unit', 20)->comment('海关法定第一计量单位，如：千克/件');
            $table->json('elements_template')->comment('海关依法必填的规范申报要素标签数组');
            $table->timestamps();
        });
        // 2. 🎯 为 items 表平滑追加 category_id 过渡外键
        Schema::table('items', function (Blueprint $table) {
            if (!Schema::hasColumn('items', 'category_id')) {
                // 必须先设为 nullable() 才能通过有数据的旧表结构
                $table->foreignId('category_id')->nullable()->after('id')->constrained('categories')->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
        });
        Schema::dropIfExists('categories');
    }
};
