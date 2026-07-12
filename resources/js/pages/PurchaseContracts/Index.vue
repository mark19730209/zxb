<!-- resources/js/Pages/PurchaseContracts/Index.vue [完全体 - 双级级联修改 + 100%原生暗黑模式版] -->
<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import axios from 'axios';
import { ref } from 'vue'
import { toast } from 'vue-sonner' // 🎯 引入高级 Sonner 气泡
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
// import {
//   Select,
//   SelectContent,
//   SelectGroup,
//   SelectItem,
//   SelectTrigger,
//   SelectValue,
// } from '@/components/ui/select'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import AppLayout from '@/layouts/AppLayout.vue';

import { type BreadcrumbItem } from '@/types';
// 假设你使用了 lucide-vue-next 或者其他图标库，如果没有，直接用内置 emoji 即可
// import { Printer } from 'lucide-vue-next'; 

// 辅助函数：根据选中的 ID 实时获取整条商品数据，用于输入框(Trigger)的图片和文字回显
// const getSelectedItem = (itemId: string | number, categoryId: string | number) => {
//   if (!itemId || !categoryId) return null
//   const list = getFilteredItems(categoryId)
//   // 注意：shadcn 的 value 通常转为字符串处理更稳妥，这里做下兼容对比
//   return list.find((sub: any) => String(sub.id) === String(itemId)) || null
// }


const breadcrumbs: BreadcrumbItem[] = [
    { title: 'ERP/TMS' },
    { title: 'Items' },
];
interface CategoryStub { id: number; category_name: string; hs_code: string; unit: string }
interface ItemStub { id: number; category_id: number; sku: string; name_cn: string; purchase_price: string, image_path?: string }

const props = defineProps<{
  purchaseContracts: any
  suppliers: Array<{ id: number, company_name: string }>
  categories: CategoryStub[] 
  items: ItemStub[]           
}>()
console.log('📝 PurchaseContracts/Index.vue props:', props.items)
// =====================================================================
// 📝 核心数字智能净化器（去除无用尾随零、整数不带点、千分位自动优雅对齐）
// =====================================================================
const formatSmartNumber = (val: any) => {
  if (val === undefined || val === null || val === '' || isNaN(val)) return '0';
  const num = Number(val);
  return num.toLocaleString('zh-CN', {
    minimumFractionDigits: 0, // 整数绝不带尾巴，如 200 直接显示 200
    maximumFractionDigits: 4  // 最多保留退税核销精度的 4 位小数
  });
};

// =====================================================================
// 📝 模块 1：新采购合同创建表单
// =====================================================================
const contractForm = useForm({
  supplier_id: '', purchase_contract_no: '', signing_date: new Date().toISOString().split('T')[0], delivery_terms: '',
  items: [{ category_id: '', item_id: '', quantity: '', purchase_price: '', image_path: '' }]
})

const addProductRow = () => { contractForm.items.push({ category_id: '', item_id: '', quantity: '', purchase_price: '', image_path: '' }) }
const removeProductRow = (idx: number) => { contractForm.items.splice(idx, 1) }

const handleCategoryChange = (idx: number) => {
  contractForm.items[idx].item_id = ''
  contractForm.items[idx].purchase_price = ''
}

const handleItemChange = (idx: number) => {
  const currentItemId = contractForm.items[idx].item_id
  const targetItem = props.items.find(i => i.id === Number(currentItemId))
  if (targetItem) {
    // 自动转换：将单价剔除尾零灌入
    contractForm.items[idx].purchase_price = targetItem.purchase_price ? String(Number(targetItem.purchase_price)) : '0'
    contractForm.items[idx].image_path = targetItem.image_path ? String(targetItem.image_path) : ''
  }
}

const submitContract = () => {
  contractForm.post('/purchase-contracts/store', {
    preserveScroll: true,
    onSuccess: () => { contractForm.reset(); toast.success('✔️ 国内采购买卖合同签署封存成功！') }
  })
}

