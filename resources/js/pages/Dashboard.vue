<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import ExportDocumentController from '@/actions/App/Http/Controllers/ExportDocumentController'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Progress } from '@/components/ui/progress'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import contractsRouter from '@/routes/contracts'
import { analytics } from '@/routes/export'
import importers from '@/routes/importers'
import invoices from '@/routes/invoices'
import items from '@/routes/items'
import suppliers from '@/routes/suppliers'

// 🎯 导入指标契约强类型
interface DashboardMetrics {
  total_export_usd: number
  total_export_rmb: number
  total_purchase_amount: number
  total_received_invoice: number
  total_estimated_refund: number
  total_actual_refund: number
  total_gross_profit: number
  pending_invoice_contracts_count: number
  pending_refund_contracts_count: number
}

interface ActiveContractStub {
  id: number
  contract_no: string
  contract_date: string
  status: 'draft' | 'active' | 'shipped' | 'completed' | 'cancelled'
  invoice_status: 'none' | 'partial' | 'fully_issued'
  refund_status: 'none' | 'processing' | 'received'
  total_amount: number
}
// resources/js/Pages/Dashboard.vue 顶部 Interface 扩充

interface SupplierRefundRank {
  id: number
  company_name: string  // 工厂名称 (如: 桐庐华艺针织有限公司)
  total_purchase: number // 该工厂含税采购总额
  computed_refund: number // 🎯 该工厂为我方贡献的预计出口退税总资金
  share_rate: number     // 该工厂在全大盘里的资金占比百分比
}

// 在已有的 props 声明里追加该数组类型
const props = defineProps<{
  metrics: DashboardMetrics
  recentContracts: ActiveContractStub[]
  supplierRefundRanking: SupplierRefundRank[] // 👈 笔直插入强类型契约
}>()

// 进度合规百分比动态计算
// const invoiceProgress = computed(() => {
//   const total = props.metrics.total_purchase_amount
//   return total > 0 ? (props.metrics.total_received_invoice / total) * 100 : 0
// })
const invoiceProgress = computed(() => {
  const total = props.metrics.total_purchase_amount
  if (total <= 0) return 0
  
  const percentage = (props.metrics.total_received_invoice / total) * 100
  // 限制最大值为 100，防止组件报错
  return Math.min(100, percentage)
})

const refundProgress = computed(() => {
  const estimated = props.metrics.total_estimated_refund
  return estimated > 0 ? (props.metrics.total_actual_refund / estimated) * 100 : 0
})

const statusStyles = {
  draft: 'bg-secondary text-secondary-foreground border-secondary',
  active: 'bg-primary text-primary-foreground border-primary',
  shipped: 'bg-secondary text-secondary-foreground border-secondary',
  completed: 'bg-success text-success-foreground border-success',
  cancelled: 'bg-destructive text-destructive-foreground border-destructive'
}
</script>

