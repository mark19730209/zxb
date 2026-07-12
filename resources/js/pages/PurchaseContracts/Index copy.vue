<!-- resources/js/Pages/PurchaseContracts/Index.vue -->
<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import { toast } from 'vue-sonner' // 🎯 引入高级 Sonner 气泡
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import AppLayout from '@/layouts/AppLayout.vue';

import { type BreadcrumbItem } from '@/types';


const breadcrumbs: BreadcrumbItem[] = [
    { title: 'ERP/TMS' },
    { title: 'Items' },
];
// resources/js/Pages/PurchaseContracts/Index.vue 顶部

interface CategoryStub {
  id: number
  category_name: string
  hs_code: string
  unit: string
}

interface ItemStub {
  id: number
  category_id: number
  sku: string
  name_cn: string
  purchase_price: string
}

const props = defineProps<{
  purchaseContracts: any
  suppliers: Array<{ id: number, company_name: string }>
  categories: CategoryStub[] // 🎯 强类型接收第一级：海关大类
  items: ItemStub[]           // 🎯 强类型接收第二级：具体款式 SKU
}>()

// 1. 重构【新增采购合同】的表单初始化对象，每一行追加 category_id 追踪
const contractForm = useForm({
  supplier_id: '',
  purchase_contract_no: '',
  signing_date: new Date().toISOString().split('T')[0],
  delivery_terms: '',
  items: [
    { category_id: '', item_id: '', quantity: '', purchase_price: '' } // 🎯 默认首行插槽
  ]
})

const addProductRow = () => { 
  contractForm.items.push({ category_id: '', item_id: '', quantity: '', purchase_price: '' }) 
}

// 2. 🎯 级联核心算力：第一级大类改变时，自动清空旧的第二级商品并做好准备
const handleCategoryChange = (idx: number) => {
  contractForm.items[idx].item_id = ''
  contractForm.items[idx].purchase_price = ''
}

// 3. 🎯 级联核心算力：第二级商品改变时，自动将款式库里的预估人民币采购进价拽过来作为初值
const handleItemChange = (idx: number) => {
  const currentItemId = contractForm.items[idx].item_id
  const targetItem = props.items.find(i => i.id === Number(currentItemId))
  if (targetItem) {
    contractForm.items[idx].purchase_price = targetItem.purchase_price || '0.00'
  }
}

// 4. 🎯 核心高能：动态过滤器——实时根据当前行选中的大类，过滤出属于它的具体款式 SKU
const getFilteredItems = (categoryId: string | number) => {
  if (!categoryId) return []
  return props.items.filter(item => item.category_id === Number(categoryId))
}

// // 1. 初始化采购合同创建表单
// const contractForm = useForm({
//   supplier_id: '', purchase_contract_no: '', signing_date: new Date().toISOString().split('T')[0], delivery_terms: '',
//   items: [{ item_id: '', quantity: '', purchase_price: '' }]
// })

// const addProductRow = () => { contractForm.items.push({ item_id: '', quantity: '', purchase_price: '' }) }
const removeProductRow = (idx: number) => { contractForm.items.splice(idx, 1) }

const submitContract = () => {
  contractForm.post('/purchase-contracts/store', {
    preserveScroll: true,
    onSuccess: () => {
      contractForm.reset()
      toast.success('✔️ 国内采购买卖合同签署封存成功！')
    }
  })
}

// 2. 初始化发票快捷对齐核销表单
const invoiceForm = useForm({
  purchase_contract_id: '' as number | string,
  invoice_no: '', issue_date: new Date().toISOString().split('T')[0], total_amount: '', tax_rate: '13'
})

const activeContractNoForInvoice = ref('')

const openInvoiceModal = (id: number, no: string) => {
  invoiceForm.purchase_contract_id = id
  activeContractNoForInvoice.value = no
}

const submitInvoiceLink = () => {
  invoiceForm.post('/purchase-contracts/link-invoice', {
    preserveScroll: true,
    onSuccess: () => {
      invoiceForm.reset('invoice_no', 'total_amount')
      activeContractNoForInvoice.value = ''
      toast.success('✔️ 20位工厂专票已笔直对齐核销合同！')
    }
  })
}

// 🎯 1. 声明用于行内编辑修改采购合同的独立响应式表单
const editForm = useForm({
  id: '' as number | string,
  supplier_id: '',
  purchase_contract_no: '',
  signing_date: '',
  delivery_terms: '',
  items: [] as Array<{ id: number | null, item_id: string | number, quantity: number | string, purchase_price: number | string }>
})

// 控制当前哪一个采购合同正处于“编辑变形状态”的响应式 ID
const activeEditingContractId = ref<number | null>(null)

