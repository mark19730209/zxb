<script setup lang="ts">
import { useForm, Link } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Progress } from '@/components/ui/progress'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import AppLayout from '@/layouts/AppLayout.vue';
import contractsRoute from '@/routes/contracts' // 🎯 引入 Wayfinder 规范路由
import invoicesroute from '@/routes/invoices';
import type { Contract, FinancialTracker, Supplier, PurchaseInvoiceWithSupplier } from '@/types'
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'ERP/TMS' },
];
// const props = defineProps({
//   contract: Object,
//   tracker: Object,
//   existingInvoices: Array,
//   suppliers: Array
// })
const props = defineProps<{
  contract: Contract
  tracker: FinancialTracker
  existingInvoices: PurchaseInvoiceWithSupplier[] // 严格的发票流水线数组
  suppliers: Supplier[]                          // 严格的可选国内供货商档案
}>()
// 1. 初始化快捷登记发票表单
const invoiceForm = useForm({
  supplier_id: '',
  invoice_no: '',
  issue_date: new Date().toISOString().split('T')[0],
  total_amount: '',
  tax_rate: '13' // 服装针织类进项默认13%
})

// 2. 初始化退税款入账表单
const refundForm = useForm({
  actual_refund_received: '',
  refund_receive_date: new Date().toISOString().split('T')[0]
})

// 🎯 动态计算：发票整体回收进度比例
const invoiceProgress = computed(() => {
  const total = props.tracker?.purchase_total_amount
  if (total === 0) return 0
  return (props.tracker?.received_invoice_amount / total) * 100
})

// 🎯 动态计算：出口退税款到账进度比例
const refundProgress = computed(() => {
  const estimated = props.tracker?.estimated_refund
  if (estimated === 0) return 0
  return (props.tracker?.actual_refund_received / estimated) * 100
})

// 🎯 提交：向 Wayfinder 静态端点抛送专票登记包
const submitInvoiceRegistration = () => {
  invoiceForm.post(`/contracts/${props.contract.id}/invoices`, {
    preserveScroll: true,
    onSuccess: () => {
      invoiceForm.reset('invoice_no', 'total_amount')
      alert('财务合规系统提示：桐庐华艺等工厂专票核销成功，已动态拉升供应链勾稽百分比！')
    }
  })
}

// 🎯 提交：国库退税款落袋核销
const submitRefundVerification = () => {
  refundForm.post(`/contracts/${props.contract.id}/refunds`, {
    preserveScroll: true,
    onSuccess: () => {
      refundForm.reset('actual_refund_received')
      alert('财务合规系统提示：税务局退税资金核销成功，本期出口全盘利润已落袋结平！')
    }
  })
}
// 🎯 1. 声明用于控制“补传发票”的独立响应式表单对象
const uploadForm = useForm({
  invoice_file: null as File | null
})

// 当前正在执行补传的发票实体 ID（用于弹窗或激活输入框状态行）
const activeUploadingInvoiceId = ref<number | null>(null)

const startUploadClick = (id: number) => {
  activeUploadingInvoiceId.value = id
  uploadForm.invoice_file = null
}

const handleAddonFileChange = (event: Event) => {
  const target = event.target as HTMLInputElement
  if (target.files && target.files.length > 0) {
    uploadForm.invoice_file = target.files[0]
  }
}

