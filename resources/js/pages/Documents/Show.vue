<script setup lang="ts">
import { router, useForm, Link } from '@inertiajs/vue3'
// import { ref } from 'vue'
// import { computed } from 'vue'
import { toast } from 'vue-sonner' // 🎯 直接引入 vue-sonner 驱动器
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectLabel,
  SelectGroup,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select' // 🎯 引入 Shadcn/Vue 标准下拉框组件
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import AppLayout from '@/layouts/AppLayout.vue';
import contractsRoute from '@/routes/contracts'
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'ERP/TMS' },
];
// ... 保持原有的 import 结构不变

const props = defineProps<{
  contract: any
  invoice: any
  packingList: any
  customsDeclaration: any
  declarationElements: any
  availableItems: any[]
  availableCategories: any[]
  availableSuppliers: any[]
}>()

// 🎯 核心新加：根据当前行选中的分类ID，动态返回过滤后的商品
const getFilteredItems = (categoryId: string | number) => {
  if (!categoryId) return []
  return props.availableItems.filter((i: any) => i.category_id === Number(categoryId))
}

// 初始化兼顾空状态的货物明细
const initialItems: any[] = []
if (props.contract.contract_items && props.contract.contract_items.length > 0) {
  props.contract.contract_items.forEach((ci: any) => {
    const vals: Record<number, string> = {}
    const templateLength = ci.item?.elements_template?.length || 0
    for (let i = 0; i < templateLength; i++) { vals[i] = ci.element_values_array[i] || '' }
    initialItems.push({
      id: ci.id,
      category_id: ci.item?.category_id || '', // 🎯 核心新加：从合同已存商品中反向解析分类ID
      item_id: ci.item_id,
      unit: ci.unit || ci.item?.unit || 'PCS',
      supplier_id: ci.supplier_id || '',
      sku: ci.item?.sku || '',
      name_cn: ci.item?.name_cn || '',
      template: ci.item?.elements_template || [],
      quantity: parseFloat(ci.quantity),
      unit_price: parseFloat(ci.unit_price),
      image_path: ci.item?.image_path || '',
      purchase_price_snapshot: ci.purchase_price_snapshot || ci.item?.purchase_price || 0.00,
      packages: ci.packages,
      package_type: ci.package_type,
      net_weight: parseFloat(ci.net_weight),
      gross_weight: parseFloat(ci.gross_weight),
      volume: parseFloat(ci.volume),
      element_values: vals
    })
  })
} else {
  initialItems.push({
    id: null, category_id: '', item_id: '', unit: '', supplier_id: '', sku: '', name_cn: '', template: [],
    quantity: 0, unit_price: 0.00, purchase_price_snapshot: 0.00, packages: 1, package_type: 'CTNS', net_weight: 0, gross_weight: 0, volume: 0, element_values: {}
  })
}

// 多维双向绑定 form 对象
const form = useForm({
  contract_no: props.contract.contract_no,
  contract_date: props.contract.contract_date,
  currency: props.contract.currency,
  incoterms: props.contract.incoterms,
  payment_terms: props.contract.payment_terms,
  transport_mode: props.contract.transport_mode,        // 默认 2 | 水路运输
  port_of_loading: props.contract.port_of_loading,     // 默认上海港
  port_of_destination: props.contract.port_of_destination,     // 目的港等待进口商选择级联触发
  items: initialItems
})

const appendNewProductRow = (): void => {
  form.items.push({
    id: null,
    category_id: '', // 🎯 核心新加：空行分类占位
    item_id: '',
    supplier_id: '',
    sku: '',
    name_cn: '',
    template: [],
    quantity: 1,
    unit_price: 0.00,
    packages: 1,
    package_type: 'CTNS',
    net_weight: 0,
    gross_weight: 0,
    volume: 0,
    element_values: {}
  })
}

// 🎯 联动监听器：切换分类时，立刻重置商品选择，防止脏数据跨类
const onCategoryChange = (index: number) => {
  if (form.items[index]) {
    form.items[index].item_id = ''
    form.items[index].sku = ''
    form.items[index].name_cn = ''
    form.items[index].template = []
    form.items[index].element_values = {}
  }
}

const onSelectProduct = (index: number, selectedItemId: string | number) => {
  const product = props.availableItems.find(i => i.id === Number(selectedItemId))

  if (!product || !form.items[index]) return

  form.items[index].item_id = product.id
  form.items[index].sku = product.sku || ''
  form.items[index].name_cn = product.name_cn || ''
  form.items[index].unit = product.unit
  form.items[index].image_path = product.image_path || ''
  // 🎯 修复核心：安全解析 template 确保其一定是原生数组
  let rawTemplate = product.elements_template;
  if (typeof rawTemplate === 'string') {
    try {
      rawTemplate = JSON.parse(rawTemplate);
    } catch (e) {
      rawTemplate = [];
      console.log(e)
    }
  }

  // 确保最终落盘的一定是 Array，防止为 null / undefined
  const finalTemplate = Array.isArray(rawTemplate) ? rawTemplate : [];

  form.items[index].template = finalTemplate
  form.items[index].element_values = {}

  // 🎯 使用安全解析后的数组进行遍历，彻底消除 "forEach is not a function" 报错
  finalTemplate.forEach((_: any, tIndex: number) => {
    form.items[index].element_values[tIndex] = ''
  })

  if (!form.items[index].category_id) {
    form.items[index].category_id = product.category_id
  }

  // 数量与单位快照结合逻辑
  const u = (product.unit || '').toLowerCase();
  const isWeightBased = ['千克', 'kg', '公斤', '吨', 'ton', '米', 'm', '升', 'l'].some(w => u.includes(w));

  if (!isWeightBased) {
    form.items[index].quantity = Math.floor(Number(form.items[index].quantity || 1))
  } else {
    form.items[index].net_weight = form.items[index].quantity
    form.items[index].gross_weight = Math.max(form.items[index].gross_weight, form.items[index].net_weight * 1.03)
  }
}


