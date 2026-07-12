<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateItemsToCategories extends Command
{
    // 🎯 运行命令：php artisan customs:data-migrate
    protected $signature = 'customs:data-migrate';
    protected $description = '将旧 items 结构中的海关申报大类数据完美平滑割接、洗数至新 categories 表';

    public function handle()
    {
        $this->info('⚡ 启动外贸主数据平滑割接洗数引擎...');

        // 1. 获取 items 旧表中现存的所有历史数据
        $oldItems = DB::table('items')->get();

        if ($oldItems->isEmpty()) {
            $this->warn('提示：旧 items 表中暂无历史数据，无需迁移。');
            return Command::SUCCESS;
        }

        DB::transaction(function () use ($oldItems) {
            foreach ($oldItems as $item) {
                // 🎯 2. 数据清洗去重：检查该 H.S. 编码在 categories 里是否已经由其他同类款式创建过
                $category = DB::table('categories')->where('hs_code', $item->hs_code)->first();

                if (!$category) {
                    // 如果大类不存在，将 items 旧表里的品名、单位、要素串升级克隆写入大类表
                    $categoryId = DB::table('categories')->insertGetId([
                        'hs_code'           => $item->hs_code,
                        'category_name'     => $item->name_cn, // 临时以旧品名作为大类归类名
                        'unit'              => $item->unit ?? 'PCS',
                        'elements_template' => $item->elements_template ?? json_encode([]),
                        'created_at'        => now(),
                        'updated_at'        => now()
                    ]);
                    $this->info("✔ 成功由 H.S. 编码 [{$item->hs_code}] 升级派生出全新的海关大类标签！");
                } else {
                    $categoryId = $category->id;
                }

                // 🎯 3. 核心桥接：反向把生成的大类 ID 指针，灌回给当前 items 款式的 category_id 字段
                DB::table('items')->where('id', $item->id)->update([
                    'category_id' => $categoryId
                ]);
            }
        });

        $this->info('🎉 恭喜！历史数据全量完美割接、转移完成！指针已 100% 绑定咬合。');
        return Command::SUCCESS;
    }
}