// 🎯 2. 提交补传的物理文件包
const submitAddonAttachment = (id: number) => {
  if (!uploadForm.invoice_file) {
    alert('请先选择发票文件！')
    return
  }

  // 完全切换为最纯正的 Ziggy 传参：直接调取别名，强制 Form 二进制上送
  uploadForm.submit(invoicesroute.uploadAttachment(id), {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      activeUploadingInvoiceId.value = null
      uploadForm.reset()
      alert('财务合规提示：发票原始凭证补传并固化入库成功！')
    }
  })
}
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="min-h-screen p-8 max-w-7xl mx-auto space-y-6 bg-background text-foreground">

      <!-- 头部导航及合同溯源 -->
      <div class="flex justify-between items-center bg-card border border-border p-4 rounded-2xl shadow-lg shadow-primary/5">
        <div>
          <span class="text-primary text-xs font-semibold tracking-widest uppercase">FINANCIAL COMPLIANCE & TAX REFUND PANEL</span>
          <h1 class="text-2xl font-bold tracking-tight bg-linear-to-r from-primary to-success bg-clip-text text-transparent">合同号：{{ contract.contract_no }} 供应链财务勾稽大盘</h1>
        </div>
        <Button as-child variant="secondary" size="sm" class="h-8 text-xs font-bold rounded-md">
          <Link :href="contractsRoute.index()">返回合同主台账</Link>
        </Button>
      </div>

      <!-- 上半部分：两大核心核心资金进度条 -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        <!-- 进度条 1：进货发票回收大盘 -->
        <Card class="bg-card border-border rounded-2xl shadow-lg shadow-primary/5">
          <CardHeader class="border-b border-border pb-4">
            <CardTitle class="text-sm font-bold text-foreground flex justify-between">
              <span>① 进货增值税专票总勾稽进度</span>
              <Badge variant="outline" class="font-mono bg-primary/10 text-primary border-primary/20">
                {{ invoiceProgress.toFixed(1) }}%
              </Badge>
            </CardTitle>
            <CardDescription>对比当前合同下明细估算的含税采购预算进行百分比配平</CardDescription>
          </CardHeader>
          <CardContent class="space-y-4">
            <Progress :model-value="invoiceProgress" class="h-2.5" />
            <div class="grid grid-cols-2 gap-2 text-xs pt-1">
              <div class="bg-secondary/40 border border-border rounded-xl p-3">
                <span class="text-muted-foreground">本期国内总采购应开：</span>
                <div class="text-sm font-bold text-foreground">￥{{ tracker?.purchase_total_amount }}</div>
              </div>
              <div class="bg-secondary/40 border border-border rounded-xl p-3">
                <span class="text-muted-foreground">各家工厂已寄回认证：</span>
                <div class="text-sm font-bold text-success tracking-wide">￥{{ tracker?.received_invoice_amount }}</div>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- 进度条 2：国家税务局出口退税款到账大盘 -->
        <Card class="bg-card border-border rounded-2xl shadow-lg shadow-primary/5">
          <CardHeader class="border-b border-border pb-4">
            <CardTitle class="text-sm font-bold text-foreground flex justify-between">
              <span>② 海关放行与税局退税款到账率</span>
              <Badge variant="outline" class="font-mono bg-success/10 text-success border-success/20">
                {{ refundProgress.toFixed(1) }}%
              </Badge>
            </CardTitle>
            <CardDescription>核心公式：不含税采购总额 × 商品预设退税率</CardDescription>
          </CardHeader>
          <CardContent class="space-y-4">
            <Progress :model-value="refundProgress" class="h-2.5" />
            <div class="grid grid-cols-2 gap-2 text-xs pt-1">
              <div class="bg-secondary/40 border border-border rounded-xl p-3">
                <span class="text-muted-foreground">系统推演预计可退：</span>
                <div class="text-sm font-bold text-foreground">￥{{ tracker?.estimated_refund }}</div>
              </div>
              <div class="bg-secondary/40 border border-border rounded-xl p-3">
                <span class="text-muted-foreground">国库资金实际已到账：</span>
                <div class="text-sm font-bold text-success tracking-wide">￥{{ tracker?.actual_refund_received }}</div>
              </div>
            </div>
          </CardContent>
        </Card>

      </div>

      <!-- 下半部分：多商户发票快捷登记区与历史对账单 -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- 左侧及中间：发票登记流水线 -->
        <div class="lg:col-span-2 space-y-6">
          <Card class="bg-card border-border rounded-2xl shadow-lg shadow-primary/5">
            <CardHeader class="border-b border-border pb-4">
              <CardTitle class="text-sm font-bold">已收工厂专票历史流向台账</CardTitle>
            </CardHeader>
            <CardContent>
              <div class="rounded-xl border border-border bg-card overflow-hidden">
                <Table>
                  <TableHeader>
                    <TableRow class="bg-primary text-primary-foreground text-xs">
                      <TableHead>供货商/开票抬头</TableHead>
                      <TableHead>发票号码</TableHead>
                      <TableHead class="text-center w-55">影像凭证档案</TableHead>
                      <TableHead class="text-right">税率/税额</TableHead>
                      <TableHead class="text-right">价税合计总额</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    <TableRow v-for="inv in existingInvoices" :key="inv.id" class="hover:bg-secondary/40 transition-colors text-xs">
                      <!-- 工厂抬头与开票日期 -->
                      <TableCell>
                        <div class="font-medium text-foreground">{{ inv.supplier?.company_name }}</div>
                        <div class="text-[10px] text-foreground/80 font-mono">日期: {{ inv.issue_date }}</div>
                      </TableCell>

                      <!-- 🎯 统一展示 20 位纯数字长流水号 -->
                      <TableCell class="font-mono">
                        <div class="font-mono font-bold tracking-wider text-primary">{{ inv.invoice_no }}</div>
                      </TableCell>

                      <!-- 影像附件调阅 -->
                      <!-- 🎯 核心高能区：动态渲染 [查阅原件] 与 [快捷补传表单格] -->
                      <TableCell class="text-center">
                        <!-- 状态 A：如果已经存在附件，直接供调阅查看 -->
                        <a v-if="inv.file_path" :href="`/storage/${inv.file_path}`" target="_blank"
                          class="inline-flex items-center gap-1 text-xs font-bold bg-primary/10 text-primary border border-primary/20 hover:bg-primary hover:text-primary-foreground rounded-md px-2 py-1">
                          📎 查阅原始凭证
                        </a>

                        <!-- 状态 B：如果没有附件，且目前没触发点击，展示补传按钮 -->
                        <div v-else-if="activeUploadingInvoiceId !== inv.id" class="space-y-1">
                          <span class="text-[10px] text-destructive italic block">⚠️ 缺失影像凭证</span>
                          <Button size="sm" variant="outline"
                            class="h-6 text-[10px] border-warning/20 bg-warning/10 text-warning hover:bg-warning hover:text-warning-foreground rounded-md"
                            @click="startUploadClick(inv.id)">
                            📤 快捷补传发票
                          </Button>
                        </div>

                        <!-- 状态 C：点击补传后，当场绽放出文件选择控件与小“对勾”提交按钮 -->
                        <div v-else
                          class="flex items-center gap-1 bg-secondary/40 border-border rounded-lg p-1 border animate-fadeIn">
                          <input type="file" accept=".pdf,.jpg,.jpeg,.png" @change="handleAddonFileChange"
                            class="w-30 text-[9px] text-muted-foreground file:py-0.5 file:px-1 file:rounded file:border-0 file:text-[9px] file:bg-background cursor-pointer" />
                          <div class="flex gap-1">
                            <Button size="sm" class="h-5 px-1.5 bg-success hover:brightness-110 text-success-foreground text-[9px] rounded-md"
                              @click="submitAddonAttachment(inv.id)" :disabled="uploadForm.processing">
                              ✓
                            </Button>
                            <button type="button" class="text-[10px] text-muted-foreground hover:text-foreground px-1"
                              @click="activeUploadingInvoiceId = null">
                              ✕
                            </button>
                          </div>
                        </div>
                      </TableCell>

                      <TableCell class="text-right font-mono text-muted-foreground">
                        <div>13%</div>
                        <div>￥{{ inv.tax_amount }}</div>
                      </TableCell>

                      <!-- 🎯 核心高能：这里展示的 total_amount 将在桥接算法下，精准变为【本次大额发票分摊扣减给本合同的实际金额】 -->
                      <TableCell class="text-right font-bold font-mono text-foreground text-sm">
                        ￥{{ inv.total_amount }}
                      </TableCell>

                    </TableRow>

                    <TableRow v-if="existingInvoices.length === 0">
                      <TableCell colspan="4" class="text-center py-6 text-muted-foreground text-xs">暂无任何国内工厂专票入账回档流水
                      </TableCell>
                    </TableRow>
                  </TableBody>
                </Table>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- 右侧：两大财务登记挂账浮窗 -->
        <div class="space-y-6">

          <!-- 弹窗 1：录入新收到的工厂发票（支持桐庐华艺一键录入） -->
          <Card class="bg-card border-border rounded-2xl shadow-lg shadow-primary/5">
            <CardHeader class="border-b border-border pb-4">
              <CardTitle class="text-xs font-bold text-primary flex items-center gap-1">⚡ 新收国内工厂发票回档登记</CardTitle>
            </CardHeader>
            <CardContent class="space-y-3.5 text-xs">
              <div class="space-y-1">
                <Label class="text-[11px] text-muted-foreground">销售方开票工厂 (Supplier)</Label>
                <select v-model="invoiceForm.supplier_id"
                  class="w-full h-8 rounded-lg border-border bg-background px-2 text-xs shadow-sm focus:ring-2 focus:ring-ring" required>
                  <option value="" disabled>请选择开票的国内工厂...</option>
                  <option v-for="sup in suppliers" :key="sup.id" :value="sup.id">{{ sup.company_name }}</option>
                </select>
              </div>
              <div class="space-y-1">
                <Label class="text-[11px] text-muted-foreground">发票号码</Label>
                <Input v-model="invoiceForm.invoice_no" class="h-8 text-xs font-mono rounded-lg border-border bg-background focus-visible:ring-ring"
                  placeholder="例如：26332000005417048116" required />
              </div>
              <div class="grid grid-cols-2 gap-2">
                <div class="space-y-1">
                  <Label class="text-[11px] text-muted-foreground">价税合计(元)</Label>
                  <Input type="number" step="0.01" v-model="invoiceForm.total_amount"
                    class="h-8 text-xs font-bold font-mono text-foreground rounded-lg border-border bg-background focus-visible:ring-ring" placeholder="￥0.00" required />
                </div>
                <div class="space-y-1">
                  <Label class="text-[11px] text-muted-foreground">开票日期</Label>
                  <Input type="date" v-model="invoiceForm.issue_date" class="h-8 text-xs rounded-lg border-border bg-background focus-visible:ring-ring" required />
                </div>
              </div>
              <Button class="w-full rounded-md bg-primary hover:brightness-110 text-primary-foreground shadow-lg shadow-primary/20 transition-all duration-200 hover:scale-[1.02] h-8 text-[11px] font-bold"
                :disabled="invoiceForm.processing"
                @click="submitInvoiceRegistration">
                确认勾稽发票入账
              </Button>
            </CardContent>
          </Card>

          <!-- 弹窗 2：录入税局退税款落袋 -->
          <Card class="bg-card border-border rounded-2xl shadow-lg shadow-primary/5">
            <CardHeader class="border-b border-border pb-4">
              <CardTitle class="text-xs font-bold text-primary flex items-center gap-1">
                ✔ 国家税务局国库退税到账确认
              </CardTitle>
            </CardHeader>
            <CardContent class="space-y-3.5 text-xs">
              <div class="grid grid-cols-2 gap-2">
                <div class="space-y-1">
                  <Label class="text-[11px] text-muted-foreground">本次到账金额</Label>
                  <Input type="number" step="0.01" v-model="refundForm.actual_refund_received"
                    class="h-8 text-xs font-bold text-primary font-mono rounded-lg border-border bg-background focus-visible:ring-ring" placeholder="￥0.00" required />
                </div>
                <div class="space-y-1">
                  <Label class="text-[11px] text-muted-foreground">到账日期</Label>
                  <Input type="date" v-model="refundForm.refund_receive_date" class="h-8 text-xs rounded-lg border-border bg-background focus-visible:ring-ring" required />
                </div>
              </div>
              <Button class="w-full rounded-md bg-primary hover:brightness-110 text-primary-foreground shadow-lg shadow-primary/20 transition-all duration-200 hover:scale-[1.02] h-8 text-[11px] font-bold"
                :disabled="refundForm.processing"
                @click="submitRefundVerification">
                核销可退税额大盘
              </Button>
            </CardContent>
          </Card>

        </div>
      </div>

    </div>
  </AppLayout>
</template>