// ... 后面原有的 removeProductRow / saveDocumentsChanges / exportToExcelWorkbook 保持完全一致

// const props = defineProps<{
//   contract: any
//   invoice: any
//   packingList: any
//   customsDeclaration: any
//   declarationElements: any
//   availableItems: any[]
//   availableCategories: any[]
//   availableSuppliers: any[]
// }>()

// // 初始化兼顾空状态的货物明细
// const initialItems: any[] = []
// if (props.contract.contract_items && props.contract.contract_items.length > 0) {
//   props.contract.contract_items.forEach((ci: any) => {
//     const vals: Record<number, string> = {}
//     const templateLength = ci.item?.elements_template?.length || 0
//     for (let i = 0; i < templateLength; i++) { vals[i] = ci.element_values_array[i] || '' }
//     initialItems.push({
//       id: ci.id, item_id: ci.item_id, unit: ci.unit || ci.item?.unit || 'PCS', supplier_id: ci.supplier_id || '',
//       sku: ci.item?.sku || '', name_cn: ci.item?.name_cn || '', template: ci.item?.elements_template || [],
//       quantity: parseFloat(ci.quantity), unit_price: parseFloat(ci.unit_price), purchase_price_snapshot: ci.purchase_price_snapshot || ci.item?.purchase_price || 0.00,
//       packages: ci.packages, package_type: ci.package_type,
//       net_weight: parseFloat(ci.net_weight), gross_weight: parseFloat(ci.gross_weight), volume: parseFloat(ci.volume),
//       element_values: vals
//     })
//   })
// } else {
//   initialItems.push({
//     id: null, item_id: '', unit: '', supplier_id: '', sku: '', name_cn: '', template: [],
//     quantity: 0, unit_price: 0.00, purchase_price_snapshot: 0.00, packages: 1, package_type: 'CTNS', net_weight: 0, gross_weight: 0, volume: 0, element_values: {}
//   })
// }

// // 多维双向绑定 form 对象
// const form = useForm({
//   contract_date: props.contract.contract_date,
//   currency: props.contract.currency,
//   incoterms: props.contract.incoterms,
//   payment_terms: props.contract.payment_terms,
//   items: initialItems
// })

// const appendNewProductRow = (): void => {
//   form.items.push({
//     id: null, item_id: '', supplier_id: '', sku: '', name_cn: '', template: [],
//     quantity: 1, unit_price: 0.00, packages: 1, package_type: 'CTNS', net_weight: 0, gross_weight: 0, volume: 0, element_values: {}
//   })
// }
// // 🎯 前端核心判定：自动检测该 H.S. 编码的属性，如果是重量物品，直接触发“重量结算模式”
// // const isWeightBased = computed(() => {
// //   const u = item.unit?.toLowerCase() || '';
// //   return ['千克', 'kg', '公斤', '吨', 'ton'].some(w => u.includes(w));
// // });

// const onSelectProduct = (index: number, selectedItemId: string | number) => {
//   const product = props.availableItems.find(i => i.id === Number(selectedItemId))

//   if (!product || !form.items[index]) return

//   form.items[index].item_id = product.id
//   form.items[index].sku = product.sku || ''
//   form.items[index].name_cn = product.name_cn || ''
//   form.items[index].unit = product.unit // 🎯 核心新加：选择商品时同步刷新前端单位格
//   form.items[index].template = product.elements_template || []
//   form.items[index].element_values = {}

//   form.items[index].template.forEach((_: any, tIndex: number) => {
//     form.items[index].element_values[tIndex] = ''
//   })
// }

const removeProductRow = (index: number) => {
  form.items.splice(index, 1)
}

const saveDocumentsChanges = () => {
  const payloadItems = form.items.map(item => ({
    ...item,
    // 🎯 核心补齐：显式转换为高精度浮点数，防止 JavaScript 运行时丢失精度
    quantity: parseFloat(Number(item.quantity).toFixed(4)),      // 数量保留 4 位
    unit_price: parseFloat(Number(item.unit_price).toFixed(4)),  // 单价保留 4 位
    purchase_price_snapshot: parseFloat(Number(item.purchase_price_snapshot).toFixed(4)),
    packages: parseInt(Number(item.packages).toString()),        // 件数保持整数

    net_weight: parseFloat(Number(item.net_weight).toFixed(3)),  // 净重严格保留 3 位
    gross_weight: parseFloat(Number(item.gross_weight).toFixed(3)), // 🎯 毛重严格保留 3 位（克级精度）
    volume: parseFloat(Number(item.volume).toFixed(3)),          // 体积保留 3 位

    element_values: Object.keys(item.element_values).sort().map(k => item.element_values[Number(k)] || '-')
  }))

  router.put(`/contracts/${props.contract.id}/documents`, {
    contract_no: form.contract_no,
    contract_date: form.contract_date,
    currency: form.currency,
    incoterms: form.incoterms,
    payment_terms: form.payment_terms,
    transport_mode: form.transport_mode,        // 默认 2 | 水路运输
    port_of_loading: form.port_of_loading,     // 默认上海港
    port_of_destination: form.port_of_destination,     // 目的港等待进口商选择级联触发
    items: payloadItems
  }, {
    preserveScroll: true,
    onSuccess: () => toast.success('制单系统提示：合同条款与三位小数高精度装箱物流链已同步配平！')
  })
}

