<!-- resources/js/Pages/Invoices/Index.vue [20位统一发票流水号版] -->
<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3'
import { debouncedWatch } from '@vueuse/core'
import { ref, computed } from 'vue'
// import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import AppLayout from '@/layouts/AppLayout.vue';
import invoicesroute from '@/routes/invoices';
import type { PurchaseInvoicePageProps } from '@/types'
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'ERP/TMS' },
];
const props = defineProps<PurchaseInvoicePageProps>()
const search = ref(props.filters.search || '')

// watch(search, debounce((val) => {
//   router.get('/invoices', { search: val }, { preserveState: true, replace: true })
// }, 300))
// 💡 推荐：使用 debouncedWatch 实现真正的 300ms 防抖监听
debouncedWatch(search, (value) => {

  // 1. 从 Wayfinder 路由对象中提取出纯字符串 URL（通常可以直接调 .url()）
  const url = invoicesroute.index.url()

  // 2. 正确将 URL 字符串传递给 router.get
  router.get(
    url,
    { search: value },
    { preserveState: true, replace: true }
  )

}, { debounce: 300 }) // 👈 这里的 300ms 才会真正生效

// 🎯 核心精简：表单对象中彻底移除 invoice_code
const form = useForm({
  supplier_id: '',
  invoice_no: '', // 统一收发 20 位大额数字流水
  issue_date: new Date().toISOString().split('T')[0],
  total_amount: '',
  tax_rate: '13',
  invoice_file: null as File | null, // 🎯 核心补齐：存储选中的照片或PDF物理文件
  allocations: [{ contract_id: '', amount: '' }]
})

// 🎯 接管前端文件控件的选择行为
const handleFileSelection = (event: Event) => {
  const target = event.target as HTMLInputElement
  if (target.files && target.files.length > 0) {
    form.invoice_file = target.files[0]
  }
}
const addAllocationRow = () => { form.allocations.push({ contract_id: '', amount: '' }) }
const removeAllocationRow = (idx: number) => { form.allocations.splice(idx, 1) }

const currentAllocatedSum = computed(() => {
  return form.allocations.reduce((sum, item) => sum + (Number(item.amount) || 0), 0)
})

const isBalanced = computed(() => {
  return Math.abs(currentAllocatedSum.value - Number(form.total_amount)) < 0.01 && Number(form.total_amount) > 0
})

