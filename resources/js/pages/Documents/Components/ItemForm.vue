<script setup lang="ts">
import { ref } from 'vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'

interface FormItem {
  item_id: string
  quantity: number
  unit_price: number
  packages: number
  package_type: string
  net_weight: number
  gross_weight: number
  volume: number
  selectedTemplate: string[]
  element_values: Record<number, string>
}

// 模拟从后端获取的基础商品档案数据（实际开发中通过 props 或 API 获取）
const availableItems = ref([
  { 
    id: 1, 
    sku: 'SKU-001', 
    name_cn: '不锈钢螺丝', 
    hs_code: '7318151001', 
    elements_template: ['品牌', '每千克个数', '规格型号', '材质'],
    unit: '千克',
    purchase_price: 50.00
  },
  { 
    id: 2, 
    sku: 'SKU-002', 
    name_cn: '锂电池无线音箱', 
    hs_code: '8518210000', 
    elements_template: ['品牌', '型号', '功率', '是否含有电池'],
    unit: '台',
    purchase_price: 260.00
  }
])

// 表单响应式数据
const formItems = ref<FormItem[]>([
  {
    item_id: '',
    quantity: 100,
    unit_price: 5.50,
    packages: 5,
    package_type: 'Cartons',
    net_weight: 50,
    gross_weight: 55,
    volume: 0.25,
    selectedTemplate: [], // 存储当前选定商品的要素模板
    element_values: {}    // 动态存储用户填写的要素值，形如 {0: "无牌", 1: "100个"}
  }
])

// 监听商品选择，动态切换对应的海关要素输入框
const handleItemChange = (index: number, itemId: string | null) => {
  if (!itemId) {
    formItems.value[index].selectedTemplate = []
    formItems.value[index].element_values = {}
    return
  }
  const selected = availableItems.value.find(i => i.id === parseInt(itemId))
  if (selected) {
    formItems.value[index].selectedTemplate = selected.elements_template
    formItems.value[index].element_values = {}
    // 初始化空值
    selected.elements_template.forEach((_: string, tIndex: number) => {
      formItems.value[index].element_values[tIndex] = ''
    })
  }
}

const addNewItemRow = () => {
  formItems.value.push({
    item_id: '', quantity: 1, unit_price: 0, packages: 1, package_type: 'Cartons',
    net_weight: 0, gross_weight: 0, volume: 0, selectedTemplate: [], element_values: {}
  })
}

const submitForm = () => {
  // 转换 element_values 对象为规范的顺序数组，方便后端处理
  const payload = formItems.value.map(item => ({
    ...item,
    element_values: Object.keys(item.element_values)
      .map(Number)
      .sort((a, b) => a - b)
      .map(key => item.element_values[key] || '-')
  }))
  
  console.log('提交给后端的合规报关负载数据:', payload)
  // 此处使用 Inertia.post(route('contract.items.store', contractId), { items: payload }) 进行提交
}
</script>

