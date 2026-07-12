<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Requests\CategoryRequest;
// use Illuminate\Http\Response;
use Inertia\Response;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    /**
     * 1. 商品列表页：支持按品名、SKU、H.S.编码模糊搜索
     */
    public function index(Request $request)
    {
        $categories = Category::query()
            ->when($request->input('search'), function ($query, $search) {
                $query->where('category_name', 'like', "%{$search}%")
                      ->orWhere('hs_code', 'like', "%{$search}%");
            })
            ->orderBy('id', 'desc')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Categories/Index', [
            'categories' => $categories,
            'filters' => $request->only(['search'])
        ]);
    }

    /**
     * 2. 新增页：渲染创建商品表单
     */
    public function create()
    {
        return Inertia::render('Categories/Create');
    }

    /**
     * 3. 提交新增：验证并保存外贸商品属性
     */
    public function store(CategoryRequest $request)
    {
        $validated = $request->validated();
        // 将前端填写的申报要素数组转化为JSON存储
        // 存储格式示例：["品牌","型号","材质","成分"]
        $validated['elements_template'] = json_encode($validated['elements_template'], JSON_UNESCAPED_UNICODE);

        Category::create($validated);

        return redirect()->route('categories.index')
            ->with('success', '海关 H.S. 编码添加成功！');
    }

    /**
     * 4. 编辑页：拉取单条商品数据，并将其中的元素模板还原为数组传给前端
     */
    public function edit(Category $category): Response
    {
        // 如果数据库存的是 JSON 字符串，则在向前端投递前解码为 Vue 可读的数组
        if (is_string($category->elements_template)) {
            $category->elements_template = json_decode($category->elements_template, true) ?: [];
        }

        return Inertia::render('Categories/Edit', [
            'category' => $category
        ]);
    }

    /**
     * 5. 提交更新：验证并覆盖商品旧属性
     */
    public function update(CategoryRequest $request, Category $category)
    {
        $validated = $request->validated();

        $validated['elements_template'] = json_encode($validated['elements_template'], JSON_UNESCAPED_UNICODE);

        $category->update($validated);

        return redirect()->route('categories.index')
            ->with('success', '海关 H.S. 编码档案更新成功！');
    }

    /**
     * 6. 删除动作：移除该商品档案
     */
    /**
     * 🎯 彻底切换至 Ziggy + Inertia SPA 范式的品类卸载销毁动作
     */
    public function destroy(Category $category)
    {
        // 🛡️ 1. 第一道防线：强力检索 items（款式表）中是否有长尾货号咬合绑定了该 H.S. 大类
        if (DB::table('items')->where('category_id', $category->id)->exists()) {
            // 🎯 笔直触发 Sonner 红牌警告：通知前端母版雷达，原地弹出 5 秒长效拦截气泡
            return redirect()->back()->with('error', '合规风控拦截：禁止删除该海关 H.S. 编码！该大类下已有关联的具体大货商品 SKU，强行删除将导致历史进销项勾稽与退税账目彻底断链！');
        }

        // 🛡️ 2. 第二道防线：级联拦截合同明细（ContractItem）
        // if (DB::table('contract_items')->where('item_id', $category->id)->exists()) {
        //     return redirect()->back()->with('error', '合规风控拦截：禁止删除！已有生效的业务合同或报关单据包含了该品类。');
        // }

        // 3. 核心执行：从物理层安全卸载
        $category->delete();

        // 4. 🎯 核心修正：使用 Inertia 官方推荐的 toRoute 或 back 重定向，确保单页应用无缝重载视图
        return redirect()->back()
            ->with('success', '✔️ 成功：海关 H.S. 编码及要素申报模板档案已安全移除。');
    }

}