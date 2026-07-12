<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SupplierController extends Controller
{
    /**
     * 1. 列表页：展示所有国内供货商，支持搜索
     */
    public function index(Request $request)
    {
        $suppliers = Supplier::query()
            ->when($request->input('search'), function ($query, $search) {
                $query->where('company_name', 'like', "%{$search}%")
                      ->orWhere('tax_id', 'like', "%{$search}%");
            })
            ->orderBy('id', 'desc')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Suppliers/Index', [
            'suppliers' => $suppliers,
            'filters' => $request->only(['search'])
        ]);
    }

    /**
     * 2. 新增页：渲染创建表单
     */
    public function create()
    {
        return Inertia::render('Suppliers/Create');
    }

    /**
     * 3. 提交新增：验证并保存国内供应商
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_name'   => 'required|string|max:255|unique:suppliers,company_name',
            'tax_id'         => 'required|string|size:18|unique:suppliers,tax_id', // 18位统一社会信用代码
            'bank_account'   => 'required|string|max:255', // 开户行及账号
            'contact_person' => 'nullable|string|max:100',
            'company_address'=> 'nullable',
            'company_phone'  => 'nullable',
            'bank_name'      => 'nullable',
            'bank_code'      => 'nullable'
        ], [
            'company_name.unique' => '该供应商名称（抬头）已存在',
            'tax_id.unique'       => '该纳税人识别号已登记过',
            'tax_id.length'       => '纳税人识别号必须为18位社会信用代码',
        ]);

        Supplier::create($validated);

        return redirect()->route('suppliers.index')
            ->with('success', '国内供货商档案创建成功！');
    }

    /**
     * 4. 编辑页：拉取单条数据并渲染修改表单
     */
    public function edit(Supplier $supplier)
    {
        return Inertia::render('Suppliers/Edit', [
            'supplier' => $supplier
        ]);
    }

    /**
     * 5. 提交更新：验证并覆盖旧数据
     */
    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'company_name'   => 'required|string|max:255|unique:suppliers,company_name,' . $supplier->id,
            'tax_id'         => 'required|string|size:18|unique:suppliers,tax_id,' . $supplier->id,
            'bank_account'   => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:100',
            'company_address'=> 'nullable',
            'company_phone'  => 'nullable',
            'bank_name'      => 'nullable',
            'bank_code'      => 'nullable'
        ]);

        $supplier->update($validated);

        return redirect()->route('suppliers.index')
            ->with('success', '供货商档案更新成功！');
    }

    /**
     * 6. 删除动作：移除该供应商档案
     */
    public function destroy(Supplier $supplier)
    {
        // 健壮性合规校验：如果该工厂已经开过发票，则不允许直接删除，防止断链
        if ($supplier->purchaseInvoices()->exists()) {
            return redirect()->back()->with('error', '无法删除该供应商：系统内存在该工厂关联的进货发票挂账流水。');
        }

        $supplier->delete();

        return redirect()->route('suppliers.index')
            ->with('success', '供货商档案已安全移除。');
    }
}
