<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Contract;
use App\Models\Supplier;
use App\Models\FinancialTracker;
use App\Models\InvoiceAllocation;
use App\Models\PurchaseInvoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialComplianceTest extends TestCase
{
    // 🎯 核心高能：每次跑测试前全自动重置并清理测试数据库，保证数据绝对纯净
    use RefreshDatabase;

    protected $user;
    protected $supplier;
    protected $contract1;
    protected $contract2;
    protected $tracker1;
    protected $tracker2;

    /**
     * 测试前置环境初始化：快速伪造经营主体、工厂及两个出口合同
     */
    protected function setUp(): void
    {
        parent::setUp();

        // 1. 伪造一个登录外贸员
        $this->user = User::factory()->create();

        // 2. 伪造国内供货商（如：桐庐华艺针织有限公司）
        $this->supplier = Supplier::create([
            'company_name' => '桐庐华艺针织有限公司',
            'tax_id' => '91330122720040617G',
            'company_address' => '浙江省杭州市桐庐县横村镇桐千路898号',
            'company_phone' => '0571-58587378',
            'bank_name' => '桐庐农村商业银行横村支行',
            'bank_account' => '201000003721565',
            'bank_code' => '402331006063'
        ]);

        // 3. 伪造出口合同 01（含采购预算 40,000 元）
        $this->contract1 = Contract::create([
            'contract_no' => 'CT-2026-001',
            'exporter_id' => 1,
            'importer_id' => 1,
            'contract_date' => '2026-06-28',
            'currency' => 'USD',
            'incoterms' => 'FOB',
            'payment_terms' => 'T/T 30/70',
            'status' => 'active',
            'invoice_status' => 'none'
        ]);
        $this->tracker1 = FinancialTracker::create([
            'contract_id' => $this->contract1->id,
            'purchase_total_amount' => 40000.00, // 👈 预设含税采购预算
            'received_invoice_amount' => 0.00,
            'estimated_refund' => 4601.77
        ]);

        // 4. 伪造出口合同 02（含采购预算 60,000 元）
        $this->contract2 = Contract::create([
            'contract_no' => 'CT-2026-002',
            'exporter_id' => 1,
            'importer_id' => 1,
            'contract_date' => '2026-06-28',
            'currency' => 'USD',
            'incoterms' => 'FOB',
            'payment_terms' => 'T/T 30/70',
            'status' => 'active',
            'invoice_status' => 'none'
        ]);
        $this->tracker2 = FinancialTracker::create([
            'contract_id' => $this->contract2->id,
            'purchase_total_amount' => 60000.00, // 👈 预设含税采购预算
            'received_invoice_amount' => 0.00,
            'estimated_refund' => 6902.65
        ]);
    }

    /**
     * 🛡️ 风控防线测试 1：验证当财务拆摊的分摊金额总和，不等于 20位发票票面总额时，系统必须实施硬拦截。
     */
    public function test_it_blocks_invoice_registration_if_allocations_do_not_match_total_amount(): void
    {
        // 模拟提交一笔 100,000 元的大额发票
        $response = $this->actingAs($this->user)->post(route('finance.multi-invoice.register'), [
            'supplier_id' => $this->supplier->id,
            'invoice_no' => '26332000002331079951', // 20位发票号
            'issue_date' => '2026-06-28',
            'total_amount' => 100000.00,
            'tax_rate' => 13,
            // 🎯 故意的错漏数据：分配 40,000 + 50,000 = 90,000，故意留下 10,000 差额不配平
            'allocations' => [
                ['contract_id' => $this->contract1->id, 'amount' => 40000.00],
                ['contract_id' => $this->contract2->id, 'amount' => 50000.00],
            ]
        ]);

        // 🛑 断言：系统必须抛出 Session 验证错误，阻断入库，并重定向返回
        $response->assertSessionHasErrors(['allocations']);
        
        // 断言：数据库发票表中绝对没有写入这条错误脏数据
        $this->assertDatabaseMissing('purchase_invoices', [
            'invoice_no' => '26332000002331079951'
        ]);
    }

    /**
     * 🛡️ 业务闭环测试 2：验证标准的合并发票拆分、跨订单精准核销挂账、以及主合同发票状态机联动流转。
     */
    public function test_it_successfully_registers_multi_order_invoice_and_updates_contract_status(): void
    {
        // 模拟提交 100,000 元发票，并完美配平分配：合同一分 40,000（达到100%），合同二分 60,000（达到100%）
        $response = $this->actingAs($this->user)->post(route('finance.multi-invoice.register'), [
            'supplier_id' => $this->supplier->id,
            'invoice_no' => '26332000002331079951',
            'issue_date' => '2026-06-28',
            'total_amount' => 100000.00,
            'tax_rate' => 13,
            'allocations' => [
                ['contract_id' => $this->contract1->id, 'amount' => 40000.00],
                ['contract_id' => $this->contract2->id, 'amount' => 60000.00],
            ]
        ]);

        // 🛑 断言：后端返回完全顺畅
        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        // 🛑 断言：发票主表顺利生成记录，且根据 13% 税率自动逆推了不含税和税额
        $this->assertDatabaseHas('purchase_invoices', [
            'invoice_no' => '26332000002331079951',
            'total_amount' => 100000.00,
            'tax_amount' => 11504.42, // 100000 - (100000 / 1.13) = 11504.42
        ]);

        // 🛑 断言：分摊明细表精确下网了两条镜像明细
        $this->assertDatabaseHas('invoice_allocations', [
            'contract_id' => $this->contract1->id,
            'allocated_amount' => 40000.00
        ]);

        // 🛑 断言：核心大盘全自动算齐配平！由于发票已打满含税预算，合同发票回收状态被迫平滑跃迁至“发票全齐”
        $this->assertEquals('fully_issued', $this->contract1->fresh()->invoice_status);
        $this->assertEquals('fully_issued', $this->contract2->fresh()->invoice_status);
    }

    /**
     * 🛡️ 风控防线测试 3：验证发票未收齐时，强行点击“业务结案”状态机流转，系统必须触发硬拦截。
     */
    public function test_it_intercepts_contract_completion_if_invoices_are_not_fully_issued(): void
    {
        // 合同 1 目前开票进度为 0% (invoice_status = 'none')
        // 模拟外贸操作员强行通过接口将其下拉切换为 COMPLETED 状态
        $response = $this->actingAs($this->user)->patch(route('contracts.update-status', $this->contract1->id), [
            'status' => 'completed'
        ]);

        // 🛑 断言：系统必须拦截该动作，并重定向弹回 Session 报错提示
        $response->assertSessionHas('error');
        
        // 断言：该合同的业务流状态依然顽固地停留在 active，绝不被篡改结案
        $this->assertEquals('active', $this->contract1->fresh()->status);
    }
}