// =====================================================================
// 🛠️ 模块 2：行内级联编辑器表单
// =====================================================================
const editForm = useForm({
  id: '' as number | string, supplier_id: '', purchase_contract_no: '', signing_date: '', delivery_terms: '',
  items: [] as Array<{ id: number | null, category_id: string | number, item_id: string | number, quantity: number | string, purchase_price: number | string, image_path?: string }>
})

const activeEditingContractId = ref<number | null>(null)

const startInlineEdit = (pc: any) => {
  activeEditingContractId.value = pc.id
  editForm.id = pc.id
  editForm.supplier_id = pc.supplier_id
  editForm.purchase_contract_no = pc.purchase_contract_no
  editForm.signing_date = pc.signing_date
  editForm.delivery_terms = pc.delivery_terms || ''
  
  editForm.items = pc.purchase_contract_items ? pc.purchase_contract_items.map((i: any) => {
    const matchedItem = props.items.find(item => item.id === i.item_id)
    return {
      id: i.id,
      category_id: matchedItem ? matchedItem.category_id : '',
      item_id: i.item_id,
      quantity: i.quantity ? Number(i.quantity) : '', // 强制过滤尾随零
      purchase_price: i.purchase_price ? Number(i.purchase_price) : '', // 强制过滤尾随零
      image_path: matchedItem ? matchedItem.image_path : undefined
    }
  }) : [{ id: null, category_id: '', item_id: '', quantity: '', purchase_price: '', image_path: undefined }]
}

const addEditProductRow = () => { editForm.items.push({ id: null, category_id: '', item_id: '', quantity: '', purchase_price: '', image_path: undefined }) }
const removeEditProductRow = (idx: number) => { editForm.items.splice(idx, 1) }

const handleEditCategoryChange = (idx: number) => {
  editForm.items[idx].item_id = ''
  editForm.items[idx].purchase_price = ''
}

const handleEditItemChange = (idx: number) => {
  const currentItemId = editForm.items[idx].item_id
  const targetItem = props.items.find(i => i.id === Number(currentItemId))
  if (targetItem) {
    editForm.items[idx].purchase_price = targetItem.purchase_price ? String(Number(targetItem.purchase_price)) : '0'
    editForm.items[idx].image_path = targetItem.image_path || undefined
  }
}

const submitContractUpdate = (id: number) => {
  editForm.put(`/purchase-contracts/${id}`, {
    preserveScroll: true,
    onSuccess: () => { activeEditingContractId.value = null; toast.success('✔️ 台账数据原地重配平！') }
  })
}

const exportPdf = (id: number) => {
  // 直接在新窗口打开这个 GET 链接，浏览器会自动识别并弹出下载，完全不影响当前页面
  window.open(`/purchase-contracts/${id}/exportPdf`, '_blank');
}

// =====================================================================
// 🧾 模块 3：20位专票挂账核销表单
// =====================================================================
const invoiceForm = useForm({
  purchase_contract_id: '' as number | string, invoice_no: '', issue_date: new Date().toISOString().split('T')[0], total_amount: '', tax_rate: '13'
})
const activeContractNoForInvoice = ref('')
const openInvoiceModal = (id: number, no: string) => { invoiceForm.purchase_contract_id = id; activeContractNoForInvoice.value = no }
const submitInvoiceLink = () => {
  invoiceForm.post('/purchase-contracts/link-invoice', {
    preserveScroll: true,
    onSuccess: () => { invoiceForm.reset('invoice_no', 'total_amount'); activeContractNoForInvoice.value = ''; toast.success('✔️ 发票已核销对齐！') }
  })
}

const getFilteredItems = (categoryId: string | number) => {
  if (!categoryId) return []
  return props.items.filter(item => item.category_id === Number(categoryId))
}

// 💡 核心新增：纯前端异步横向 PDF 导出方法
const isExportingPdf = ref<number | null>(null); // 用于控制单独某一行的 Loading 状态