// ================== 🎯 核心补齐：纯前端高保真 Excel 异步导出算法 ==================
const exportToExcelWorkbook = () => {
  // 🎯 完全基于 Wayfinder 规范：直接在新标签页调起后端的模板二进制覆盖流
  window.open(`/contracts/${props.contract.id}/documents/excel-template`, '_blank');
}

const formatSmartNumber = (val: any) => {
  if (val === undefined || val === null || isNaN(val)) return '0';
  return Number(val).toLocaleString('zh-CN', {
    minimumFractionDigits: 0, // 整数不显示小数点
    maximumFractionDigits: 2  // 最多显示2位小数（可调）
  });
};

const getCurrentItem = (itemId: string | number) => {
  if (!itemId) return null

  return props.availableItems.find(
    (i: any) => i.id === Number(itemId)
  ) || null
}
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="min-h-screen p-8 max-w-7xl mx-auto space-y-6 bg-background text-foreground">

      <!-- 顶部控制大盘 -->
      <div
        class="flex justify-between items-center bg-card border border-border p-5 rounded-2xl shadow-lg shadow-primary/5">
        <div>
          <span class="text-primary text-xs font-semibold tracking-widest uppercase">CORE EXPORT CONTRACT
            WORKBENCH</span>
          <h1
            class="text-2xl font-bold tracking-tight bg-linear-to-r from-primary to-(--success) bg-clip-text text-transparent">
            核心合同：{{ contract.contract_no }} 工作台</h1>
        </div>
        <div class="flex gap-2">
          <!-- 🎯 导出 Excel 核心挂载按钮 -->
          <Button
            class="bg-primary hover:brightness-110 text-primary-foreground rounded-md shadow-lg shadow-primary/20 transition-all duration-200 h-8 text-xs font-bold"
            @click="exportToExcelWorkbook">
            📥 一键导出全套 Excel
          </Button>
          <Button
            class="bg-success hover:brightness-110 text-success-foreground rounded-md shadow-lg h-8 text-xs font-bold"
            @click="appendNewProductRow">
            ➕ 选配/追加合同外销商品
          </Button>
          <Button as-child variant="secondary" size="sm" class="h-8 text-xs rounded-md">
            <Link :href="contractsRoute.index()">返回主台账</Link>
          </Button>
        </div>
      </div>

      <!-- 货物明细与规范申报动态修正中心 -->
      <Card class="bg-card border-border rounded-2xl shadow-lg shadow-primary/5">
        <CardHeader class="bg-muted/30 border-b border-border pb-3 flex flex-row items-center justify-between">
          <CardTitle class="text-sm font-bold text-primary">⚡ 货物明细与规范申报动态修正中心（单据源头）</CardTitle>
          <Button size="sm"
            class="bg-primary hover:brightness-110 text-primary-foreground rounded-md shadow-lg shadow-primary/20 h-8"
            @click="saveDocumentsChanges">
            🔄 同步应用并分发全套单据
          </Button>
        </CardHeader>
        <CardContent class="pt-4 space-y-6">
          <!-- 🎯 合同要素与国际物流核心大盘修改分栏 -->
