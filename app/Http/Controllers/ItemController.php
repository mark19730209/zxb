<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\ItemRequest;
use Illuminate\Support\Arr;

class ItemController extends Controller
{
    /**
     * 1. 商品列表页：支持按品名、SKU、H.S.编码模糊搜索
     */
    public function index(Request $request)
    {
        // 🎯 核心高能：多表深度联合预加载，确保物理冗余列删除后，单位与 H.S. 编码平滑穿透
        $items = Item::query()
            ->with(['category'])
            ->when($request->input('search'), function ($query, $search) {
                $query->where('sku', 'like', "%{$search}%")
                    ->orWhere('name_cn', 'like', "%{$search}%")
                    ->orWhereHas('category', function($q) use ($search) {
                        $q->where('category_name', 'like', "%{$search}%")
                            ->orWhere('hs_code', 'like', "%{$search}%");
                    });
            })
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        return \Inertia\Inertia::render('Items/Index', [
            'items' => $items,
            'filters' => $request->only(['search'])
        ]);
    }

    /**
     * 2. 新增页：渲染创建商品表单
     */
    public function create()
    {
        $categories = Category::select('id', 'category_name', 'hs_code', 'unit', 'elements_template')
            ->get()
            ->map(function ($category) {
                // 🎯 在这里精准执行解码清洗
                if (is_string($category->elements_template)) {
                    $category->elements_template = json_decode($category->elements_template, true) ?: [];
                }
                return $category;
            });

        return Inertia::render('Items/Create', [
            'categories' => $categories
        ]);
    }

    /**
     * 3. 提交新增：验证并保存外贸商品属性
     */
    public function store(ItemRequest $request)
    {
        $validated = $request->validated();
        
        $imagePath = null;
        if ($request->hasFile('item_image')) {
            // 命名规范：SKU_大货号.后缀
            $fileName = 'SKU_' . $validated['sku'] . '.' . $request->file('item_image')->getClientOriginalExtension();
            $imagePath = $request->file('item_image')->storeAs('products', $fileName, 'public');
        }
        $validated['image_path'] = $imagePath;
        Arr::forget($validated, 'item_image'); 
        Item::create($validated);

        return redirect()->route('items.index')
            ->with('success', '外贸商品档案及海关合规模板建立成功！');
    }

    /**
     * 4. 编辑页：拉取单条商品数据，并将其中的元素模板还原为数组传给前端
     */
    public function edit(Item $item)
    {
        $categories = Category::select('id', 'category_name', 'hs_code', 'unit', 'elements_template')
            ->get()
            ->map(function ($category) {
                // 🎯 在这里精准执行解码清洗
                if (is_string($category->elements_template)) {
                    $category->elements_template = json_decode($category->elements_template, true) ?: [];
                }
                return $category;
            });
        return Inertia::render('Items/Edit', [
            'item'       => $item,
            // 🎯 补齐前端下拉大盘所需的数据流供给源头
            'categories' => $categories
        ]);
    }

    /**
     * 5. 提交更新：验证并覆盖商品旧属性
     */
    public function update(ItemRequest $request, Item $item)
    {
        $validated = $request->validated();

        $imagePath = null;
        if ($request->hasFile('item_image')) {
            // 命名规范：SKU_大货号.后缀
            $fileName = 'SKU_' . $validated['sku'] . '.' . $request->file('item_image')->getClientOriginalExtension();
            $imagePath = $request->file('item_image')->storeAs('products', $fileName, 'public');
            $validated['image_path'] = $imagePath;
        }
        Arr::forget($validated, 'item_image'); 
        $item->update($validated);

        return redirect()->route('items.index')
            ->with('success', '外贸商品档案更新成功！');
    }

    /**
     * 6. 删除动作：移除该商品档案
     */
    public function destroy(Item $item)
    {
        // 健壮性防呆校验：如果该商品已经在任意一份出口合同明细中被使用了，禁止删除！
        // 必须优先保证单据历史和退税核销数据的完整性
        if (DB::table('contract_items')->where('item_id', $item->id)->exists()) {
            return redirect()->back()->with('error', '禁止删除该商品：已有业务合同或报关单据关联了此商品，删除将导致历史退税账目断链！');
        }

        $item->delete();

        return redirect()->route('items.index')
            ->with('success', '外贸商品档案已安全移除。');
    }

    /**
     * 🎯 核心补齐：一键快捷切换商品款式的上架启用状态 (逻辑封存)
     */
    public function toggleActive($id)
    {
        $item = Item::findOrFail($id);
        
        // 状态位取反跃迁
        $item->update([
            'is_actived' => !$item->is_actived
        ]);

        $statusText = $item->is_actived ? '重新激活上架' : '逻辑下架封存';
        
        // 笔直投递给 Sonner 闪存雷达，右上角秒弹绿牌喜报
        return redirect()->back()->with('success', "✔️ 款式 [{$item->sku}] 已成功实施{$statusText}！");
    }

}