// 🎯 点击“更正”按钮，瞬间跨表抓取当前合同的主子树快照，注入编辑表单
const startInlineEdit = (pc: any) => {
  console.log('🛠️ 开始行内更正采购合同：', pc)
  activeEditingContractId.value = pc.id
  editForm.id = pc.id
  editForm.supplier_id = pc.supplier_id
  editForm.purchase_contract_no = pc.purchase_contract_no
  editForm.signing_date = pc.signing_date
  editForm.delivery_terms = pc.delivery_terms || ''
  
  // 注入已有的货物明细行，若数据库有 items 关联，在此加载。
  // 此处假设后端已经通过 with('purchaseContractItems') 捎带出来
  editForm.items = pc.purchase_contract_items ? pc.purchase_contract_items.map((i: any) => ({
    id: i.id,
    item_id: i.item_id,
    quantity: i.quantity,
    purchase_price: i.purchase_price
  })) : [{ id: null, item_id: '', quantity: '', purchase_price: '' }]
}

const addEditProductRow = () => { editForm.items.push({ id: null, item_id: '', quantity: '', purchase_price: '' }) }
const removeEditProductRow = (idx: number) => { editForm.items.splice(idx, 1) }

// 🎯 2. 提交采购合同的全量行内更正
const submitContractUpdate = (id: number) => {
  // 笔直投递给 Ziggy 别名端点
  editForm.put(`/purchase-contracts/${id}`, {
    preserveScroll: true,
    onSuccess: () => {
      activeEditingContractId.value = null
      toast.success('✔️ 成功：国内采购合同及货品采购额已原地重新更正配平！')
    }
  })
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
                    <TableCell class="text-right font-mono font-bold text-foreground text-sm">￥{{ parseFloat(pc.total_rmb_amount).toLocaleString() }}</TableCell>
                    <TableCell class="text-right font-mono font-bold text-blue-600 dark:text-blue-400">￥{{ parseFloat(pc.received_amount || 0).toLocaleString() }}</TableCell>
                    
                    <TableCell class="text-center">
                      <Badge v-if="pc.invoice_status === 'none'" variant="secondary" class="bg-red-50 dark:bg-red-950/30 text-red-600 dark:text-red-400 border-red-100 dark:border-red-900/40">未交票</Badge>
                      <Badge v-else-if="pc.invoice_status === 'partial'" variant="secondary" class="bg-amber-50 dark:bg-amber-950/30 text-amber-600 dark:text-amber-400 border-amber-100 dark:border-amber-900/40">分批收票中</Badge>
                      <Badge v-else variant="secondary" class="bg-green-50 dark:bg-emerald-950/30 text-green-600 dark:text-emerald-400 border-green-100 dark:border-emerald-900/40">专票全收齐</Badge>
                    </TableCell>
                    <TableCell class="text-center">
                      <div class="flex justify-center gap-2.5">
                        <button type="button" class="text-primary hover:text-primary/80 transition-colors hover:underline font-bold" @click="startInlineEdit(pc)">更正</button>
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
                          <!-- 优化：将子货品容器从 bg-slate-50 改为通透的 bg-muted/40 -->
                          <div v-for="(subItem, subIdx) in editForm.items" :key="subIdx" class="flex gap-2 items-center bg-muted/40 p-2 border border-border rounded-lg shadow-2xs">
                            <div class="w-5 text-center font-mono text-muted-foreground text-[10px]">#{{ subIdx + 1 }}</div>
                            <div class="flex-1"><Input v-model="subItem.item_id" placeholder="货品款式 ID" class="h-7 text-xs" /></div>
                            <div class="w-28"><Input type="number" step="0.0001" v-model="subItem.quantity" placeholder="采购数量" class="h-7 text-xs font-mono" /></div>
                            <div class="w-32"><Input type="number" step="0.01" v-model="subItem.purchase_price" placeholder="实际下单价(RMB)" class="h-7 text-xs font-mono font-bold text-right" /></div>
                            <button type="button" class="text-destructive hover:opacity-80 font-bold px-1 text-xs transition-opacity" @click="removeEditProductRow(subIdx)">✕</button>
                          </div>
                        </div>

                        <!-- 3. 编辑保存控制底栏 -->
                        <div class="border-t border-border pt-3 flex justify-end gap-2">
                          <Button size="sm" variant="ghost" class="h-7 text-muted-foreground hover:bg-muted text-[11px]" @click="activeEditingContractId = null">放弃取消</Button>
                          <!-- 优化：采用更适合暗黑模式的高饱和度蓝色背景 bg-blue-600 dark:bg-blue-500，确保可读性与交互感 -->
                          <Button size="sm" class="h-7 bg-blue-600 dark:bg-blue-500 hover:bg-blue-700 dark:hover:bg-blue-600 text-white font-bold text-[11px] px-4 transition-all" @click="submitContractUpdate(pc.id)" :disabled="editForm.processing">
                            {{ editForm.processing ? '正在轧算中...' : '保存更新并配平大盘' }}
                          </Button>
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

