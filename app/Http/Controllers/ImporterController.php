<?php

// app/Http/Controllers/ImporterController.php
namespace App\Http\Controllers;

use App\Models\Importer;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ImporterController extends Controller
{
    public function index(Request $request)
    {
        $importers = Importer::query()
            ->when($request->input('search'), function ($query, $search) {
                $query->where('company_name_en', 'like', "%{$search}%")
                      ->orWhere('country_code', 'like', "%{$search}%");
            })
            ->orderBy('id', 'desc')
            ->paginate(10);

        return Inertia::render('Importers/Index', ['importers' => $importers, 'filters' => $request->only(['search'])]);
    }

    public function create() { return Inertia::render('Importers/Create'); }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_name_en'    => 'required|string|unique:importers,company_name_en',
            'company_address_en' => 'required|string',
            'country_code'       => 'required|string|max:3',
            'contact_email'      => 'nullable|email',
            'tax_no'             => 'nullable|string'
        ]);

        Importer::create($validated);
        return redirect('/importers')->with('success', '境外买方档案建立成功！');
    }

    public function edit(Importer $importer) { return Inertia::render('Importers/Edit', ['importer' => $importer]); }

    public function update(Request $request, Importer $importer)
    {
        $validated = $request->validate([
            'company_name_en'    => 'required|string|unique:importers,company_name_en,' . $importer->id,
            'company_address_en' => 'required|string',
            'country_code'       => 'required|string|max:3',
            'contact_email'      => 'nullable|email',
            'tax_no'             => 'nullable|string'
        ]);

        $importer->update($validated);
        return redirect('/importers')->with('success', '境外买方档案更新成功！');
    }

    public function destroy(Importer $importer)
    {
        if ($importer->contracts()->exists()) {
            return redirect()->back()->with('error', '无法注销：该买方名下已有正在执行的出口合同。');
        }
        $importer->delete();
        return redirect('/importers')->with('success', '档案成功安全移除。');
    }
}
