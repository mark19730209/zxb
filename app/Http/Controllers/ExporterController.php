<?php

namespace App\Http\Controllers;

use App\Models\Exporter;
use Inertia\Inertia;
use Illuminate\Http\Request;

class ExporterController extends Controller
{
    public function index(Request $request)
    {
        $exporters = Exporter::query()
            ->when($request->input('search'), function ($query, $search) {
                $query->where('company_name_en', 'like', "%{$search}%")
                      ->orWhere('company_name_cn', 'like', "%{$search}%")
                      ->orWhere('tax_id', 'like', "%{$search}%");
            })
            ->orderBy('id', 'desc')
            ->paginate(10);

        return Inertia::render('Exporters/Index', ['exporters' => $exporters, 'filters' => $request->only(['search'])]);
    }

    public function create() { return Inertia::render('Exporters/Create'); }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_name_cn' => 'required|string|unique:exporters,company_name_cn',
            'company_name_en' => 'required|string|unique:exporters,company_name_en',
            'company_address' => 'required|string',
            'contact_tel'     => 'required|string',
            'tax_id'          => 'required|string|max:18|unique:exporters,tax_id',
            'customs_code'    => 'nullable|string|max:10',
            'bank_name'       => 'required|string',
            'bank_account'    => 'required|string',
            'swift_code'      => 'required|string',
            'bank_address'    => 'required|string',
        ]);

        Exporter::create($validated);
        return redirect('/exporters')->with('success', '境外卖方档案建立成功！');
    }

    public function edit(Exporter $exporter) { return Inertia::render('Exporters/Edit', ['exporter' => $exporter]); }

    public function update(Request $request, Exporter $exporter)
    {
        $validated = $request->validate([
            'company_name_cn' => 'required|string|unique:exporters,company_name_cn,' . $exporter->id,
            'company_name_en' => 'required|string|unique:exporters,company_name_en,' . $exporter->id,
            'company_address' => 'required|string',
            'contact_tel'     => 'required|string',
            'tax_id'          => 'required|string|max:18|unique:exporters,tax_id,' . $exporter->id,
            'customs_code'    => 'nullable|string|max:10',
            'bank_name'       => 'required|string',
            'bank_account'    => 'required|string',
            'swift_code'      => 'required|string',
            'bank_address'    => 'required|string',
        ]);

        $exporter->update($validated);
        return redirect('/exporters')->with('success', '境外卖方档案更新成功！');
    }

    public function destroy(Exporter $exporter)
    {
        if ($exporter->contracts()->exists()) {
            return redirect()->back()->with('error', '无法注销：该卖方名下已有正在执行的出口合同。');
        }
        $exporter->delete();
        return redirect('/exporters')->with('success', '档案成功安全移除。');
    }
}