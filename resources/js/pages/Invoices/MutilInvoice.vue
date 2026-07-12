<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { computed } from 'vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'ERP/TMS' },
];
defineProps<{
  suppliers: Array<{ id: number, company_name: string }>
  activeContracts: Array<{ id: number, contract_no: string }> // 全系统正在流转的有效合同列表
}>()

// 🎯 初始化多订单合并分摊表单
const form = useForm({
  supplier_id: '',
  invoice_code: '',
  invoice_no: '',
  issue_date: new Date().toISOString().split('T')[0],
  total_amount: 0,
  tax_rate: '13',
  allocations: [
    { contract_id: '', amount: 0 } // 默认第一行分配插槽
  ]
})

// 动态添加分摊合同行（点击后多核销一个订单）
const addAllocationRow = () => {
  form.allocations.push({ contract_id: '', amount: 0 })
}

// 移除某一行
const removeAllocationRow = (index: number) => {
  form.allocations.splice(index, 1)
}

// 🎯 核心风控计算：实时反馈当前已经分配出去的金额总和
const currentAllocatedSum = computed(() => {
  return form.allocations.reduce((sum, item) => sum + (Number(item.amount) || 0), 0)
})

// 🎯 核心风控判定：发票账目是否完全配平
const isBalanced = computed(() => {
  return Math.abs(currentAllocatedSum.value - Number(form.total_amount)) < 0.01 && Number(form.total_amount) > 0
})

const submitMultiInvoice = () => {
  if (!isBalanced.value) {
    alert('系统拦截：当前分配给各合同的金额总和与发票票面总面额不相等，请配平后再提交！')
    return
  }

  // 🎯 使用 Wayfinder 硬编码静态路径将分摊大包推向后端
  form.post('/financials/multi-invoice', {
    onSuccess: () => {
      form.reset('invoice_code', 'invoice_no', 'total_amount')
      form.allocations = [{ contract_id: '', amount: 0 }]
      alert('大额合并发票跨订单多商户分摊勾稽成功！')
    }
  })
}
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
  <Card class="border-amber-200 bg-amber-50/5 shadow-md">
    <CardHeader class="bg-amber-50/20 border-b pb-3">
      <CardTitle class="text-sm font-bold text-amber-900 flex justify-between items-center">
        <span>⚡ 大额多订单合并专票 智能分摊勾稽中心</span>
        <Button size="sm" variant="outline" class="h-7 text-xs border-amber-300" @click="addAllocationRow">
          ➕ 增加分摊扣减合同
        </Button>
      </CardTitle>
      <CardDescription>当工厂将多笔出口业务开在同一张增值税发票上时，在此进行差额拆分挂账</CardDescription>
    </CardHeader>
    <CardContent class="pt-4 space-y-4">
      
      <!-- 发票主面额基础录入 -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-3 text-xs">
        <div class="space-y-1">
          <Label>销售方开票工厂</Label>
          <select v-model="form.supplier_id" class="w-full h-8 rounded-md border bg-white px-2 text-xs shadow-sm">
            <option value="" disabled>选择工厂...</option>
            <option v-for="sup in suppliers" :key="sup.id" :value="sup.id">{{ sup.company_name }}</option>
          </select>
        </div>
        <div class="space-y-1"><Label>发票号码</Label><Input v-model="form.invoice_no" class="h-8 font-mono" placeholder="8位号码" /></div>
        <div class="space-y-1"><Label>发票总面额 (价税合计)</Label><Input type="number" v-model="form.total_amount" class="h-8 font-bold font-mono text-slate-900" placeholder="0.00" /></div>
        <div class="space-y-1"><Label>开票日期</Label><Input type="date" v-model="form.issue_date" class="h-8" /></div>
      </div>

      <!-- 动态分配合同明细网格区 -->
      <div class="border-t pt-3 space-y-2">
        <span class="text-[11px] font-bold text-foreground block mb-1">📋 跨订单金额分摊细则（必须精确配平）：</span>
        
        <div v-for="(alloc, index) in form.allocations" :key="index" class="flex gap-3 items-center bg-white p-2 border rounded-lg shadow-sm">
          <div class="w-8 text-center text-xs font-mono text-muted-foreground">#{{ index + 1 }}</div>
          
          <!-- 选择目标合同 -->
          <div class="flex-1">
            <select v-model="alloc.contract_id" class="w-full h-8 rounded-md border text-xs bg-slate-50/50 px-2">
              <option value="" disabled>挑选分摊扣减的目标出口合同号...</option>
              <option v-for="con in activeContracts" :key="con.id" :value="con.id">
                合同号: {{ con.contract_no }}
              </option>
            </select>
          </div>

          <!-- 分摊给该合同的金额 -->
          <div class="w-48">
            <Input type="number" v-model="alloc.amount" class="h-8 text-xs font-mono" placeholder="分摊含税金额" />
          </div>

          <!-- 移除行 -->
          <Button type="button" variant="ghost" size="sm" class="h-8 px-2 text-red-500 hover:text-red-700" @click="removeAllocationRow(index)">
            ✕
          </Button>
        </div>
      </div>

      <!-- 底部风控配平检测沙盘 -->
      <div class="border-t pt-3 flex flex-row justify-between items-center text-xs">
        <div class="space-y-0.5">
          <div class="text-muted-foreground font-mono">发票总面额：￥{{ Number(form.total_amount).toFixed(2) }}</div>
          <div class="font-mono" :class="isBalanced ? 'text-green-600 font-bold' : 'text-red-500'">
            已分摊总计：￥{{ currentAllocatedSum.toFixed(2) }} 
            <span>({{ isBalanced ? '已配平' : '差额: ￥' + (Number(form.total_amount) - currentAllocatedSum).toFixed(2) }})</span>
          </div>
        </div>
        <Button 
          class="h-8 text-xs font-bold px-6"
          :class="isBalanced ? 'bg-amber-600 hover:bg-amber-700 text-white' : 'bg-slate-200 text-muted-foreground cursor-not-allowed'"
          :disabled="!isBalanced || form.processing"
          @click="submitMultiInvoice"
        >
          确认大额专票分摊挂账
        </Button>
      </div>

    </CardContent>
  </Card>
  </AppLayout>
</template>
