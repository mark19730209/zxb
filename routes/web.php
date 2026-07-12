<?php

use App\Http\Controllers\Crm;
use App\Http\Controllers\Feature;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    // Route::get('dashboard', Crm\DashboardController::class)->name('dashboard');

    // CRM Routes
    Route::resource('contacts', Crm\ContactController::class);
    Route::post('contacts/{contact}/favorite', [Crm\ContactController::class, 'favorite'])->name('contacts.favorite');
    Route::resource('organizations', Crm\OrganizationController::class)->only(['index', 'show', 'update']);
    Route::resource('contacts.notes', Crm\NoteController::class)->only(['store']);

    // Feature Showcase Routes
    Route::prefix('features')->name('features.')->group(function () {
        // Forms
        Route::prefix('forms')->name('forms.')->group(function () {
            Route::get('use-form', [Feature\FormController::class, 'useForm'])->name('use-form');
            Route::post('use-form', [Feature\FormController::class, 'submitUseForm']);

            Route::get('form-component', [Feature\FormController::class, 'formComponent'])->name('form-component');
            Route::post('form-component', [Feature\FormController::class, 'submitFormComponent']);

            Route::get('file-uploads', [Feature\FormController::class, 'fileUploads'])->name('file-uploads');
            Route::post('file-uploads', [Feature\FormController::class, 'submitFileUploads']);

            Route::get('validation', [Feature\FormController::class, 'validation'])->name('validation');
            Route::post('validation', [Feature\FormController::class, 'submitValidation']);
            Route::post('validation/secondary', [Feature\FormController::class, 'submitValidationSecondary'])->name('validation.secondary');

            Route::get('precognition', [Feature\FormController::class, 'precognition'])->name('precognition');
            Route::post('precognition', [Feature\FormController::class, 'storeAccount'])->middleware('precognitive');

            Route::get('optimistic-updates', [Feature\FormController::class, 'optimisticUpdates'])->name('optimistic-updates');
            Route::post('optimistic-toggle/{contact}', [Feature\FormController::class, 'toggleFavorite'])->name('optimistic-toggle');

            Route::get('use-form-context', [Feature\FormController::class, 'useFormContext'])->name('use-form-context');

            Route::get('dotted-keys', [Feature\FormController::class, 'dottedKeys'])->name('dotted-keys');
            Route::post('dotted-keys', [Feature\FormController::class, 'submitDottedKeys']);

            Route::get('wayfinder', [Feature\FormController::class, 'wayfinder'])->name('wayfinder');
        });

        // Navigation
        Route::prefix('navigation')->name('navigation.')->group(function () {
            Route::get('links', [Feature\NavigationController::class, 'links'])->name('links');
            Route::match(['post', 'put', 'patch', 'delete'], 'links', [Feature\NavigationController::class, 'linksAction']);

            Route::get('preserve-state', [Feature\NavigationController::class, 'preserveState'])->name('preserve-state');
            Route::get('preserve-scroll', [Feature\NavigationController::class, 'preserveScroll'])->name('preserve-scroll');
            Route::get('view-transitions', [Feature\NavigationController::class, 'viewTransitions'])->name('view-transitions');

            Route::get('history-management', [Feature\NavigationController::class, 'historyManagement'])->name('history-management');
            Route::post('history-management', [Feature\NavigationController::class, 'historyAction']);

            Route::get('async-requests', [Feature\NavigationController::class, 'asyncRequests'])->name('async-requests');
            Route::get('async-slow', [Feature\NavigationController::class, 'asyncSlow'])->name('async-slow');

            Route::get('manual-visits', [Feature\NavigationController::class, 'manualVisits'])->name('manual-visits');

            Route::get('redirects', [Feature\NavigationController::class, 'redirectDemo'])->name('redirects');
            Route::post('redirects/back', [Feature\NavigationController::class, 'redirectStandard'])->name('redirects.back');
            Route::post('redirects/to-route', [Feature\NavigationController::class, 'redirectToRoute'])->name('redirects.to-route');
            Route::post('redirects/external', [Feature\NavigationController::class, 'redirectExternal'])->name('redirects.external');

            Route::get('scroll-management', [Feature\NavigationController::class, 'scrollManagement'])->name('scroll-management');

            Route::get('instant-visits', [Feature\NavigationController::class, 'instantVisits'])->name('instant-visits');
            Route::get('instant-visit-target', [Feature\NavigationController::class, 'instantVisitTarget'])->name('instant-visit-target');

            Route::get('url-fragments', [Feature\NavigationController::class, 'urlFragments'])->name('url-fragments');
            Route::get('url-fragments/redirect-hash', [Feature\NavigationController::class, 'redirectWithHash']);
            Route::post('url-fragments/redirect-hash', [Feature\NavigationController::class, 'redirectWithHash']);
            Route::get('url-fragments/preserve-target', [Feature\NavigationController::class, 'preserveFragmentTarget']);
            Route::get('url-fragments/preserve-redirect', [Feature\NavigationController::class, 'preserveFragmentRedirect']);
        });

        // Data Loading
        Route::prefix('data-loading')->name('data-loading.')->group(function () {
            Route::get('deferred-props', [Feature\DataLoadingController::class, 'deferredProps'])->name('deferred-props');
            Route::get('partial-reloads', [Feature\DataLoadingController::class, 'partialReloads'])->name('partial-reloads');
            Route::get('infinite-scroll', [Feature\DataLoadingController::class, 'infiniteScroll'])->name('infinite-scroll');
            Route::get('when-visible', [Feature\DataLoadingController::class, 'whenVisible'])->name('when-visible');
            Route::get('polling', [Feature\DataLoadingController::class, 'polling'])->name('polling');
            Route::get('prop-merging', [Feature\DataLoadingController::class, 'propMerging'])->name('prop-merging');
            Route::get('once-props/{page?}', [Feature\DataLoadingController::class, 'onceProps'])->name('once-props')->where('page', '[12]');
            Route::get('optional-props', [Feature\DataLoadingController::class, 'optionalProps'])->name('optional-props');
        });

        // Prefetching
        Route::prefix('prefetching')->name('prefetching.')->group(function () {
            Route::get('link-prefetch', [Feature\PrefetchingController::class, 'linkPrefetch'])->name('link-prefetch');
            Route::get('stale-while-revalidate', [Feature\PrefetchingController::class, 'staleWhileRevalidate'])->name('stale-while-revalidate');
            Route::get('manual-prefetch', [Feature\PrefetchingController::class, 'manualPrefetch'])->name('manual-prefetch');
            Route::get('cache-management', [Feature\PrefetchingController::class, 'cacheManagement'])->name('cache-management');
        });

        // State Management
        Route::prefix('state')->name('state.')->group(function () {
            Route::get('remember', [Feature\StateController::class, 'remember'])->name('remember');
            Route::get('flash-data', [Feature\StateController::class, 'flashData'])->name('flash-data');
            Route::post('flash-data', [Feature\StateController::class, 'storeFlashData']);
            Route::post('flash-data/error', [Feature\StateController::class, 'storeFlashDataError'])->name('flash-data.error');
            Route::post('flash-data/warning', [Feature\StateController::class, 'storeFlashDataWarning'])->name('flash-data.warning');
            Route::get('shared-props', [Feature\StateController::class, 'sharedProps'])->name('shared-props');
        });

        // Layouts & Head
        Route::prefix('layouts')->name('layouts.')->group(function () {
            Route::get('persistent-layouts', [Feature\LayoutController::class, 'persistentLayouts'])->name('persistent-layouts');
            Route::get('persistent-layouts/page-2', [Feature\LayoutController::class, 'persistentLayoutsPageTwo'])->name('persistent-layouts.page-2');
            Route::get('nested-layouts', [Feature\LayoutController::class, 'nestedLayouts'])->name('nested-layouts');
            Route::get('head', [Feature\LayoutController::class, 'head'])->name('head');
            Route::get('layout-props', [Feature\LayoutController::class, 'layoutProps'])->name('layout-props');
        });

        // Events & Lifecycle
        Route::prefix('events')->name('events.')->group(function () {
            Route::get('global-events', [Feature\EventController::class, 'globalEvents'])->name('global-events');
            Route::post('global-events/action', [Feature\EventController::class, 'globalEventsAction'])->name('global-events.action');

            Route::get('visit-callbacks', [Feature\EventController::class, 'visitCallbacks'])->name('visit-callbacks');
            Route::post('visit-callbacks/action', [Feature\EventController::class, 'visitCallbacksAction'])->name('visit-callbacks.action');

            Route::get('progress', [Feature\EventController::class, 'progress'])->name('progress');
            Route::get('progress/slow', [Feature\EventController::class, 'progressSlow'])->name('progress.slow');
        });

        // Error Handling
        Route::prefix('errors')->name('errors.')->group(function () {
            Route::get('http-exceptions', [Feature\NetworkErrorController::class, 'httpExceptions'])->name('http-exceptions');
            Route::get('http-exceptions/403', [Feature\NetworkErrorController::class, 'httpException403'])->name('http-exceptions.403');
            Route::get('http-exceptions/404', [Feature\NetworkErrorController::class, 'httpException404'])->name('http-exceptions.404');
            Route::get('http-exceptions/500', [Feature\NetworkErrorController::class, 'httpException500'])->name('http-exceptions.500');
            Route::get('http-exceptions/unhandled', [Feature\NetworkErrorController::class, 'httpExceptionUnhandled'])->name('http-exceptions.unhandled');

            Route::get('network-errors', [Feature\NetworkErrorController::class, 'networkErrors'])->name('network-errors');
        });

        // HTTP
        Route::prefix('http')->name('http.')->group(function () {
            Route::get('use-http', [Feature\HttpController::class, 'useHttp'])->name('use-http');
            Route::post('use-http/api', [Feature\HttpController::class, 'useHttpApi'])->name('use-http.api');
        });
    });
});

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\ContractItemController;
use App\Http\Controllers\ExportAnalyticsController;
use App\Http\Controllers\ExportDocumentController;
use App\Http\Controllers\FinancialTrackerController;
use App\Http\Controllers\ImporterController;
use App\Http\Controllers\ExporterController;
use App\Http\Controllers\PurchaseInvoiceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PurchaseContractController;