<div class="p-4 border border-dashed border-border rounded-xl bg-muted/30 space-y-4">
  <div class="text-xs font-bold text-muted-foreground flex items-center gap-1.5 border-b border-border/60 pb-1.5">
    <span>💼 订单核心条款与物流大盘控制（全单联动）</span>
  </div>

  <!-- 第一排：商务条款 (新增出口合同号，调整为 5 列布局) -->
  <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
    <!-- 🌟 新增：出口合同号 -->
    <div class="space-y-1">
      <Label for="contract_no" class="text-xs font-bold text-foreground">出口合同号 (Contract No.)</Label>
      <Input id="contract_no" v-model="form.contract_no" placeholder="如: CT20260625-01"
        class="h-8 text-xs font-mono uppercase rounded-lg border border-border bg-background focus-visible:ring-ring" required />
      <div v-if="form.errors.contract_no" class="text-[10px] text-destructive mt-0.5 font-medium">
        {{ form.errors.contract_no }}
      </div>
    </div>

    <div class="space-y-1">
      <Label class="text-xs font-bold text-foreground">签约日期 (Date)</Label>
      <Input type="date" v-model="form.contract_date"
        class="h-8 text-xs bg-background border border-border rounded-lg focus-visible:ring-ring" />
    </div>

    <div class="space-y-1">
      <Label class="text-xs font-bold text-foreground">外销币种 (Currency)</Label>
      <Select :model-value="form.currency"
        @update:model-value="(val) => form.currency = val ? String(val) : 'USD'">
        <SelectTrigger class="w-full h-8 text-xs bg-background">
          <SelectValue placeholder="选择币种..." />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="USD">USD - 美元</SelectItem>
          <SelectItem value="EUR">EUR - 欧元</SelectItem>
          <SelectItem value="GBP">GBP - 英镑</SelectItem>
          <SelectItem value="CNY">CNY - 跨境人民币</SelectItem>
        </SelectContent>
      </Select>
    </div>

    <div class="space-y-1">
      <Label class="text-xs font-bold text-foreground">价格/贸易术语 (Incoterms)</Label>
      <Select :model-value="form.incoterms"
        @update:model-value="(val) => form.incoterms = val ? String(val) : 'FOB'">
        <SelectTrigger class="w-full h-8 text-xs bg-background">
          <SelectValue placeholder="选择术语..." />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="FOB">FOB - 离岸价</SelectItem>
          <SelectItem value="CIF">CIF - 到岸价</SelectItem>
          <SelectItem value="EXW">EXW - 工厂交付</SelectItem>
        </SelectContent>
      </Select>
    </div>


  </div>

  <!-- 🌟 第二排：国际物流舱单三要素 (保持原样不变) -->
  <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 border-t border-border/40 pt-3">
    <div class="space-y-1">
      <Label class="text-xs font-bold text-foreground">收汇付款条件 (Payment Terms)</Label>
      <Input v-model="form.payment_terms"
        class="h-8 text-xs bg-background border border-border rounded-lg focus-visible:ring-ring" />
    </div>
    <div class="space-y-1">
      <Label class="text-xs font-bold text-foreground">运输方式 (Traffic Mode)</Label>
      <Select :model-value="form.transport_mode"
        @update:model-value="(val) => form.transport_mode = val ? String(val) : '2'">
        <SelectTrigger class="w-full h-8 text-xs bg-background">
          <SelectValue placeholder="选择运输方式..." />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="2">2 | 水路运输 (SEA)</SelectItem>
          <SelectItem value="5">5 | 航空运输 (AIR)</SelectItem>
          <SelectItem value="3">3 | 铁路运输 (RAIL)</SelectItem>
          <SelectItem value="4">4 | 公路运输 (ROAD)</SelectItem>
        </SelectContent>
      </Select>
    </div>

    <div class="space-y-1">
      <Label class="text-xs font-bold text-foreground">出口港 / 启运港 (Loading Port)</Label>
      <Select :model-value="form.port_of_loading"
        @update:model-value="(val) => form.port_of_loading = val ? String(val) : ''">
        <SelectTrigger class="w-full h-8 text-xs bg-background">
          <SelectValue placeholder="选择国内启运港..." />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="YAN">烟台港 (YANTAI, CN)</SelectItem>
          <SelectItem value="SHA">上海港 (SHANGHAI, CN)</SelectItem>
          <SelectItem value="TAO">青岛港 (QINGDAO, CN)</SelectItem>
          <SelectItem value="NGB">宁波港 (NINGBO, CN)</SelectItem>
          <SelectItem value="SZX">深圳港 (SHENZHEN, CN)</SelectItem>
        </SelectContent>
      </Select>
    </div>

    <div class="space-y-1">
      <Label class="text-xs font-bold text-foreground">进口港 / 目的港 (Destination)</Label>
      <Select :model-value="form.port_of_destination"
        @update:model-value="(val) => form.port_of_destination = val ? String(val) : ''">
        <SelectTrigger class="w-full h-8 text-xs bg-background">
          <SelectValue placeholder="选择境外目的港..." />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="INC">仁川港 (INCHEON, KR)</SelectItem>
          <SelectItem value="PUS">釜山港 (BUSAN, KR)</SelectItem>
          <SelectItem value="TYO">东京港 (TOKYO, JP)</SelectItem>
          <SelectItem value="LAX">洛杉矶港 (LOS ANGELES, US)</SelectItem>
        </SelectContent>
      </Select>
      <!-- 港口错误信息提示防呆 -->
      <p v-if="form.errors.port_of_destination" class="text-[10px] text-destructive mt-0.5 font-medium">
        {{ form.errors.port_of_destination }}
      </p>
    </div>
  </div>