const exportContractPdf = async (id: number, contractNo: string) => {
  if (isExportingPdf.value !== null) return;
  isExportingPdf.value = id;
  
  try {
    // 🎯 绕过 Inertia.js 路由，通过原始 axios 索要二进制 Blob 大对象
    const response = await axios.post(`/purchase-contracts/${id}/exportPdf`, {}, {
      responseType: 'blob' 
    });

    // 1. 将接收到的二进制流打包为 PDF 类型
    const blob = new Blob([response.data], { type: 'application/pdf' });
    const url = window.URL.createObjectURL(blob);

    // 2. 虚拟一个 HTML5 <a> 标签并模拟点击，触发浏览器的原生下载窗口
    const link = document.createElement('a');
    link.href = url;
    // 使用合同编号作为文件名，非常适合外贸台账归档管理
    link.setAttribute('download', `购销合同_${contractNo}.pdf`); 
    document.body.appendChild(link);
    link.click();

    // 3. 及时清理释放内存与临时 DOM 节点
    link.remove();
    window.URL.revokeObjectURL(url);
  } catch (error) {
    console.error('采购合同 PDF 导出失败:', error);
    alert('PDF 导出失败，请检查后端 Puppeteer 及 Node 全局环境配置！');
  } finally {
    isExportingPdf.value = null;
  }
}
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
  <!-- 优化：增加 text-foreground 与过渡动画，确保全局文字在暗黑模式下正确显示 -->
  <div class="p-8 max-w-7xl space-y-6 bg-background text-foreground transition-colors duration-300">
    <div>
      <!-- 优化：渐变色微调，引入暗黑模式适配色 emerald-400 -->
      <h1 class="text-2xl font-bold tracking-tight bg-linear-to-r from-primary to-success dark:to-emerald-400 bg-clip-text text-transparent">
        国内供货工厂合约与票流管控中心
      </h1>
      <p class="text-sm text-muted-foreground">管控国内供应链人民币买卖合同，级联扣减工厂20位发票，确保退税“三流一致”</p>
    </div>

    <!-- ================== 1. 快捷起草国内采购合同大盘 ================== -->
    <!-- 优化：去除 border-slate-200，改用语义化 border-border 与暗黑无阴影设计 -->
    <Card class="border-border bg-card shadow-sm dark:shadow-none">
      <!-- 优化：将 bg-slate-50/50 改为 bg-muted/40，文本改为 text-card-foreground -->
      <CardHeader class="pb-3 bg-muted/40 border-b border-border">
        <CardTitle class="text-xs font-bold text-foreground tracking-wider">📝 签署国内工厂采购买卖合同</CardTitle>
      </CardHeader>
      <CardContent class="pt-4 space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-xs">
          <div class="space-y-1">
            <Label>销售方国内生产工厂</Label>
            <!-- 优化：彻底重构原生 select 的背景与边框，防止暗黑模式下变成白底白字 -->
            <select v-model="contractForm.supplier_id" class="w-full h-8 rounded border border-border bg-background text-foreground px-2 focus:outline-none focus:ring-1 focus:ring-ring">
              <option value="" disabled class="bg-background text-muted-foreground">请选择供货工厂...</option>
              <option v-for="sup in suppliers" :key="sup.id" :value="sup.id" class="bg-background text-foreground">{{ sup.company_name }}</option>
            </select>
          </div>
          <div class="space-y-1">
            <Label>内部采购合同号 (P.C. No.)</Label>
            <Input v-model="contractForm.purchase_contract_no" placeholder="如: CG-2026-001" class="h-8 font-mono font-bold" />
          </div>
          <div class="space-y-1">
            <Label>国内签署日期</Label>
            <!-- 优化：确保原生 date 控件在暗黑模式下的反转色正常 -->
            <Input type="date" v-model="contractForm.signing_date" class="h-8 dark:scheme:dark" />
          </div>
        </div>

        <!-- 🎯 完善后的海关品类 ➜ 款式货号 双级联动动态货品行 -->
        <div class="space-y-2 border-t border-border pt-3">
          <div class="flex justify-between items-center">
            <span class="text-[11px] font-bold text-muted-foreground">📦 并入合同采购大货品项（海关大类 ➔ 款式级联）：</span>
            <Button size="sm" variant="outline" class="h-6 border-border text-foreground hover:bg-muted" @click="addProductRow">
              ➕ 增加采购品项
            </Button>
          </div>
          
          <div v-for="(item, idx) in contractForm.items" :key="idx" class="flex flex-col md:flex-row gap-3 items-center bg-muted/30 p-2.5 border border-border rounded-xl shadow-2xs animate-fadeIn">
            <div class="w-6 text-center text-xs font-mono text-muted-foreground font-bold">#{{ idx + 1 }}</div>
            
            <!-- 🎯 【第一级级联】选择海关 H.S. 大类品类 -->
            <div class="flex-1 w-full md:w-auto">
              <select 
                v-model="item.category_id" 
                @change="handleCategoryChange(idx)"
                class="w-full h-8 rounded border border-border text-xs px-2 focus:border-primary shadow-2xs font-semibold"
              >
                <option value="" disabled>1. 选择海关大类品类...</option>
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                  {{ cat.category_name }} ({{ cat.hs_code }})
                </option>
              </select>
            </div>

            <!-- 🎯 【第二级级联】动态条件过滤，只弹出属于该大类名下的具体款式 SKU -->
            <div class="flex-1 w-full md:w-auto">
              <select 
                v-model="item.item_id" 
                @change="handleItemChange(idx)"
                :disabled="!item.category_id"
                class="w-full h-8 rounded border border-border text-xs px-2 focus:border-primary disabled:opacity-50 disabled:bg-muted font-bold text-muted-foreground shadow-2xs"
              >
                <option value="" disabled>
                  {{ item.category_id ? '2. 选择具体款式 SKU货号...' : '⚡ 请先选择左侧大类...' }}
                </option>
                <option v-for="subItem in getFilteredItems(item.category_id)" :key="subItem.id" :value="subItem.id">
                  款号: {{ subItem.sku }} | {{ subItem.name_cn }}
                </option>
              </select>
            </div>

            <!-- 采购数量及含税人民币进价修改项 -->
            <div class="w-full md:w-28 flex items-center gap-1">
              <Input type="number" v-model="item.quantity" placeholder="数量" class="h-8 text-xs font-mono bg-white text-right" />
              <!-- 自动穿透透视第一计量单位作为后缀 -->
              <span class="text-[10px] text-muted-foreground font-medium select-none w-6">
                {{ categories.find(c => c.id === Number(item.category_id))?.unit || '' }}
              </span>
            </div>
            
            <div class="w-full md:w-32 relative">
              <span class="absolute left-2 top-2 text-[10px] text-muted-foreground font-bold select-none">￥</span>
              <Input type="number" step="0.01" v-model="item.purchase_price" placeholder="下单单价" class="h-8 text-xs font-mono font-black text-muted-foreground pl-5 text-right border-amber-200 focus:border-amber-500" />
            </div>
      <div v-if="item.image_path" >
        <img :src="`/storage/${item.image_path}`" class="size-8 object-cover rounded-sm" />
      </div>
            <button type="button" class="text-red-400 hover:text-red-600 font-bold px-1 text-xs self-center" @click="removeProductRow(idx)">✕</button>
          </div>
        </div>

        <div class="flex justify-end pt-2 border-t border-border">
          <!-- 优化：将原 bg-slate-900 升级为自适应的 bg-primary 和 text-primary-foreground -->
          <Button size="sm" class="bg-primary text-primary-foreground font-bold h-8 px-6 hover:brightness-110 transition-all" @click="submitContract">确认签署采购合约</Button>
        </div>
      </CardContent>
    </Card>

    <!-- ================== 2. 核心弹出式：20位专票笔直冲抵核销控制卡 ================== -->
    <!-- 优化：重塑琥珀色警告框，确保暗黑模式下带有深色透明感底色 (dark:bg-amber-950/20) 与高亮文本 -->
    <Card v-if="activeContractNoForInvoice" class="border-amber-200 dark:border-amber-900/60 bg-amber-50/30 dark:bg-amber-950/20 animate-fadeIn">
      <CardHeader class="pb-2 bg-amber-50/20 dark:bg-amber-950/10 border-b border-amber-200/60 dark:border-amber-900/40">
        <CardTitle class="text-xs font-bold text-amber-900 dark:text-amber-400">⚡ 针对采购合同 [{{ activeContractNoForInvoice }}] 录入专用发票核销挂账</CardTitle>
      </CardHeader>
      <CardContent class="pt-4 grid grid-cols-1 md:grid-cols-4 gap-3 items-end text-xs">
        <div class="space-y-1">
          <Label class="text-amber-900 dark:text-amber-300">统一 20位 纯数字发票号码</Label>
          <Input v-model="invoiceForm.invoice_no" maxlength="20" class="h-8 font-mono font-bold tracking-wider border-amber-200 dark:border-amber-900 bg-background" placeholder="2633..." />
        </div>
        <div class="space-y-1">
          <Label class="text-amber-900 dark:text-amber-300">价税合计含税面额 (RMB)</Label>
          <Input type="number" v-model="invoiceForm.total_amount" class="h-8 font-mono font-bold border-amber-200 dark:border-amber-900 bg-background text-foreground" placeholder="￥0.00" />
        </div>
        <div class="space-y-1">
          <Label class="text-amber-900 dark:text-amber-300">开票日期</Label>
          <Input type="date" v-model="invoiceForm.issue_date" class="h-8 border-amber-200 dark:border-amber-900 bg-background dark:scheme:dark" />
        </div>
        <div class="flex gap-2">
          <Button size="sm" class="bg-amber-600 hover:bg-amber-500 text-white font-bold h-8 flex-1" @click="submitInvoiceLink">确认发票对齐核销</Button>
          <Button size="sm" variant="ghost" class="h-8 text-amber-900 dark:text-amber-400 hover:bg-amber-100 dark:hover:bg-amber-950/50" @click="activeContractNoForInvoice = ''">取消</Button>
        </div>
      </CardContent>
    </Card>
      <!-- ================== 3. 国内采购买卖合同大台账清单 ================== -->
    <Card class="border-border bg-card shadow-sm dark:shadow-none">
        <CardHeader class="pb-2"><CardTitle class="text-base font-bold">国内工厂采购合约台账总盘</CardTitle></CardHeader>
        <CardContent>
          <div class="rounded-xl border overflow-hidden bg-card border-border">
            <Table>
              <TableHeader>
                <TableRow class="bg-muted/60 border-b border-border">
                  <TableHead class="text-foreground font-bold">采购合同号 / 签约日期</TableHead>
                  <TableHead class="text-foreground font-bold">国内生产厂家</TableHead>
                  <TableHead class="text-right text-foreground font-bold">合同含税总额</TableHead>
                  <TableHead class="text-right text-foreground font-bold">已收发票面额</TableHead>
                  <TableHead class="text-center text-foreground font-bold">专票交收进度</TableHead>
                  <TableHead class="w-40 text-center text-foreground font-bold">纠错与过账</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <template v-for="pc in purchaseContracts.data" :key="pc.id">
                  
                  <!-- ━━━ 🔹 状态一：常规只读数据行 🔹 ━━━ -->
                  <TableRow v-if="activeEditingContractId !== pc.id" class="hover:bg-muted/40 border-b border-border last:border-0 text-xs font-medium transition-colors">
                    <TableCell>
                      <div class="font-bold text-foreground font-mono tracking-wide">{{ pc.purchase_contract_no }}</div>
                      <div class="text-[10px] text-muted-foreground font-mono">{{ pc.signing_date }}</div>
                    </TableCell>
                    <TableCell class="font-bold text-foreground/90">{{ pc.supplier?.company_name }}</TableCell>
                    <!-- <TableCell class="text-right font-mono font-bold text-foreground text-sm">￥{{ parseFloat(pc.total_rmb_amount).toLocaleString() }}</TableCell>
                    <TableCell class="text-right font-mono font-bold text-blue-600 dark:text-blue-400">￥{{ parseFloat(pc.received_amount || 0).toLocaleString() }}</TableCell> -->
                    <!-- 🎯 使用优化后的净化格式化方法展示完美数字 -->
                    <TableCell class="text-right font-mono font-bold text-foreground text-sm">￥{{ formatSmartNumber(pc.total_rmb_amount) }}</TableCell>
                    <TableCell class="text-right font-mono font-bold text-blue-600 dark:text-blue-400">￥{{ formatSmartNumber(pc.received_amount || 0) }}</TableCell>
       
                    <TableCell class="text-center">
                      <Badge v-if="pc.invoice_status === 'none'" variant="secondary" class="bg-red-50 dark:bg-red-950/30 text-red-600 dark:text-red-400 border-red-100 dark:border-red-900/40">未交票</Badge>
                      <Badge v-else-if="pc.invoice_status === 'partial'" variant="secondary" class="bg-amber-50 dark:bg-amber-950/30 text-amber-600 dark:text-amber-400 border-amber-100 dark:border-amber-900/40">分批收票中</Badge>
                      <Badge v-else variant="secondary" class="bg-green-50 dark:bg-emerald-950/30 text-green-600 dark:text-emerald-400 border-green-100 dark:border-emerald-900/40">专票全收齐</Badge>
                    </TableCell>
                    <!-- <TableCell class="text-center">
                      <div class="flex justify-center gap-2.5">
                        <button type="button" class="text-primary hover:text-primary/80 transition-colors hover:underline font-bold" @click="startInlineEdit(pc)">更正</button>
                        <Button size="sm" variant="outline" class="h-6 text-[10px] border-amber-200 dark:border-amber-900/60 text-amber-700 dark:text-amber-400 bg-amber-50/20 dark:bg-amber-950/10 hover:bg-amber-100 dark:hover:bg-amber-950/40" @click="openInvoiceModal(pc.id, pc.purchase_contract_no)">📥 收单过账</Button>
                      </div>
                    </TableCell> -->
                        <TableCell class="text-center">
      <div class="flex justify-center items-center gap-2.5">
        <!-- 1. 更正操作（保留原逻辑） -->
        <button type="button" class="text-primary hover:text-primary/80 transition-colors hover:underline font-bold" @click="startInlineEdit(pc)">更正</button>
        
        <!-- 2. 🎯 核心新增：横向高保真 PDF 导出打印按钮 -->
  <!-- 💡 按钮分流方案：如果后期发现字段有路径，说明之前生成过，我们可以直接通过 <a> 标签秒级下载历史文件，完全不卡顿 -->
  <a 
    v-if="pc.pdf_path"
    :href="`/purchase-contracts/${pc.id}/downloadArchived`"
    target="_blank"
    class="inline-flex items-center justify-center h-6 px-2 text-[10px] font-bold rounded border border-emerald-200 dark:border-emerald-900 text-emerald-700 dark:text-emerald-400 bg-emerald-50/50 dark:bg-emerald-950/20 hover:bg-emerald-100"
  >
    📂 下载历史存档
  </a>

  <!-- 如果此合同是从未打印过的新合同，则显示之前的实时生成按钮 -->
  <Button 
    v-else
    size="sm" 
    variant="outline" 
    :disabled="isExportingPdf === pc.id"
    class="h-6 text-[10px] border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 bg-slate-50/50 dark:bg-slate-900/50 hover:bg-slate-100 dark:hover:bg-slate-800"
    @click="exportContractPdf(pc.id, pc.purchase_contract_no)"
  >
    <span v-if="isExportingPdf === pc.id" class="animate-spin mr-1">⌛</span>
    <span v-else class="mr-1">🖨️</span>
    生成横向PDF
  </Button>

        <!-- 3. 收单过账（保留原逻辑） -->
        <Button size="sm" variant="outline" class="h-6 text-[10px] border-amber-200 dark:border-amber-900/60 text-amber-700 dark:text-amber-400 bg-amber-50/20 dark:bg-amber-950/10 hover:bg-amber-100 dark:hover:bg-amber-950/40" @click="openInvoiceModal(pc.id, pc.purchase_contract_no)">📥 收单过账</Button>
      </div>
    </TableCell>
                  </TableRow>

                  <!-- ━━━ 🔸 状态二：点击更正后，整行无缝膨胀变形为级联表单格 🔸 ━━━ -->
                  <!-- 优化：调整高亮边框和展开行的阴影，使其在暗黑模式下呈现柔和的蓝色半透明辉光 -->
                  <TableRow v-else class="bg-blue-50/10 dark:bg-blue-950/5 border-2 border-blue-400/50 dark:border-blue-500/40 animate-fadeIn text-xs">
                    <!-- 优化：将原本强制变白的 bg-white 替换为语义化的 bg-card，暗黑模式下自动使用合理的暗色 -->
                    <TableCell colspan="6" class="p-4 bg-card border-b border-border">
                      <div class="space-y-4">
                        <div class="flex justify-between items-center border-b border-dashed border-border pb-2">
                          <!-- 优化：微调文本颜色，使其在暗黑模式下变更为高对比度的蓝色（blue-400） -->
                          <span class="font-black text-blue-800 dark:text-blue-400 text-xs">🛠️ 正在行内更正采购合同明细及价格：</span>
                          <Button size="sm" variant="outline" class="h-6 border-blue-300 dark:border-blue-800 text-blue-700 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-950/30" @click="addEditProductRow">
                            ➕ 补加货品行
                          </Button>
                        </div>

                        <!-- 1. 表头条款微调网格 -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-[11px]">
                          <div class="space-y-1"><Label>合同编号</Label><Input v-model="editForm.purchase_contract_no" class="h-7 text-xs font-mono font-bold" /></div>
                          <!-- 优化：原生的日期输入框添加 color-scheme 控制，使暗黑模式下的日期选择组件反色正常 -->
                          <div class="space-y-1"><Label>签约日期</Label><Input type="date" v-model="editForm.signing_date" class="h-7 text-xs dark:scheme:dark" /></div>
                          <div class="space-y-1">
                            <Label>指定生产工厂</Label>
                            <!-- 优化：彻底重构下拉菜单，防止在暗黑模式下出现“惨白背景/白字”问题 -->
                            <select v-model="editForm.supplier_id" class="w-full h-7 rounded border border-border text-xs bg-background text-foreground px-2 focus:outline-none focus:ring-1 focus:ring-ring">
                              <option v-for="sup in suppliers" :key="sup.id" :value="sup.id" class="bg-background text-foreground">{{ sup.company_name }}</option>
                            </select>
                          </div>
                        </div>

                        <!-- 2. 子货品明细及下单采购进价深度覆写格 -->
                        <div class="space-y-2 border-t border-border pt-3">
                          <span class="text-[10px] font-bold text-muted-foreground block mb-1">📋 款式明细件数价格微调（系统自动动态轧算总额）：</span>
   
          <div v-for="(item, idx) in editForm.items" :key="idx" class="flex flex-col md:flex-row gap-3 items-center bg-muted/30 p-2.5 border border-border rounded-xl shadow-2xs animate-fadeIn">
            <div class="w-6 text-center text-xs font-mono text-muted-foreground font-bold">#{{ idx + 1 }}</div>
            
            <!-- 🎯 【第一级级联】选择海关 H.S. 大类品类 -->
            <div class="flex-1 w-full max-w-50 md:w-auto">
              <select 
                v-model="item.category_id" 
                @change="handleEditCategoryChange(idx)"
                class="w-full h-8 rounded border border-border text-xs px-2 focus:border-primary shadow-2xs font-semibold"
              >
                <option value="" disabled>1. 选择海关大类品类...</option>
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                  {{ cat.category_name }} ({{ cat.hs_code }})
                </option>
              </select>
            </div>

            <!-- 🎯 【第二级级联】动态条件过滤，只弹出属于该大类名下的具体款式 SKU -->
            <div class="flex-1 w-full md:w-auto">
              <select 
                v-model="item.item_id" 
                @change="handleEditItemChange(idx)"
                :disabled="!item.category_id"
                class="w-full h-8 rounded border border-border text-xs px-2 focus:border-primary disabled:opacity-50 disabled:bg-muted font-bold text-muted-foreground shadow-2xs"
              >
                <option value="" disabled>
                  {{ item.category_id ? '2. 选择具体款式 SKU货号...' : '⚡ 请先选择左侧大类...' }}
                </option>
                <option v-for="subItem in getFilteredItems(item.category_id)" :key="subItem.id" :value="subItem.id">
                  款号:{{ subItem.sku }} | {{ subItem.name_cn }} 
                </option>
              </select>
            </div>

            <!-- 采购数量及含税人民币进价修改项 -->
            <div class="w-full md:w-28 flex items-center gap-1">
              <Input type="number" v-model="item.quantity" placeholder="数量" class="h-8 text-xs font-mono bg-white text-right" />
              <!-- 自动穿透透视第一计量单位作为后缀 -->
              <span class="text-[10px] text-muted-foreground font-medium select-none w-6">
                {{ categories.find(c => c.id === Number(item.category_id))?.unit || '' }}
              </span>
            </div>
            
            <div class="w-full md:w-32 relative">
              <span class="absolute left-2 top-2 text-[10px] text-muted-foreground font-bold select-none">￥</span>
              <Input type="number" step="0.01" v-model="item.purchase_price" placeholder="下单单价" class="h-8 text-xs font-mono font-black text-muted-foreground pl-5 text-right border-amber-200 focus:border-amber-500" />
            </div>
      <div v-if="item.image_path" >
        <img :src="`/storage/${item.image_path}`" class="size-8 object-cover rounded-sm" />
      </div>
            <button type="button" class="text-red-400 hover:text-red-600 font-bold px-1 text-xs self-center" @click="removeEditProductRow(idx)">✕</button>
          </div>
                        </div>

                        <!-- 3. 编辑保存控制底栏 -->
                        <div class="border-t border-border pt-3 flex justify-end gap-2">
                          <Button size="sm" variant="ghost" class="h-7 text-muted-foreground hover:bg-muted text-[11px]" @click="activeEditingContractId = null">放弃取消</Button>
                          <!-- 优化：采用更适合暗黑模式的高饱和度蓝色背景 bg-blue-600 dark:bg-blue-500，确保可读性与交互感 -->
                          <Button size="sm" class="h-7 bg-blue-600 dark:bg-blue-500 hover:bg-blue-700 dark:hover:bg-blue-600 text-white font-bold text-[11px] px-4 transition-all" @click="submitContractUpdate(pc.id)" :disabled="editForm.processing">
                            {{ editForm.processing ? '正在轧算中...' : '保存更新并配平大盘' }}
                          </Button>
                          <Button size="sm" variant="ghost" class="h-7 font-bold text-[11px] text-muted-foreground hover:bg-muted" @click="exportPdf(pc.id)" >PDF</Button>
                        </div>
                      </div>
                    </TableCell>
                  </TableRow>

                </template>
              </TableBody>
            </Table>
          </div>
        </CardContent>
      </Card>

  </div>
  </AppLayout>
</template>