/*
|--------------------------------------------------------------------------
| 外贸出口单据与供应链合规系统 - 核心路由表
|--------------------------------------------------------------------------
*/
// routes/web.php

// 🎯 全量切换为 Ziggy 规范的 Dashboard 入口端点
Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    
    // =====================================================================
    // 1. 基础档案管理 (Items & Suppliers)
    // =====================================================================
    // 物品/商品档案管理（含 H.S. Code、退税率、申报要素模板）
    Route::resource('categories', CategoryController::class)->except(['show']);
    
    Route::patch('items/{item}/toggle-active', [ItemController::class, 'toggleActive'])->name('items.toggle-active');
    Route::resource('items', ItemController::class)->except(['show']);
    
    // 国内供货工厂/商户管理
    Route::resource('suppliers', SupplierController::class)->except(['show']);

    // 境外买方管理
    Route::resource('importers', ImporterController::class)->except(['show']);
    Route::resource('exporters', ExporterController::class)->except(['show']);

    // =====================================================================
    // 2. 外贸合同与主订单生命周期 (Contracts)
    // =====================================================================
    // 合同主表管理（状态机：草稿、生效、已发运、已结案、已作废）
    Route::resource('contracts', ContractController::class);
    
    // 动态更新合同整体的业务/发运状态
    Route::patch('contracts/{contract}/status', [ContractController::class, 'updateStatus'])
        ->name('contracts.update-status');

    // =====================================================================
    // 3. 智能制单与动态“申报要素串”联动 (Contract Items)
    // =====================================================================
    // 保存/重置某个合同下的所有商品明细、物流体积重量、以及组装好的海关要素串
    // Route::post('contracts/{contract}/items', [ContractItemController::class, 'store'])
    //     ->name('contracts.items.store');
    // =====================================================================
    // 🎯 核心补齐：合同内单一物品（明细）级联增删改查路由
    // =====================================================================
    Route::get('contracts/{contract}/items', [ContractItemController::class, 'index'])->name('contracts.items.index');
    Route::post('contracts/{contract}/items/store', [ContractItemController::class, 'store'])->name('contracts.items.single_store');
    Route::put('contracts/{contract}/items/{item_id}', [ContractItemController::class, 'update'])->name('contracts.items.single_update');
    Route::delete('contracts/{contract}/items/{item_id}', [ContractItemController::class, 'destroy'])->name('contracts.items.single_destroy');


    // =====================================================================
    // 4. 五维单据工作台分发中心 (Export Documents)
    // =====================================================================
    // 核心制单工作台：一次性组装输出包含 Invoice, Packing List, 报关单, 申报要素等5个维度的全套数据
    Route::get('contracts/{contract}/documents', [ExportDocumentController::class, 'show'])
        ->name('contracts.documents.show');

    // 🎯 核心补齐：专门接收前端 Show.vue 工作台发来的 PUT 覆盖更新请求！
    Route::put('contracts/{contract}/documents', [ExportDocumentController::class, 'update'])
        ->name('contracts.documents.update');

    // 🎯 完全契合 Wayfinder 的后端模板导出映射端点
    Route::get('contracts/{contract}/documents/excel-template', [ExportDocumentController::class, 'exportExcelTemplate']);
    // [可选扩展] 支持一键导出/下载正式的外贸 PDF 单据
    
    Route::get('contracts/{contract}/documents/export/{type}', [ExportDocumentController::class, 'exportPdf'])
        ->name('contracts.documents.export-pdf')
        ->where('type', 'invoice|packing|customs');


    // =====================================================================
    // 5. 财务供应链与合规退税核销 (Financial & Tax Trackers)
    // =====================================================================
    // 进入某个合同的财务勾稽专属大盘（查看开票进度、退税核销百分比）
    Route::get('contracts/{contract}/financials', [FinancialTrackerController::class, 'index'])
        ->name('contracts.financials.index');

    // 快捷登记接口：收到某个国内供货商（工厂）寄来的进货增值税专用发票并执行多商户差额核销
    Route::post('contracts/{contract}/invoices', [FinancialTrackerController::class, 'registerInvoice'])
        ->name('finance.invoice.register');

    // 录入实际退税款项：当向税局申请的退税款到账时，核销预估退税额并变更退税状态
    Route::post('contracts/{contract}/refunds', [FinancialTrackerController::class, 'receiveTaxRefund'])
        ->name('finance.refund.receive');

    Route::get('analytics', [ExportAnalyticsController::class, 'index'])
        ->name('export.analytics');

    // =====================================================================
    // 🎯 核心补齐：全系统进货发票综合管理大盘
    // =====================================================================
    // 发票总台账列表页
    Route::get('invoices', [PurchaseInvoiceController::class, 'index'])->name('invoices.index');
    Route::post('invoices/{invoice}/upload-attachment', [PurchaseInvoiceController::class, 'uploadAttachment'])
    ->name('invoices.upload-attachment');

    // 🎯 全量切换为标准的 Ziggy 别名方法，供前端 Index.vue 极其利落地进行调用
    Route::put('invoices/{invoice}', [PurchaseInvoiceController::class, 'update'])->name('invoices.update');
    Route::delete('invoices/{invoice}', [PurchaseInvoiceController::class, 'destroy'])->name('invoices.destroy');

    // 跨订单合并大额专票分摊挂账登记接口
    Route::post('financials/multi-invoice', [PurchaseInvoiceController::class, 'registerMultiOrderInvoice'])
        ->name('finance.multi-invoice.register');

    // routes/web.php -> 补齐国内供应链合约区块
    Route::get('purchase-contracts', [PurchaseContractController::class, 'index'])->name('purchase-contracts.index');
    Route::post('purchase-contracts/store', [PurchaseContractController::class, 'store'])->name('purchase-contracts.store');
    Route::post('purchase-contracts/link-invoice', [PurchaseContractController::class, 'linkInvoice'])->name('purchase-contracts.link-invoice');
    // Route::get('purchase-contracts/{purchase_contract}/exportPdf', [PurchaseContractController::class, 'exportPdf'])->name('purchase-contracts.exportPdf');
    // 🎯 确保这里是 Route::post，而不是 Route::get 
    Route::post('purchase-contracts/{purchase_contract}/exportPdf', [PurchaseContractController::class, 'exportPdf'])->name('purchase-contracts.exportPdf');
// 2. 🎯 核心新增：后期不走进程、直接读取服务器文件的秒速下载 GET 通道
    Route::get('purchase-contracts/{purchase_contract}/downloadArchived', [PurchaseContractController::class, 'downloadArchivedPdf'])->name('purchase-contracts.downloadArchived');
    Route::put('purchase-contracts/{purchase_contract}', [PurchaseContractController::class, 'update'])
        ->name('purchase-contracts.update');
});