</div>

          <!-- 循环商品卡片 -->
          <div v-for="(item, index) in form.items" :key="index"
            class="p-4 border border-border rounded-2xl bg-card space-y-4 shadow-lg shadow-primary/5 relative">
            <button type="button" @click="removeProductRow(index)"
              class="absolute right-3 top-3 text-xs text-destructive hover:text-destructive font-bold rounded-md">✕
              移除此项</button>
            <!-- <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-muted/30 p-3 rounded-lg">
              <div class="space-y-1.5">
                <Label class="text-xs font-bold text-foreground">步骤 ①：从系统商品档案库中勾选货物</Label>
                <select v-model="item.item_id" @change="onSelectProduct(index, item.item_id)"
                  class="w-full h-8 rounded-lg border border-border bg-background px-2 text-xs shadow-sm focus:ring-2 focus:ring-ring focus:outline-none transition-colors">
                  <option value="" disabled>请挑选对应的 H.S. 编码商品...</option>
                  <option v-for="avail in availableItems" :key="avail.id" :value="avail.id">{{ avail.name_cn }} (SKU: {{
                    avail.sku }} | HS: {{ avail.hs_code }} )</option>
                </select>
              </div>
              <div class="space-y-1.5">
                <Label class="text-xs font-bold text-foreground">步骤 ②：绑定国内供货商</Label>
                <select v-model="item.supplier_id"
                  class="w-full h-8 rounded-lg border border-border bg-background px-2 text-xs shadow-sm focus:ring-2 focus:ring-ring focus:outline-none transition-colors">
                  <option value="" disabled>请指定国内工厂...</option>
                  <option v-for="sup in availableSuppliers" :key="sup.id" :value="sup.id">{{ sup.company_name }}
                  </option>
                </select>
              </div>
            </div> -->
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 bg-muted/30 p-3 rounded-lg">

              <!-- 步骤 ①-A：选择商品大类 -->
              <div class="space-y-1.5 col-span-3">
                <Label class="text-xs font-bold text-foreground">步骤 ①-A：选择商品大类</Label>
                <Select :model-value="item.category_id ? String(item.category_id) : ''" @update:model-value="(val) => {
                  item.category_id = val
                  onCategoryChange(index)
                }">
                  <SelectTrigger class="h-8 text-xs w-full">
                    <SelectValue placeholder="请选择商品分类..." />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectGroup>
                      <SelectLabel class="text-xs text-muted-foreground">商品大类</SelectLabel>
                      <SelectItem v-for="cat in availableCategories" :key="cat.id" :value="String(cat.id)"
                        class="text-xs">
                        HS: {{ cat.hs_code }} — {{ cat.category_name }}
                      </SelectItem>
                    </SelectGroup>
                  </SelectContent>
                </Select>
              </div>

              <!-- 步骤 ①-B：从大类下联动勾选货物 -->
              <div class="space-y-1.5 col-span-5">
                <Label class="text-xs font-bold text-foreground">步骤 ①-B：从分类下勾选货物</Label>
                <Select :model-value="item.item_id ? String(item.item_id) : ''" :disabled="!item.category_id"
                  @update:model-value="(val: any) => onSelectProduct(index, val)">
                  <SelectTrigger class="h-8 text-xs w-full">
                    <SelectValue placeholder="请挑选对应的 H.S. 编码商品..." />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectGroup>
                      <SelectLabel class="text-xs text-muted-foreground">
                        {{ item.category_id ? '当前分类下的商品' : '请先选择商品大类' }}
                      </SelectLabel>
                      <SelectItem v-for="avail in getFilteredItems(item.category_id)" :key="avail.id"
                        :value="String(avail.id)" class="text-xs">
                        {{ avail.name_cn }}
                        <span class="text-muted-foreground ml-1">SKU: {{ avail.sku }}</span>
                        <!-- <img v-if="avail.image_path" :src="`/storage/${avail.image_path}`" class="size-4 object-contain" /> -->
                      </SelectItem>
                    </SelectGroup>
                  </SelectContent>
                </Select>
              </div>
<!-- 商品图片 -->
<div class="space-y-1.5 col-span-1">
  <!-- <Label class="text-xs font-bold text-foreground">商品图片</Label> -->
  <div class="size-14 rounded-lg border bg-muted flex items-center justify-center overflow-hidden">
    <img v-if="getCurrentItem(item.item_id)?.image_path" :src="`/storage/${getCurrentItem(item.item_id)?.image_path}`" class="w-full h-full object-contain" />
    <div v-else class="text-xs text-muted-foreground">暂无图片</div>
  </div>