<template>
  <Card class="w-full bg-card border-border rounded-2xl shadow-lg shadow-primary/5">
    <CardHeader class="border-b border-border">
      <CardTitle class="text-primary font-bold tracking-wide flex justify-between items-center">
        <span>出口货物明细与智能规范申报面板</span>
        <Button size="sm" variant="outline" class="rounded-md border-primary/20 bg-primary/5 text-primary hover:bg-primary hover:text-primary-foreground transition-colors" @click="addNewItemRow">+ 添加品项</Button>
      </CardTitle>
    </CardHeader>
    <CardContent class="space-y-6">
      
      <div v-for="(formItem, index) in formItems" :key="index" class="p-5 border border-border rounded-2xl bg-card shadow-sm space-y-4">
        
        <!-- 第一排：商品基本商务要素 -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div class="space-y-2">
            <Label class="text-muted-foreground text-xs font-medium">选择报关商品 (HS Code)</Label>
            <Select v-model="formItem.item_id" @update:modelValue="(val) => handleItemChange(index, typeof val === 'string' ? val : null)">
              <SelectTrigger class="rounded-lg border-border bg-background focus:ring-ring"><SelectValue placeholder="请选择商品..." /></SelectTrigger>
              <SelectContent>
                <SelectItem v-for="item in availableItems" :key="item.id" :value="item.id.toString()">
                  {{ item.name_cn }} ({{ item.hs_code }})
                </SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div class="space-y-2">
            <Label class="text-muted-foreground text-xs font-medium">出口数量</Label>
            <Input type="number" v-model="formItem.quantity" class="rounded-lg border-border bg-background focus-visible:ring-ring" />
          </div>
          <div class="space-y-2">
            <Label class="text-muted-foreground text-xs font-medium">外销单价 (USD)</Label>
            <Input type="number" step="0.0001" v-model="formItem.unit_price" class="rounded-lg border-border bg-background focus-visible:ring-ring" />
          </div>
          <div class="space-y-2">
            <Label class="text-muted-foreground text-xs font-medium">包装箱数与种类</Label>
            <div class="flex gap-2">
              <Input type="number" v-model="formItem.packages" class="w-1/2 rounded-lg border-border bg-background focus-visible:ring-ring" />
              <Input v-model="formItem.package_type" class="w-1/2 rounded-lg border-border bg-background focus-visible:ring-ring" />
            </div>
          </div>
        </div>

        <!-- 第二排：仓储物流装箱要素（直接决定 Packing List 渲染） -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="space-y-2">
            <Label class="text-muted-foreground text-xs font-medium">总净重 (Net Weight KG)</Label>
            <Input type="number" step="0.01" v-model="formItem.net_weight" class="rounded-lg border-border bg-background focus-visible:ring-ring" />
          </div>
          <div class="space-y-2">
            <Label class="text-muted-foreground text-xs font-medium">总毛重 (Gross Weight KG)</Label>
            <Input type="number" step="0.01" v-model="formItem.gross_weight" class="rounded-lg border-border bg-background focus-visible:ring-ring" />
          </div>
          <div class="space-y-2">
            <Label class="text-muted-foreground text-xs font-medium">总体积 (Volume CBM)</Label>
            <Input type="number" step="0.001" v-model="formItem.volume" class="rounded-lg border-border bg-background focus-visible:ring-ring" />
          </div>
        </div>

        <!-- 动态申报要素生成核心区（海关合规审查核心） -->
        <div v-if="formItem.selectedTemplate.length > 0" class="border border-primary/20 bg-primary/5 rounded-xl p-4">
          <h4 class="text-primary font-bold tracking-wide mb-3">
            ⚠️ 依据 H.S. 编码必须依法填报的动态要素（海关规定）：
          </h4>
          <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <div v-for="(label, tIndex) in formItem.selectedTemplate" :key="tIndex" class="space-y-1">
              <Label class="text-muted-foreground text-xs font-medium">{{ tIndex + 1 }}. {{ label }}</Label>
              <Input 
                v-model="formItem.element_values[tIndex]" 
                :placeholder="'请输入' + label"
                class="h-8 text-xs rounded-lg focus-visible:ring-ring bg-background border-border" 
              />
            </div>
          </div>
          
          <!-- 实时要素串预览 -->
          <div class="mt-3 bg-card border border-border rounded-lg p-3 text-success font-mono text-[11px] break-all">
            报关系统生成字符串预览：
            <span>
              {{ Object.keys(formItem.element_values).map(Number).sort((a, b) => a - b).map(k => formItem.element_values[k] || '-').join('|') }}|
            </span>
          </div>
        </div>

      </div>

      <div class="flex justify-end pt-4">
        <Button class="px-8 rounded-md bg-primary hover:brightness-110 text-primary-foreground shadow-lg shadow-primary/20 transition-all duration-200 hover:scale-[1.02]" @click="submitForm">
          保存并自动分发五个维度单据
        </Button>
      </div>
    </CardContent>
  </Card>
</template>