const submitMultiInvoice = () => {
  if (!isBalanced.value) return

  // 🎯 注意：因为包含真实的图片/PDF物理文件上传，在提交给后端时，必须通过 `forceFormData: true` 强制转为二进制复合表单
  form.post('/financials/multi-invoice', {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      form.reset('invoice_no', 'total_amount', 'invoice_file')
      form.allocations = [{ contract_id: '', amount: '' }]
      alert('带高保真图片/PDF凭证大包的发票拆分核销全部成功！')
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

// 🎯 1. 声明用于行内修改发票的独立响应式表单对象
const editForm = useForm({
  invoice_no: '',
  issue_date: '',
  total_amount: 0,
  tax_rate: '13'
})

// 控制当前哪一行发票正处于“编辑状态”的响应式 ID 挂钩
const activeEditingInvoiceId = ref<number | null>(null)

const startInlineEdit = (inv: any) => {
  activeEditingInvoiceId.value = inv.id
  editForm.invoice_no = inv.invoice_no
  editForm.issue_date = inv.issue_date
  editForm.total_amount = parseFloat(inv.total_amount)
}

// 🎯 2. 提交发票的行内修正
const submitInvoiceUpdate = (id: number) => {
  // 笔直投递给 Ziggy 别名端点
  editForm.submit(invoicesroute.update(id), {
    preserveScroll: true,
    onSuccess: () => {
      activeEditingInvoiceId.value = null
      alert('发票信息更正成功，全网业务流状态机已重新咬合配平！')
    }
  })
}

// 🎯 3. 触发发票的联锁销毁和撤销动作
const triggerInvoiceDestroy = (id: number, no: string) => {
  if (confirm(`⚠️ 财务高危操作警告：\n确认要永久注销发票号码为 [${no}] 的进货专用发票吗？\n注销后，所有被该发票分摊扣减的出口合同开票进度将会全自动调退扣减！`)) {

    // 🎯 笔直调用 Ziggy 销毁路由端点
    router.delete(invoicesroute.destroy(id), {
      preserveScroll: true,
      onSuccess: () => alert('发票已被安全撤销，供应链多商户已完成反向销账。')
    })
  }
}
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="min-h-screen p-8 max-w-7xl mx-auto space-y-6 bg-background text-foreground">
      <div>
        <h1 class="text-2xl font-bold tracking-tight bg-linear-to-r from-primary to-success bg-clip-text text-transparent">增值税专用发票管理中心</h1>
        <p class="text-sm text-muted-foreground mt-1">采用最新的统一 20 位法定数字发票流水规范，支持多订单差额核销挂账</p>
      </div>

      <!-- ================== 核心功能：20位大额发票拆分挂账沙盘 ================== -->
      <Card class="bg-card border-border rounded-2xl shadow-lg shadow-primary/5">
        <CardHeader class="pb-3 flex flex-row items-center justify-between space-y-0 border-b border-border">
          <div class="space-y-1">
            <CardTitle class="text-sm font-bold text-primary">⚡ 20位大额合并发票 跨订单智能分摊核销工作台</CardTitle>
          </div>
          <Button size="sm" variant="outline" class="h-7 border-border bg-background text-foreground"
            @click="addAllocationRow">
            ➕ 追加分摊出口合同
          </Button>
        </CardHeader>
        <CardContent class="space-y-4">

          <!-- 🎯 精简后的第一排表单输入项 -->
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-xs">
            <div class="space-y-1">
              <Label>销售方工厂 (Supplier)</Label>
              <select v-model="form.supplier_id" class="w-full h-8 border border-border bg-background text-foreground focus:ring-2 focus:ring-ring focus:outline-none rounded-md px-2 shadow-sm">
                <option value="" disabled>请选择开票工厂...</option>
                <option v-for="sup in suppliers" :key="sup.id" :value="sup.id">{{ sup.company_name }}</option>
              </select>
            </div>

            <!-- 🎯 合并后唯一的 20 位长输入框格 -->
            <div class="md:col-span-2 space-y-1">
              <Label>统一 20 位数字发票号码 (Invoice No.)</Label>
              <Input v-model="form.invoice_no" maxlength="20" class="h-8 font-mono tracking-wider font-bold border-border bg-background focus-visible:ring-ring rounded-lg"
                placeholder="请输入严格的 20 位数字流水号（如：2633...）" />
              <div v-if="form.errors.invoice_no" class="text-[10px] text-destructive">{{ form.errors.invoice_no }}</div>
            </div>

            <div class="space-y-1"><Label>价税合计含税面额</Label><Input type="number" v-model="form.total_amount"
                class="h-8 font-bold font-mono text-success tracking-wide border-border bg-background focus-visible:ring-ring rounded-lg" placeholder="￥0.00" /></div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-xs pt-1">
            <div class="space-y-1"><Label>开票日期</Label><Input type="date" v-model="form.issue_date" class="h-8 border-border bg-background focus-visible:ring-ring rounded-lg" /></div>
            <!-- 🎯 核心新加：照片或 PDF 凭证拖拽上传插槽 -->
            <div class="space-y-1 md:col-span-2">
              <Label class="text-primary font-bold">📤 上传对应的纸质照片或电子发票 PDF (Max 10MB)</Label>
              <input type="file" accept=".pdf,.jpg,.jpeg,.png" @change="handleFileSelection"
                class="w-full text-xs text-muted-foreground file:mr-4 file:py-1 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-primary/5 file:text-primary hover:file:bg-primary/10 cursor-pointer h-8 pt-1" />
            </div>
          </div>

          <!-- 动态分摊细则行 -->
          <div class="border-t pt-3 space-y-2">
            <div v-for="(alloc, index) in form.allocations" :key="index"
              class="flex gap-3 items-center bg-background p-2 border rounded-lg shadow-sm">
              <div class="w-6 text-center text-xs font-mono text-muted-foreground">#{{ index + 1 }}</div>
              <div class="flex-1">
                <select v-model="alloc.contract_id" class="w-full h-8 border border-border bg-background text-foreground focus:ring-2 focus:ring-ring focus:outline-none rounded-md text-xs px-2">
                  <option value="" disabled>选择需要执行开票核销的目标出口合同号...</option>
                  <option v-for="con in activeContracts" :key="con.id" :value="con.id">
                    合同: {{ con.contract_no }} (发票状态: {{ con.invoice_status }})
                  </option>
                </select>
              </div>
              <div class="w-48"><Input type="number" v-model="alloc.amount" class="h-8 text-xs font-mono border-border bg-background focus-visible:ring-ring rounded-lg"
                  placeholder="分摊面额" /></div>
              <button type="button" class="text-destructive hover:text-destructive font-bold px-1 text-sm"
                @click="removeAllocationRow(index)">✕</button>
            </div>
          </div>

          <!-- 配平底栏 -->
          <div class="border-t pt-3 flex justify-between items-center text-xs">
            <div class="font-mono" :class="isBalanced ? 'text-success font-black tracking-wide' : 'text-destructive'">
              发票面额：<span class="text-success font-black tracking-wide">￥{{ Number(form.total_amount) }}</span> |
              已拆分总计：<span class="text-success font-black tracking-wide">￥{{ currentAllocatedSum.toLocaleString() }}</span>
              <span>({{ isBalanced ? '已配平' : '未配平差额: ￥' + (Number(form.total_amount) - currentAllocatedSum).toFixed(2)
                }})</span>
            </div>
            <Button class="h-8 font-bold text-xs px-6"
              :class="isBalanced ? 'bg-primary hover:bg-primary/90 text-primary-foreground' : 'bg-muted text-muted-foreground cursor-not-allowed'"
              :disabled="!isBalanced || form.processing" @click="submitMultiInvoice">
              确认 20 位专票拆分入账
            </Button>
          </div>
        </CardContent>
      </Card>

      <!-- ================== 列表：已入账的所有 20 位长票台账 ================== -->
      <!-- <Card class="shadow-sm">
        <CardContent class="pt-4">
          <div class="rounded-md border">
            <Table>
              <TableHeader>
                <TableRow class="bg-muted/50 text-xs">
                  <TableHead>开票工厂/发票号码</TableHead>
                  <TableHead class="text-center w-55">影像凭证档案</TableHead>
                  <TableHead class="text-right">价税合计面额</TableHead>
                  <TableHead class="text-right">不含税基数/税额</TableHead>
                  <TableHead>跨订单合并拆摊流向</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow v-for="inv in invoices.data" :key="inv.id" class="hover:bg-muted/50 text-xs">
                  <TableCell>
                    <div class="font-bold text-foreground">{{ inv.supplier?.company_name }}</div>
                    <div class="text-[10px] text-muted-foreground font-mono">号: {{ inv.invoice_no }} | 日期: {{
                      inv.issue_date }}</div>
                  </TableCell>
                  <TableCell class="text-center">
                    
                    <a v-if="inv.file_path" :href="`/storage/${inv.file_path}`" target="_blank"
                      class="inline-flex items-center gap-1 text-xs font-bold text-primary dark:text-blue-400 hover:underline bg-blue-50 dark:bg-blue-950/40 px-2 py-1 rounded border border-blue-100 dark:border-blue-800">
                      📎 查阅原始凭证
                    </a>

                    
                    <div v-else-if="activeUploadingInvoiceId !== inv.id" class="space-y-1">
                      <span class="text-[10px] text-red-400 italic block">⚠️ 缺失影像凭证</span>
                      <Button size="sm" variant="outline"
                        class="h-6 text-[10px] border-amber-200 dark:border-amber-800 text-amber-600 dark:text-amber-400 bg-amber-50/10 hover:bg-amber-50 dark:hover:bg-amber-950/30"
                        @click="startUploadClick(inv.id)">
                        📤 快捷补传发票
                      </Button>
                    </div>

                    
                    <div v-else
                      class="flex items-center gap-1 bg-amber-50/50 dark:bg-amber-950/30 p-1 border border-amber-200 dark:border-amber-800 rounded animate-fadeIn">
                      <input type="file" accept=".pdf,.jpg,.jpeg,.png" @change="handleAddonFileChange"
                        class="w-30 text-[9px] text-muted-foreground file:py-0.5 file:px-1 file:rounded file:border-0 file:text-[9px] file:bg-background cursor-pointer" />
                      <div class="flex gap-1">
                        <Button size="sm" class="h-5 px-1.5 bg-green-600 hover:bg-green-700 text-[9px]"
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
                  <TableCell class="text-right font-mono font-black text-sm text-foreground">￥{{ inv.total_amount }}
                  </TableCell>
                  <TableCell class="text-right font-mono text-muted-foreground">
                    <div>净: ￥{{ inv.tax_exclusive_amount }}</div>
                    <div>税: ￥{{ inv.tax_amount }}</div>
                  </TableCell>
                  <TableCell class="p-2">
                    <div class="flex flex-wrap gap-1.5 max-w-88">
                      <span v-for="alloc in inv.allocations" :key="alloc.id"
                        class="inline-flex items-center gap-1 bg-muted border text-[10px] px-2 py-0.5 rounded font-mono text-foreground">
                        <strong>{{ alloc.contract?.contract_no }}</strong>
                        <span class="text-amber-600 font-bold">➜ ￥{{ alloc.allocated_amount }}</span>
                      </span>
                    </div>
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </div>
        </CardContent>
      </Card> -->
      <Card class="bg-card border-border rounded-2xl shadow-lg shadow-primary/5">
        <CardHeader class="border-b border-border"></CardHeader>
        <CardContent class="pt-4">
          <div class="rounded-xl border border-border bg-card overflow-hidden">
            <Table>
              <TableHeader>
                <TableRow class="bg-primary text-primary-foreground text-xs">
                  <TableHead>开票工厂/开票日期</TableHead>
                  <TableHead>统一 20 位发票号码 (Invoice No.)</TableHead>
                  <TableHead class="text-center w-55">影像凭证档案</TableHead>
                  <TableHead class="text-right">价税合计面额</TableHead>
                  <TableHead>跨订单合并拆摊流向</TableHead>
                  <TableHead class="w-35 text-center font-bold">账目纠错</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow v-for="inv in invoices.data" :key="inv.id" class="hover:bg-muted/50 text-xs">

                  <!-- 状态 A：常规只读模式 -->
                  <template v-if="activeEditingInvoiceId !== inv.id">
                    <TableCell>
                      <div class="font-bold text-foreground">{{ inv.supplier?.company_name }}</div>
                      <div class="text-[10px] text-muted-foreground font-mono">日期: {{ inv.issue_date }}</div>
                    </TableCell>
                    <TableCell>
                      <span class="text-primary font-bold tracking-wider bg-primary/5 rounded-md px-2 py-1 font-mono text-sm">
                        {{ inv.invoice_no }}
                      </span>
                    </TableCell>
                    <!-- 🎯 核心高能区：动态渲染 [查阅原件] 与 [快捷补传表单格] -->
                    <TableCell class="text-center">
                      <!-- 状态 A：如果已经存在附件，直接供调阅查看 -->
                      <a v-if="inv.file_path" :href="`/storage/${inv.file_path}`" target="_blank"
                        class="inline-flex items-center gap-1 text-xs font-bold text-primary hover:underline bg-primary/5 px-2 py-1 rounded border border-primary/10">
                        📎 查阅原始凭证
                      </a>
                      <!-- 状态 B：如果没有附件，且目前没触发点击，展示补传按钮 -->
                      <div v-else-if="activeUploadingInvoiceId !== inv.id" class="space-y-1">
                        <span class="text-[10px] text-destructive italic block">⚠️ 缺失影像凭证</span>
                        <Button size="sm" variant="outline"
                          class="h-6 text-[10px] border-warning text-warning bg-warning/10 hover:bg-warning/20"
                          @click="startUploadClick(inv.id)">
                          📤 快捷补传发票
                        </Button>
                      </div>
                      <!-- 状态 C：点击补传后，当场绽放出文件选择控件与小“对勾”提交按钮 -->
                      <div v-else
                        class="flex items-center gap-1 bg-warning/10 p-1 border border-warning rounded animate-fadeIn">
                        <input type="file" accept=".pdf,.jpg,.jpeg,.png" @change="handleAddonFileChange"
                          class="w-30 text-[9px] text-muted-foreground file:py-0.5 file:px-1 file:rounded file:border-0 file:text-[9px] file:bg-background cursor-pointer" />
                        <div class="flex gap-1">
                          <Button size="sm" class="h-5 px-1.5 bg-success hover:bg-success/80 text-[9px]"
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
                    <TableCell class="text-right font-mono text-success font-black tracking-wide text-sm">
                      ￥{{ inv.total_amount }}
                    </TableCell>
                    <TableCell class="p-2">
                      <div class="flex flex-wrap gap-1.5 max-w-[320px]">
                        <span v-for="alloc in inv.allocations" :key="alloc.id"
                          class="inline-flex items-center gap-1 bg-secondary/40 border-border rounded-md text-[10px] px-2 py-0.5 font-mono text-foreground">
                          <strong>{{ alloc.contract?.contract_no }}</strong>
                          <span class="text-success font-bold">➜ ￥{{ alloc.allocated_amount }}</span>
                        </span>
                      </div>
                    </TableCell>
                    <!-- 只读状态下的操作纠错组 -->
                    <TableCell class="text-center">
                      <div class="flex justify-center gap-3">
                        <Button variant="ghost" size="sm" class="bg-primary text-primary-foreground hover:bg-primary/80 px-2 py-0 text-xs font-bold"
                          @click="startInlineEdit(inv)">更正</Button>
                        <Button variant="ghost" size="sm" class="text-destructive px-2 py-0 text-xs font-bold"
                          @click="triggerInvoiceDestroy(inv.id, inv.invoice_no)">作废</Button>
                      </div>
                    </TableCell>
                  </template>

                  <!-- 🎯 状态 B：点击“更正”后，当前行原地无缝变形为高拟真 Input 编辑输入格 -->
                  <template v-else>
                    <TableCell>
                      <div class="font-bold text-foreground mb-1">{{ inv.supplier?.company_name }}</div>
                      <Input type="date" v-model="editForm.issue_date" class="h-7 text-xs p-1 border-border bg-background focus-visible:ring-ring rounded-lg" />
                    </TableCell>
                    <TableCell>
                      <Input v-model="editForm.invoice_no" maxlength="20"
                        class="h-8 font-mono text-xs text-primary font-bold border-border bg-background focus-visible:ring-ring rounded-lg" />
                    </TableCell>
                    <TableCell class="text-right">
                      <Input type="number" step="0.01" v-model="editForm.total_amount"
                        class="h-8 text-xs font-mono font-bold text-right border-border bg-background focus-visible:ring-ring rounded-lg" />
                    </TableCell>
                    <TableCell class="text-muted-foreground italic text-[11px] bg-muted/50">
                      ⚠️ 更正发票金额将自动重新配平分摊明细
                    </TableCell>
                    <!-- 编辑编辑状态下的保存控制组 -->
                    <TableCell class="text-center">
                      <div class="flex justify-center gap-2">
                        <Button size="sm" class="bg-primary hover:bg-primary/90 text-primary-foreground"
                          @click="submitInvoiceUpdate(inv.id)" :disabled="editForm.processing">保存</Button>
                        <Button variant="ghost" size="sm" class="text-muted-foreground hover:text-foreground text-[10px]"
                          @click="activeEditingInvoiceId = null">取消</Button>
                      </div>
                    </TableCell>
                  </template>

                </TableRow>
              </TableBody>
            </Table>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>