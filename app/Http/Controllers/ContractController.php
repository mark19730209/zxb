<?php

// app/Http/Controllers/ContractController.php
namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Importer;
use App\Models\Supplier;
use App\Models\Exporter;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ContractController extends Controller
{
    /**
     * 1. 合同主台账：多表联查及财务勾稽大盘综合展示
     */
    public function index(Request $request)
    {
        // 🎯 核心高能：多表深度级联预加载，一次性灌入全套大货明细
        $contracts = Contract::query()
            ->with([
                'financialTracker',
                'contractItems.item.category',     // 穿透拉出商品基础档案 (sku, 品名)
                'contractItems.supplier'  // 穿透拉出绑定的国内供货工厂 (如桐庐华艺)
            ])
            ->when($request->input('search'), function ($query, $search) {
                $query->where('contract_no', 'like', "%{$search}%");
            })
            ->orderBy('id', 'desc')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Contracts/Index', [
            'contracts' => $contracts,
            'filters' => $request->only(['search'])
        ]);
    }

    /**
     * 2. 新增合同页：需要拉取国外进口商列表作为表单下拉数据
     */
    public function create()
    {
        return Inertia::render('Contracts/Create', [
            'importers' => Importer::select('id', 'company_name_en', 'country_code')->get(),
            'exporters' => Exporter::select('id', 'company_name_cn')->get() 
        ]);
    }

    /**
     * 3. 保存新合同（严格指定 fillable 字段并初始化状态）
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'contract_no'    => 'required|string|unique:contracts,contract_no',
            'exporter_id'    => 'required|integer', // 通常固定为本公司ID
            'importer_id'    => 'required|exists:importers,id',
            'contract_date'  => 'required|date',
            'currency'       => 'required|string|max:3',
            'incoterms'      => 'required|string|max:10', // FOB, CIF, EXW 等
            'payment_terms'  => 'required|string',
                // 🎯 核心补齐：三大涉外物流核心参数硬约束
            'transport_mode'      => 'required|string|max:10',
            'port_of_loading'     => 'required|string|max:50',
            'port_of_destination' => 'required|string|max:100', // 目的港设为强必填

        ]);

        Contract::create($validated);

        return redirect('/contracts')->with('success', '外贸出口合同主表建立成功！接下来请填充货物明细。');
    }

    /**
     * 4. 状态机切换核心触发器 (PATCH)
     * 控制业务流转：草稿(draft) -> 生效(active) -> 已发运(shipped) -> 已结案(completed)
     */
    public function updateStatus(Request $request, Contract $contract)
    {
        $validated = $request->validate([
            'status' => 'required|in:draft,active,shipped,completed,cancelled'
        ]);

        // 业务合规防呆审计拦截
        if ($validated['status'] === 'completed') {
            // 如果发票未全额收齐或退税没到账，强制给出合规警告，但允许特殊结案（视企业流程而定）
            $tracker = $contract->financialTracker;
            if ($contract->invoice_status !== 'fully_issued') {
                return redirect()->back()->with('error', '流转拦截：国内工厂进货发票尚未全额回收认证，无法结案！');
            }
        }

        $contract->update(['status' => $validated['status']]);

        return redirect()->back()->with('success', '合同业务状态机已成功切换至：' . strtoupper($validated['status']));
    }
}