</div>
              <!-- 步骤 ②：绑定国内供货商 -->
              <div class="space-y-1.5 col-span-3">
                <Label class="text-xs font-bold text-foreground">步骤 ②：绑定国内供货商</Label>
                <Select :model-value="item.supplier_id ? String(item.supplier_id) : ''"
                  @update:model-value="(val) => { item.supplier_id = val }">
                  <SelectTrigger class="h-8 text-xs w-full">
                    <SelectValue placeholder="请指定国内工厂..." />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectGroup>
                      <SelectLabel class="text-xs text-muted-foreground">供货商</SelectLabel>
                      <SelectItem v-for="sup in availableSuppliers" :key="sup.id" :value="String(sup.id)"
                        class="text-xs">
                        {{ sup.company_name }}
                      </SelectItem>
                    </SelectGroup>
                  </SelectContent>
                </Select>
              </div>

            </div>


            <div v-if="item.item_id" class="space-y-4 animate-fadeIn">

              <!-- 升级后的 7 列高集成度商务与包装参数输入网格 -->
              <div class="grid grid-cols-2 md:grid-cols-7 gap-3 text-xs">

                <!-- 1. 出口申报数量 -->
                <div class="space-y-1">
                  <Label class="text-[11px] text-muted-foreground font-bold">出口数量 ({{ item.unit || 'KG' }})</Label>
                  <Input type="number"
                    :step="['千克', '米', '升', '吨'].some(w => item.unit?.toLowerCase().includes(w)) ? '0.01' : '1'"
                    v-model="item.quantity" @input="() => {
                      if (!['千克', '米', '升', '吨'].some(w => item.unit?.toLowerCase().includes(w))) {
                        item.quantity = Math.floor(Number(item.quantity));
                      } else {
                        item.net_weight = item.quantity;
                        item.gross_weight = Math.max(item.gross_weight, item.net_weight * 1.03);
                      }
                    }"
                    class="h-8 font-mono font-bold bg-background border border-border rounded-lg focus-visible:ring-ring" />
                </div>

                <!-- 2. 外销销售单价 (向国外收美金的单价) -->
                <div class="space-y-1">
                  <Label class="text-[11px] text-muted-foreground">外销单价 (USD)</Label>
                  <Input type="number" step="0.0001" v-model="item.unit_price"
                    class="h-8 font-mono bg-background border border-border rounded-lg focus-visible:ring-ring" />
                </div>

                <!-- 3. 🎯 核心补齐：本次实际下单进货价 (给国内工厂付人民币的单价) -->
                <div class="space-y-1 bg-muted/60 p-0 rounded border border-dashed border-border">
                  <Label class="text-[11px] text-foreground font-bold flex justify-between">
                    <span>进价(RMB)</span>
                    <span class="text-[9px] text-muted-foreground">含税</span>
                  </Label>
                  <div class="relative">
                    <span class="absolute left-2 top-1.5 text-[10px] text-muted-foreground font-bold">￥</span>
                    <Input type="number" step="0.01" v-model="item.purchase_price_snapshot"
                      class="h-7 mt-0.5 pl-5 text-xs font-mono font-black text-foreground bg-background border border-border rounded-md focus-visible:ring-ring"
                      placeholder="0.00" />
                  </div>
                </div>

                <!-- 4. 大货打包件数/包装 -->
                <div class="space-y-1">
                  <Label class="text-[11px] text-muted-foreground">件数/包装</Label>
                  <div class="flex gap-1">
                    <Input type="number" v-model="item.packages"
                      class="h-8 w-1/2 bg-background border border-border rounded-lg focus-visible:ring-ring" />
                    <Input v-model="item.package_type"
                      class="h-8 w-1/2 bg-background border border-border rounded-lg focus-visible:ring-ring" />
                  </div>
                </div>

                <!-- 5. 总净重 -->
                <div class="space-y-1">
                  <Label class="text-[11px] text-muted-foreground flex justify-between">
                    <span>总净重 (KG)</span>
                    <span v-if="['千克', 'kg', '公斤', '吨'].some(w => item.unit?.toLowerCase().includes(w))"
                      class="text-[9px] text-primary font-bold">锁定</span>
                  </Label>
                  <Input type="number" step="0.01" v-model="item.net_weight"
                    :disabled="['千克', 'kg', '公斤', '吨'].some(w => item.unit?.toLowerCase().includes(w))"
                    class="h-8 font-mono bg-background border border-border rounded-lg focus-visible:ring-ring disabled:opacity-80 disabled:text-primary disabled:font-bold" />
                </div>

                <!-- 6. 大货总毛重 -->
                <div class="space-y-1">
                  <Label class="text-[11px] text-muted-foreground">总毛重 (KG)</Label>
                  <Input type="number" step="0.001" v-model="item.gross_weight"
                    class="h-8 font-mono bg-background border border-border rounded-lg focus-visible:ring-ring" />
                </div>

                <!-- 7. 总体积 -->
                <div class="space-y-1">
                  <Label class="text-[11px] text-muted-foreground">总体积 (CBM)</Label>
                  <Input type="number" step="0.001" v-model="item.volume"
                    class="h-8 font-mono bg-background border border-border rounded-lg focus-visible:ring-ring" />
                </div>

              </div>
              <!-- resources/js/Pages/Documents/Show.vue 动态申报要素填空模块 -->

              <!-- 🎯 只有当操作员在上方勾选了具体的出口货物且该海关大类存在要素模板时，才绽放此区 -->
              <div v-if="item.item_id && item.template && item.template.length > 0"
                class="border border-blue-200/60 bg-blue-50/5 dark:bg-blue-950/5 rounded-xl p-4 space-y-4 animate-fadeIn">

                <!-- 区块头部导引 -->
                <div class="flex justify-between items-center border-b border-blue-100/50 pb-2">
                  <div>
                    <h4 class="text-xs font-bold text-blue-900 dark:text-blue-400 flex items-center gap-1.5">
                      🛠️ 中华人民共和国海关 ➜ 规范申报要素填报矩阵
                    </h4>
                    <p class="text-[10px] text-muted-foreground mt-0.5">
                      根据税则指纹，报关员必须逐项如实申报。系统将自动使用 <code class="bg-slate-100 px-1 rounded font-bold">|</code>
                      符号拼合成标准通关长报文。
                    </p>
                  </div>
                  <Badge variant="secondary" class="text-[10px] bg-blue-100/80 text-blue-800 font-mono font-bold">
                    共 {{ item.template.length }} 项法定要素
                  </Badge>
                </div>

                <!-- 🎯 核心高能：横向及多列展开的动态填空矩阵网格 -->
                <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
                  <div v-for="(label, idx) in item.template" :key="idx"
                    class="space-y-1.5 p-2.5 bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-lg shadow-2xs group hover:border-blue-200 transition-colors">
                    <!-- 要素法定序号与紧凑的标签名（利用 whitespace-nowrap 防抖） -->
                    <Label class="text-[11px] font-bold text-slate-500 dark:text-slate-400 flex items-center gap-1">
                      <span
                        class="font-mono text-[9px] bg-slate-100 text-slate-500 rounded px-1 group-hover:bg-blue-600 group-hover:text-white transition-colors">
                        {{ Number(idx) + 1 }}
                      </span>
                      <span class="whitespace-nowrap tracking-wide select-none">
                        {{ typeof label === 'string' ? label.trim() : label }}
                      </span>
                    </Label>

                    <!-- 🎯 真实录入格：双向绑定到行项内特定序号的元数据中 -->
                    <Input v-model="item.element_values[idx]" placeholder="请输入内容..."
                      class="h-7 text-xs rounded-md border-slate-200 bg-background focus-visible:ring-1 focus-visible:ring-blue-500/50" />
                  </div>
                </div>

                <!-- 底部高拟真动态报文流实时沙盘（给录单员提供即时反馈，极具高级感） -->
                <div
                  class="bg-slate-900 text-slate-200 rounded-lg p-2 font-mono text-[10px] flex items-center gap-2 border border-slate-800 shadow-inner">
                  <span class="bg-slate-800 text-slate-400 px-1.5 py-0.5 rounded font-bold select-none text-[9px]">🔍
                    动态报文模拟:</span>
                  <div class="flex-1 truncate tracking-wider text-green-400/90 font-semibold">
                    <!-- 实时使用 | 拼接展现 -->
                    {{ item.name_cn }} |
                    <span v-if="Object.values(item.element_values).some(v => v)">
                      {{Object.keys(item.element_values).sort().map(k => item.element_values[Number(k)] ||
                      '-').join('|') }}|
                    </span>
                    <span v-else class="text-slate-500 italic">等待录入要素...</span>
                  </div>
                </div>

              </div>

              <!-- 下方依然保留海关规范要素填空框等逻辑... -->
            </div>
          </div>
          <!-- 🎯 按钮位置 ②：空状态中心引导 -->
          <div v-if="form.items.length === 0"
            class="text-center py-12 border border-dashed border-border rounded-xl bg-muted/30">
            <p class="text-sm text-muted-foreground mb-3">当前合同项下尚未选配任何出口货物明细</p>
            <Button variant="outline"
              class="border-primary/20 text-primary bg-background hover:bg-primary hover:text-primary-foreground"
              @click="appendNewProductRow">
              ➕ 立刻初始化添加第一笔货物
            </Button>
          </div>

        </CardContent>
      </Card>
      <!-- ================== 下方：五个维度的实时单据展现视窗 ================== -->
      <Tabs default-value="invoice" class="w-full">
        <!-- 1. 选项卡标签头导航栏 -->
        <TabsList class="grid w-full grid-cols-4 bg-muted rounded-xl h-11 p-1">
          <TabsTrigger value="invoice">1. 商业发票 (Invoice)</TabsTrigger>
          <TabsTrigger value="packing">2. 装箱单 (Packing List)</TabsTrigger>
          <TabsTrigger value="customs">3. 报关单数据</TabsTrigger>
          <TabsTrigger value="elements">4. 规范申报串</TabsTrigger>
        </TabsList>

        <!-- 维度1: Commercial Invoice 视窗 -->
        <TabsContent value="invoice">
          <Card class="bg-card border-border rounded-2xl shadow-lg shadow-primary/5 font-serif">
            <CardHeader class="bg-muted/30 border-b border-border">
              <CardTitle class="text-center text-xl font-extrabold tracking-wider text-foreground uppercase">COMMERCIAL
                INVOICE</CardTitle>
              <div class="flex justify-between text-xs font-mono text-muted-foreground pt-4">
                <div><strong>Invoice No:</strong> {{ invoice.invoice_no }}</div>
                <div><strong>Date:</strong> {{ invoice.date }}</div>
                <div><strong>Price Terms:</strong> {{ invoice.incoterms }}</div>
              </div>
            </CardHeader>
            <CardContent class="pt-6">
              <Table>
                <TableHeader>
                  <TableRow class="bg-background text-foreground">
                    <TableHead class="font-bold">Description of Goods (English)</TableHead>
                    <TableHead class="text-right font-bold w-30">Quantity</TableHead>
                    <TableHead class="text-right font-bold w-40">Unit Price ({{ invoice.currency }})</TableHead>
                    <TableHead class="text-right font-bold w-45">Amount ({{ invoice.currency }})</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <TableRow v-for="sub in invoice.items" :key="sub.name"
                    class="hover:bg-secondary/40 transition-colors">
                    <TableCell class="font-medium text-xs text-foreground">{{ sub.name }}</TableCell>
                    <TableCell class="text-right text-xs font-mono">{{ formatSmartNumber(sub.qty) }}</TableCell>
                    <TableCell class="text-right text-xs font-mono">${{ parseFloat(sub.price) }}</TableCell>
                    <TableCell class="text-right text-xs font-bold font-mono text-foreground">${{
                      formatSmartNumber(sub.total) }}</TableCell>
                  </TableRow>
                  <TableRow class="bg-background text-foreground font-bold border-t-2">
                    <TableCell colspan="3" class="text-right text-xs uppercase tracking-wider">TOTAL INVOICE VALUE:
                    </TableCell>
                    <TableCell class="text-right text-sm text-success font-black tracking-wide font-mono">{{
                      formatSmartNumber(invoice.grand_total) }} {{ invoice.currency }}</TableCell>
                  </TableRow>
                </TableBody>
              </Table>
            </CardContent>
          </Card>
        </TabsContent>

        <!-- 维度2: Packing List 视窗 -->
        <TabsContent value="packing">
          <Card class="bg-card border-border rounded-2xl shadow-lg shadow-primary/5">
            <CardHeader class="bg-muted/30 border-b border-border">
              <CardTitle class="text-center text-xl font-bold text-foreground">PACKING LIST</CardTitle>
            </CardHeader>
            <CardContent class="pt-4">
              <Table>
                <TableHeader>
                  <TableRow class="bg-background text-foreground">
                    <TableHead class="font-bold">Description</TableHead>
                    <TableHead class="text-right font-bold">Packages</TableHead>
                    <TableHead class="text-right font-bold">Net Weight (KG)</TableHead>
                    <TableHead class="text-right font-bold">Gross Weight (KG)</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <TableRow v-for="sub in packingList.items" :key="sub.name"
                    class="hover:bg-secondary/40 transition-colors">
                    <TableCell class="text-xs">{{ sub.name }}</TableCell>
                    <TableCell class="text-right text-xs">{{ sub.packages }} {{ sub.package_type }}</TableCell>
                    <TableCell class="text-right text-xs font-mono">{{ formatSmartNumber(sub.nw) }}</TableCell>
                    <TableCell class="text-right text-xs font-mono">{{ formatSmartNumber(sub.gw) }}</TableCell>
                  </TableRow>
                  <TableRow class="bg-background text-foreground font-bold text-xs border-t-2">
                    <TableCell>SUMMARY TOTAL:</TableCell>
                    <TableCell class="text-right">{{ packingList.total_packages }} PKGS</TableCell>
                    <TableCell class="text-right font-mono">{{ formatSmartNumber(packingList.total_net_weight) }} KG
                    </TableCell>
                    <TableCell class="text-right font-mono">{{ formatSmartNumber(packingList.total_gross_weight) }} KG
                    </TableCell>
                  </TableRow>
                </TableBody>
              </Table>
              <div
                class="mt-4 p-3 bg-muted/30 rounded-lg flex justify-between items-center text-xs font-mono text-muted-foreground border border-border">
                <span>运输总总体积 (TOTAL VOLUME):</span>
                <span class="font-bold text-success text-sm">{{ packingList.total_volume }} CBM</span>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        <!-- 维度3: 报关单数据视窗 -->
        <TabsContent value="customs">
          <Card class="bg-card border-border rounded-2xl shadow-lg shadow-primary/5">
            <CardHeader class="bg-muted/30 border-b border-border">
              <CardTitle class="text-sm font-bold text-foreground">中华人民共和国海关出口货物报关单（草单数据）</CardTitle>
              <CardDescription>用于报关行无缝对接海关单一窗口系统，必须使用法定中文品名与单位</CardDescription>
            </CardHeader>
            <CardContent class="pt-4">
              <Table>
                <TableHeader>
                  <TableRow class="bg-background text-foreground">
                    <TableHead class="w-20">项号</TableHead>
                    <TableHead>商品编号 (HS Code)</TableHead>
                    <TableHead>商品名称及规格型号</TableHead>
                    <TableHead class="text-right">数量及单位</TableHead>
                    <TableHead class="text-right">申报总价 (USD)</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <TableRow v-for="(sub, i) in customsDeclaration.items" :key="i"
                    class="hover:bg-secondary/40 transition-colors">
                    <TableCell class="font-mono text-xs text-muted-foreground">#{{ Number(i) + 1 }}</TableCell>
                    <TableCell>
                      <Badge variant="outline" class="font-mono bg-primary/10 text-primary border border-primary/20">
                        H.S.: {{ sub.hs_code }}</Badge>
                    </TableCell>
                    <TableCell class="text-xs font-medium text-foreground">{{ sub.name_cn }}</TableCell>
                    <TableCell class="text-right text-xs font-mono">{{ formatSmartNumber(sub.qty) }} {{ sub.unit }}
                    </TableCell>
                    <TableCell class="text-right text-xs font-mono font-bold text-success tracking-wide">${{
                      formatSmartNumber(sub.amount_usd) }}</TableCell>
                  </TableRow>
                </TableBody>
              </Table>
            </CardContent>
          </Card>
        </TabsContent>

        <!-- 维度4: 规范申报要素视窗 -->
        <TabsContent value="elements">
          <Card class="bg-card border-border rounded-2xl shadow-lg shadow-primary/5">
            <CardHeader class="bg-muted/30 border-b border-border">
              <CardTitle class="text-sm font-bold text-foreground">海关规范申报要素最终拼接串预览</CardTitle>
              <CardDescription>海关审单中心核心风控抓取字段，格式严密遵循海关规范</CardDescription>
            </CardHeader>
            <CardContent class="pt-4 space-y-3">
              <div v-for="(sub, i) in declarationElements.items" :key="i"
                class="p-4 border border-border rounded-2xl bg-muted/30 font-mono text-xs shadow-lg shadow-primary/5">
                <div class="flex justify-between items-center text-muted-foreground mb-2 border-b pb-1.5">
                  <span class="font-bold text-foreground">品名：{{ sub.name_cn }}</span>
                  <Badge variant="outline" class="font-mono bg-primary/10 text-primary border border-primary/20">H.S.:
                    {{ sub.hs_code }}</Badge>
                </div>
                <div
                  class="bg-card border border-border text-success p-3 rounded-lg font-mono font-bold tracking-wide break-all whitespace-pre-wrap select-all">
                  {{ sub.declared_elements }}</div>
                <p class="text-[10px] text-muted-foreground mt-1.5">💡 提示：报关录单时，可双击直接全选上方绿色代码一键复制粘贴至报关系统。</p>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

      </Tabs>
    </div>
  </AppLayout>
</template>