<template>
  <div class="min-h-screen p-8 max-w-7xl mx-auto space-y-6 bg-background text-foreground">
    
    <!-- 头部欢迎盘 -->
    <div class="flex justify-between items-center">
      <div>
        <h1 class="text-3xl font-bold tracking-tight bg-linear-to-r from-primary to-success bg-clip-text text-transparent">外贸业财税经营大脑 (Dashboard)</h1>
        <p class="text-muted-foreground leading-6">实时穿透全网进销项资产，统筹物流单证分发与国库退税大盘</p>
      </div>
      <div class="text-xs font-mono bg-card border border-border rounded-xl shadow-lg shadow-primary/5 text-primary px-3 py-1.5">
        ⚡ 汇率基准: 6.7000 | 安全链在线
      </div>
    </div>

    <!-- ================== 1. 核心四大财务指标矩阵 ================== -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      
      <!-- 销项总额 -->
      <Card class="bg-card border-border rounded-2xl shadow-lg shadow-primary/5">
        <CardHeader class="pb-2">
          <CardTitle class="text-xs font-bold text-muted-foreground uppercase tracking-wider">① 出口外销总额</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="text-foreground font-black text-2xl">￥{{ metrics.total_export_rmb.toLocaleString() }}</div>
          <p class="text-xs text-muted-foreground mt-1 font-mono">${{ metrics.total_export_usd.toLocaleString() }} USD</p>
        </CardContent>
      </Card>

      <!-- 进项采购 -->
      <Card class="bg-card border-border rounded-2xl shadow-lg shadow-primary/5">
        <CardHeader class="pb-2">
          <CardTitle class="text-xs font-bold text-muted-foreground uppercase tracking-wider">② 上游含税采购额</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="text-foreground font-black text-2xl">￥{{ metrics.total_purchase_amount.toLocaleString() }}</div>
          <div class="flex items-center gap-2 mt-1.5">
            <span class="text-[10px] text-muted-foreground">专票回收率:</span>
            <Progress :model-value="invoiceProgress" class="h-1.5 w-16" />
            <span class="text-[10px] font-mono font-bold text-primary">{{ invoiceProgress.toFixed(1) }}%</span>
          </div>
        </CardContent>
      </Card>

      <!-- 资金留存 -->
      <Card class="bg-card border-border rounded-2xl shadow-lg shadow-primary/5">
        <CardHeader class="pb-2">
          <CardTitle class="text-xs font-bold text-success uppercase tracking-wider">③ 出口退税留存</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="text-success font-black tracking-wide text-2xl">￥{{ metrics.total_actual_refund.toLocaleString() }}</div>
          <div class="flex items-center gap-2 mt-1.5">
            <span class="text-[10px] text-muted-foreground">退税到账率:</span>
            <Progress :model-value="refundProgress" class="h-1.5 w-16" />
            <span class="text-[10px] font-mono font-bold text-success">{{ refundProgress.toFixed(1) }}%</span>
          </div>
        </CardContent>
      </Card>

      <!-- 核心毛利润 -->
      <Card class="bg-card border-border rounded-2xl shadow-lg shadow-primary/5">
        <CardHeader class="pb-2">
          <CardTitle class="text-xs font-bold text-success uppercase tracking-wider">④ 外贸综合毛利润</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="text-success font-black tracking-wide text-2xl">￥{{ metrics.total_gross_profit.toLocaleString() }}</div>
          <p class="text-[10px] text-muted-foreground mt-1">公式：销项(RMB) - 进项含税 + 预估退税</p>
        </CardContent>
      </Card>

    </div>
    <!-- ================== ⚡ 核心新加：出口全流程业务快捷通关入口 ================== -->
    <Card class="bg-card border-border rounded-2xl shadow-lg shadow-primary/5">
      <CardHeader class="border-b border-border pb-4">
        <CardTitle class="text-primary font-bold flex items-center gap-2">
          <span>⚡ 出口合规业务一键通关（快速入口中心）</span>
        </CardTitle>
        <CardDescription class="text-muted-foreground text-xs leading-5">摒弃繁琐菜单寻找，针对高频涉外业务实施跨步级一键秒级流转</CardDescription>
      </CardHeader>
      <CardContent class="pt-4">
        <div class="grid grid-cols-2 md:grid-cols-6 gap-3">
          <!-- 入口 1：起草合同 -->
          <Link
            :href="contractsRouter.create()"
            class="bg-card border border-border rounded-xl hover:bg-primary/5 hover:border-primary/20 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md group flex flex-col items-center justify-center p-3 text-center"
          >
            <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center text-lg mb-2 transition-transform group-hover:scale-105">
              📝
            </div>
            <span class="text-xs font-semibold text-foreground">起草新出口合同</span>
            <span class="text-[10px] font-mono text-muted-foreground mt-1 tracking-wide">contracts.create</span>
          </Link>
          <!-- 入口 2：发票总台账与分摊沙盘 -->
          <Link
            :href="invoices.index()"
            class="bg-card border border-border rounded-xl hover:bg-primary/5 hover:border-primary/20 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md group flex flex-col items-center justify-center p-3 text-center"
          >
            <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center text-lg mb-2 transition-transform group-hover:scale-105">
              🧾
            </div>
            <span class="text-xs font-semibold text-foreground">20位大额专票核销</span>
            <span class="text-[10px] font-mono text-muted-foreground mt-1 tracking-wide">invoices.index</span>
          </Link>
          <!-- 入口 3：经营分析大盘 -->
          <Link
            :href="analytics.url()"
            class="bg-card border border-border rounded-xl hover:bg-primary/5 hover:border-primary/20 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md group flex flex-col items-center justify-center p-3 text-center"
          >
            <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center text-lg mb-2 transition-transform group-hover:scale-105">
              📊
            </div>
            <span class="text-xs font-semibold text-foreground">毛利退税全盘透视</span>
            <span class="text-[10px] font-mono text-muted-foreground mt-1 tracking-wide">analytics.index</span>
          </Link>
          <!-- 入口 4：基础大货商品建档 -->
          <Link
            :href="items.create()"
            class="bg-card border border-border rounded-xl hover:bg-primary/5 hover:border-primary/20 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md group flex flex-col items-center justify-center p-3 text-center"
          >
            <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center text-lg mb-2 transition-transform group-hover:scale-105">
              📦
            </div>
            <span class="text-xs font-semibold text-foreground">新增海关编码商品</span>
            <span class="text-[10px] font-mono text-muted-foreground mt-1 tracking-wide">items.create</span>
          </Link>
          <!-- 入口 5：供货商资质建立 -->
          <Link
            :href="suppliers.create()"
            class="bg-card border border-border rounded-xl hover:bg-primary/5 hover:border-primary/20 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md group flex flex-col items-center justify-center p-3 text-center"
          >
            <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center text-lg mb-2 transition-transform group-hover:scale-105">
              🏭
            </div>
            <span class="text-xs font-semibold text-foreground">登记国内供货工厂</span>
            <span class="text-[10px] font-mono text-muted-foreground mt-1 tracking-wide">suppliers.create</span>
          </Link>
          <!-- 入口 6：境外进口商建档 -->
          <Link
            :href="importers.create()"
            class="bg-card border border-border rounded-xl hover:bg-primary/5 hover:border-primary/20 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md group flex flex-col items-center justify-center p-3 text-center"
          >
            <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center text-lg mb-2 transition-transform group-hover:scale-105">
              🌍
            </div>
            <span class="text-xs font-semibold text-foreground">录入国外清关买方</span>
            <span class="text-[10px] font-mono text-muted-foreground mt-1 tracking-wide">importers.create</span>
          </Link>
        </div>
      </CardContent>
    </Card>

    <!-- ================== 2. 运营与生产商退税统筹看板网格 ================== -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      
      <!-- 栏目 A：左侧 1/3 航道 ➜ 级联风控与工厂退税占比排行 -->
      <div class="space-y-6 lg:col-span-1">
        
        <!-- 挂件 1：风控审计通知栏 -->
        <Card class="bg-card border-border rounded-2xl shadow-lg shadow-primary/5">
          <CardHeader class="border-b border-border pb-3">
            <CardTitle class="text-primary font-bold flex items-center gap-2">⚠️ 供应链合规风控提醒</CardTitle>
          </CardHeader>
          <CardContent class="space-y-2.5">
            <div class="p-2.5 bg-secondary/40 border border-border rounded-xl flex justify-between items-center text-xs">
              <div><span class="font-semibold text-foreground">缺失增值税发票</span><p class="text-muted-foreground text-[9px]">需要补催工厂寄送20位专票</p></div>
              <Badge variant="destructive" class="font-mono text-[10px]">{{ metrics.pending_invoice_contracts_count }} 笔</Badge>
            </div>
            <div class="p-2.5 bg-secondary/40 border border-border rounded-xl flex justify-between items-center text-xs">
              <div><span class="font-semibold text-foreground">退税款尚未到账</span><p class="text-muted-foreground text-[9px]">处于海关放行/税局审核中</p></div>
              <Badge variant="outline" class="font-mono border-warning/20 bg-warning/10 text-warning text-[10px]">{{ metrics.pending_refund_contracts_count }} 笔</Badge>
            </div>
          </CardContent>
        </Card>

        <!-- 🎯 挂件 2：核心补齐——生产商开票与可退税额 Top 5 大盘排行 -->
        <Card class="bg-card border-border rounded-2xl shadow-lg shadow-primary/5">
          <CardHeader class="border-b border-border pb-3">
            <CardTitle class="text-primary font-bold flex items-center justify-between">
              <span>🔥 核心工厂退税留存贡献排行 (Top 5)</span>
              <Badge variant="outline" class="text-[9px] bg-primary/10 text-primary border border-primary/20">资金占比</Badge>
            </CardTitle>
            <CardDescription class="text-muted-foreground text-[10px]">穿透测算各工厂实际下单金额对应的退税留存重</CardDescription>
          </CardHeader>
          <CardContent class="space-y-3.5 pt-2">
            
            <div v-for="(rank, idx) in supplierRefundRanking" :key="rank.id" class="space-y-1.5 text-xs animate-fadeIn">
              <div class="flex justify-between items-center font-medium">
                <span class="truncate max-w-40 text-foreground">
                  <strong class="font-mono text-muted-foreground mr-1">#{{ idx + 1 }}</strong> {{ rank.company_name }}
                </span>
                <!-- resources/js/Pages/Dashboard.vue 核心修正片段 -->

                <span class="font-mono font-black text-success tracking-wide">
                  <!-- 🎯 核心修正：拿掉多余的赋值伪代码，直接将数字进行高保真千分位格式化 -->
                  ￥{{ rank.computed_refund }}
                </span>

              </div>
              
              <!-- 动态占比百分比进度条（直观反映如：华艺占比 45% 等视觉特效） -->
              <div class="flex items-center gap-2">
                <Progress :model-value="rank.share_rate" class="h-1.5 flex-1" />
                <span class="font-mono text-[10px] font-black text-primary w-8 text-right">
                  {{ rank.share_rate }}%
                </span>
              </div>
            </div>

            <!-- 空工厂数据兜底 -->
            <div v-if="supplierRefundRanking.length === 0" class="text-center py-4 text-muted-foreground italic text-[10px]">
              暂无大货商品绑定国内生产商
            </div>

          </CardContent>
        </Card>

      </div>

      <!-- 栏目 B：右侧 2/3 航道 ➜ 最近核心流转业务生命周期监测台账（保持原样） -->
      <div class="lg:col-span-2">
        <Card class="bg-card border-border rounded-2xl shadow-lg shadow-primary/5 h-full">
          <CardHeader class="border-b border-border pb-3">
            <CardTitle class="text-primary font-bold">核心流转业务订单状态监测</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="rounded-xl border border-border overflow-hidden bg-card">
              <Table>
                <TableHeader>
                  <TableRow class="bg-primary text-primary-foreground"><TableHead class="font-mono font-bold tracking-wide text-primary">出口合同号</TableHead><TableHead class="text-center">业务生命周期</TableHead><TableHead class="text-center">工厂 20 位专票</TableHead><TableHead class="text-center">出口退税进度</TableHead><TableHead class="text-right">工作台分发</TableHead></TableRow>
                </TableHeader>
                <TableBody>
                  <TableRow v-for="con in recentContracts" :key="con.id" class="hover:bg-secondary/40 transition-colors">
                    <TableCell class="font-mono font-bold tracking-wide text-primary">{{ con.contract_no }}</TableCell>
                    <TableCell class="text-center">
                      <Badge variant="outline" :class="statusStyles[con.status]" class="text-[10px] uppercase font-black px-1.5 py-0.5">
                        {{ con.status }}
                      </Badge>
                    </TableCell>
                    <TableCell class="text-center font-medium">
                      <span v-if="con.invoice_status === 'none'" class="text-destructive font-bold bg-secondary/10 px-1 py-0.5 rounded border border-destructive">未收票</span>
                      <span v-else-if="con.invoice_status === 'partial'" class="text-warning font-medium bg-secondary/10 px-1 py-0.5 rounded border border-warning">分批中</span>
                      <span v-else class="text-success font-bold bg-secondary/10 px-1 py-0.5 rounded border border-success">✔ 已齐</span>
                    </TableCell>
                    <TableCell class="text-center font-medium">
                      <span v-if="con.refund_status === 'none'" class="text-muted-foreground">未申报</span>
                      <span v-else-if="con.refund_status === 'processing'" class="text-warning font-bold animate-pulse">税局审核中</span>
                      <span v-else class="text-success font-black flex items-center justify-center gap-0.5">✔ 已到账</span>
                    </TableCell>
                    <TableCell class="text-right">
                      <Button size="sm" class="border-primary/20 bg-primary/5 text-primary hover:bg-primary hover:text-primary-foreground rounded-md transition-colors h-6 text-[10px]">
                        <Link :href="ExportDocumentController.show(con.id)">进入单证台</Link>
                      </Button>
                    </TableCell>
                  </TableRow>
                </TableBody>
              </Table>
            </div>
          </CardContent>
        </Card>
      </div>

    </div>


  </div>
</template>
