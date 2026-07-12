<script setup lang="ts">
import { ref, computed } from 'vue'
// import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Progress } from '@/components/ui/progress'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'

// 后端下发的综合财务属性
const props = defineProps({
  contractId: Number,
  tracker: Object,       // 包含 purchase_total_amount, received_invoice_amount, estimated_refund
  existingInvoices: Array, // 已经录入的发票历史列表
  suppliers: Array         // 可供选择的国内供应商名录
})

// 模拟测试数据（如 props 为空时作为后备）
const mockSuppliers = [
  { id: 1, company_name: '常州紧固件制造厂' },
  { id: 2, company_name: '宁波声学电子工业有限公司' }
 ]

const mockInvoices = ref([
  { id: 1, supplier_name: '常州紧固件制造厂', invoice_no: '88301294', total_amount: 15000.00, issue_date: '2026-05-12' }
])

// 发票快捷表单控制
const newInvoice = ref({
  supplier_id: '',
  invoice_code: '',
  invoice_no: '',
  issue_date: '',
  total_amount: ''
})

// 动态计算发票到齐进度比例
const invoiceProgress = computed(() => {
  if (!props.tracker?.purchase_total_amount) return 0
  return (props.tracker.received_invoice_amount / props.tracker.purchase_total_amount) * 100
})

// const submitInvoice = () => {
//   console.log('提交发票核销请求负载:', newInvoice.value)
//   // 此处使用 Inertia.post(route('finance.invoice.register', props.contractId), newInvoice.value)
// }
</script>

<template>
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    
    <!-- 左侧：多商户核销与退税状态总体大盘 -->
    <div class="lg:col-span-2 space-y-6">
      <Card class="bg-card border-border rounded-2xl shadow-lg shadow-primary/5">
        <CardHeader class="border-b border-border">
          <CardTitle class="text-primary font-bold tracking-wide">国内进货发票合规勾稽核销</CardTitle>
          <CardDescription class="mt-1 text-muted-foreground leading-6">控制全流程进销项闭环，防止国内工厂少开、漏开发票导致无法退税</CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="flex justify-between items-center text-sm font-medium">
            <span>进货发票总回收进度：</span>
            <span class="text-primary font-extrabold tracking-wide">
              {{ invoiceProgress.toFixed(1) }}%
            </span>
          </div>
          <!-- 动态进度条组件 -->
          <Progress :model-value="invoiceProgress" class="h-2" />
          
          <div class="grid grid-cols-2 gap-4 pt-2 text-xs">
            <div class="p-4 bg-secondary/40 rounded-xl border border-border">
              <span class="text-muted-foreground">本期国内总采购应开：</span>
              <div class="text-base font-bold text-foreground">￥{{ tracker?.purchase_total_amount || '0.00' }}</div>
            </div>
            <div class="p-4 bg-secondary/40 rounded-xl border border-border">
              <span class="text-muted-foreground">各个工厂已寄回累计：</span>
              <div class="text-base font-black tracking-wide text-success">￥{{ tracker?.received_invoice_amount || '0.00' }}</div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- 已收到的专票历史清单 -->
      <Card class="bg-card border-border rounded-2xl shadow-lg shadow-primary/5">
        <CardHeader class="border-b border-border">
          <CardTitle class="text-sm font-bold">已认证专票历史回档</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="rounded-xl overflow-hidden border border-border">
            <Table>
              <TableHeader>
                <TableRow class="bg-primary text-primary-foreground">
                  <TableHead>供货商/开票商户</TableHead>
                  <TableHead>发票号码</TableHead>
                  <TableHead>开票日期</TableHead>
                  <TableHead class="text-right">含税总额</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow v-for="inv in mockInvoices" :key="inv.id">
                  <TableCell class="font-medium text-xs">{{ inv.supplier_name }}</TableCell>
                  <TableCell class="font-mono text-xs tracking-wider text-primary">{{ inv.invoice_no }}</TableCell>
                  <TableCell class="text-xs">{{ inv.issue_date }}</TableCell>
                  <TableCell class="text-right text-xs font-black tracking-wide text-success">￥{{ inv.total_amount.toFixed(2) }}</TableCell>
                </TableRow>
                <TableRow v-if="mockInvoices.length === 0">
                  <TableCell colspan="4" class="text-center text-xs text-muted-foreground py-4">暂无任何进货发票回档记录</TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- 右侧：快捷登记侧边窗格（支持财务收到纸质发票直接录入） -->
    <div>
      <Card class="bg-card border-border rounded-2xl shadow-lg shadow-primary/5">
        <CardHeader class="border-b border-border">
          <CardTitle class="text-primary font-bold flex items-center gap-2">
            <span>⚡ 新收进货增值税专票登记</span>
          </CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="space-y-1.5">
            <Label class="text-xs">销售方（国内供应商）</Label>
            <select v-model="newInvoice.supplier_id" class="w-full h-9 rounded-lg border border-border bg-background px-3 py-1 text-sm shadow-sm focus:ring-2 focus:ring-ring focus:outline-none transition-colors">
              <option value="" disabled>请选择对应供货工厂...</option>
              <option v-for="sup in mockSuppliers" :key="sup.id" :value="sup.id">
                {{ sup.company_name }}
              </option>
            </select>
          </div>

          <div class="grid grid-cols-2 gap-2">
            <div class="space-y-1.5">
              <Label class="text-xs">发票代码</Label>
              <Input v-model="newInvoice.invoice_code" placeholder="12位代码" class="h-8 text-xs rounded-lg border-border bg-background focus-visible:ring-ring"/>
            </div>
            <div class="space-y-1.5">
              <Label class="text-xs">发票号码</Label>
              <Input v-model="newInvoice.invoice_no" placeholder="8位号码" class="h-8 text-xs rounded-lg border-border bg-background focus-visible:ring-ring"/>
            </div>
          </div>

          <div class="space-y-1.5">
            <Label class="text-xs">开票日期</Label>
            <Input type="date" v-model="newInvoice.issue_date" class="h-8 text-xs rounded-lg border-border bg-background focus-visible:ring-ring"/>
          </div>

          <div class="space-y-1.5">
            <Label class="text-xs">发票含税总金额 (价税合计)</Label>
            <div class="relative">
              <span class="absolute left-2.5 top-1.5 text-xs text-muted-foreground">￥</span>
              <Input type="number" step="0.01" v-model="newInvoice.total_amount" class="pl-7 h-8 text-xs font-black text-success rounded-lg border-border bg-background focus-visible:ring-ring" placeholder="0.00"/>
            </div>
          </div>

          <Button class="w-full mt-2 h-9 rounded-md bg-primary hover:brightness-110 text-primary-foreground shadow-lg shadow-primary/20 transition-all duration-200 hover:scale-[1.02]">
            录入并智能挂账核销
          </Button>
        </CardContent>
      </Card>
    </div>

  </div>
</template>
