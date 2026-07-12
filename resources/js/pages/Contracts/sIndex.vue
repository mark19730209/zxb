<script setup lang="ts">
import { router, Link } from '@inertiajs/vue3'
import { debouncedWatch } from '@vueuse/core' 
import { ref } from 'vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import AppLayout from '@/layouts/AppLayout.vue';
import contractsRoute from '@/routes/contracts' // 🎯 引入 Wayfinder 路由
import { type BreadcrumbItem } from '@/types';
import type { ContractPageProps, Contract } from '@/types/tms'

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'ERP/TMS' },
    { title: 'Contracts', href: contractsRoute.index().url },
];
const props = defineProps<ContractPageProps>()
const search = ref(props.filters.search || '')

debouncedWatch(search, (value) => {
  
  // 1. 从 Wayfinder 路由对象中提取出纯字符串 URL（通常可以直接调 .url()）
  const url = contractsRoute.index.url() 

  // 2. 正确将 URL 字符串传递给 router.get
  router.get(
    url, 
    { search: value }, 
    { preserveState: true, replace: true }
  )
  
}, { debounce: 300 }) // 👈 这里的 300ms 才会真正生效

// 🎯 触发 Wayfinder 状态机变更
// const changeStatus = (id, targetStatus) => {
//   router.patch(contractsRoute.updateStatus(id), { status: targetStatus }, {
//     preserveScroll: true
//   })
// }
const handleStatusTransition = (id: number, targetStatus: Contract['status']) => {
  if (confirm(`制单系统风控提示：确认要将该合同的出口业务生命周期切换至 [${targetStatus.toUpperCase()}] 吗？`)) {
    
    // 🎯 完全使用 Wayfinder 的硬编码静态路径发送 PATCH 请求进行状态跃迁
    router.patch(`/contracts/${id}/status`, { status: targetStatus }, {
      preserveScroll: true,
      onSuccess: () => alert('业务生命周期跃迁成功！对应的多商户财务看板和退税基数已全网刷新。'),
      onError: (errors) => {
        // 捕获后端 ContractController@updateStatus 抛出的发票未全齐、退税拦截等合规报错
        alert(`流转触发失败：${errors.status || '检测到合规审计拦截，请检查开票或物流参数。'}`)
      }
    })
  }
}

// 状态视觉样式映射
// const statusStyles = {
//   draft: 'bg-slate-100 text-slate-700 border-slate-200',
//   active: 'bg-blue-50 text-blue-700 border-blue-200',
//   shipped: 'bg-amber-50 text-amber-700 border-amber-200',
//   completed: 'bg-green-50 text-green-700 border-green-200',
//   cancelled: 'bg-red-50 text-red-700 border-red-200'
// }

// 状态对应的纯文字翻译映射
const statusTextMap: Record<Contract['status'], string> = {
  draft: '草稿起草 (DRAFT)',
  active: '合同生效 (ACTIVE)',
  shipped: '货物已发运 (SHIPPED)',
  completed: '业务结案 (COMPLETED)',
  cancelled: '业务作废 (CANCELLED)'
}
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
  <div class="p-8 max-w-7xl mx-auto space-y-6 text-foreground">
    <div class="flex justify-between items-center">
      <div>
        <h1 class="text-2xl font-bold tracking-tight">出口业务合同台账 (Wayfinder)</h1>
        <p class="text-sm text-muted-foreground">统筹全外贸生命周期状态，联动管控销项单据分发、国内工厂专票和出口退税</p>
      </div>
      <Button as-child>
        <Link :href="contractsRoute.create()">+ 起草新出口合同</Link>
      </Button>
    </div>

    <Card>
      <CardHeader class="pb-3 flex flex-row items-center justify-between space-y-0">
        <CardTitle class="text-base font-bold">主订单业务流水</CardTitle>
        <div class="w-72">
          <Input v-model="search" placeholder="输入合同号搜索..." class="h-9" />
        </div>
      </CardHeader>
      <CardContent>
        <div class="rounded-md border">
          <Table>
            <TableHeader>
              <TableRow class="bg-muted/50 text-xs">
                <TableHead>合同号/签约日期</TableHead>
                <TableHead>贸易条件</TableHead>
                <TableHead class="text-center">业务生命周期</TableHead>
                <TableHead class="text-center">工厂专票回收</TableHead>
                <TableHead class="text-center">出口退税进度</TableHead>
                <TableHead class="w-45 text-center font-bold">工作台分发</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="con in contracts.data" :key="con.id" class="hover:bg-muted/50 text-sm">
                
                <!-- 维度 1：基本订单号 -->
                <TableCell>
                  <div class="font-bold text-foreground">{{ con.contract_no }}</div>
                  <div class="text-xs text-muted-foreground font-mono">{{ con.contract_date }}</div>
                </TableCell>
                
                <!-- 维度 2：涉外条件 -->
                <TableCell>
                  <div class="font-mono text-xs font-semibold">{{ con.incoterms }} | {{ con.currency }}</div>
                  <div class="text-xs text-muted-foreground max-w-35 truncate">{{ con.payment_terms }}</div>
                </TableCell>
                
                <!-- 维度 3：业务状态机（支持直接在页面下拉或快捷流转，此处用Badge展示） -->
                <!-- <TableCell class="text-center">
                  <Badge variant="outline" :class="statusStyles[con.status]" class="uppercase text-[11px] px-2 py-0.5 font-bold">
                    {{ con.status }}
                  </Badge>
                </TableCell> -->
                <!-- 🎯 维度 3：核心修正点 - 静态 Badge 升级为动态状态机控制下拉框 -->
                <TableCell class="text-center">
                  <select 
                    v-model="con.status" 
                    @change="handleStatusTransition(con.id, con.status)"
                    class="h-8 rounded-md border bg-white px-2 py-1 text-xs font-bold tracking-wide text-slate-800 shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                    :class="{
                      'text-slate-600 bg-slate-50': con.status === 'draft',
                      'text-primary bg-blue-50/30': con.status === 'active',
                      'text-amber-600 bg-amber-50/30': con.status === 'shipped',
                      'text-green-600 bg-green-50/30': con.status === 'completed',
                      'text-red-600 bg-red-50/30': con.status === 'cancelled'
                    }"
                  >
                      <!-- 🎯 核心修正：利用 Object.keys 动态循环渲染，让 statusTextMap 真正生效！ -->
                    <option 
                      v-for="(text, val) in statusTextMap" 
                      :key="val" 
                      :value="val"
                    >
                      {{ text }}
                    </option>
                    <!-- <option value="draft">1. 草稿起草</option>
                    <option value="active">2. 合同生效</option>
                    <option value="shipped">3. 货物已发运</option>
                    <option value="completed">4. 业务结案</option>
                    <option value="cancelled">✕ 业务作废</option> -->
                  </select>
                </TableCell>
                <!-- 维度 4：国内工厂进货发票开具勾稽状态 -->
                <TableCell class="text-center">
                  <Badge v-if="con.invoice_status === 'none'" variant="secondary" class="bg-red-50 text-red-600">未开票</Badge>
                  <Badge v-else-if="con.invoice_status === 'partial'" variant="secondary" class="bg-amber-50 text-amber-600">分批开票中</Badge>
                  <Badge v-else variant="secondary" class="bg-green-50 text-green-600">专票全齐</Badge>
                </TableCell>

                <!-- 维度 5：向税务局申请出口退税状态 -->
                <TableCell class="text-center">
                  <span v-if="con.refund_status === 'none'" class="text-xs text-muted-foreground">未申报</span>
                  <span v-else-if="con.refund_status === 'processing'" class="text-xs text-amber-500 font-medium">税局审核中</span>
                  <span v-else class="text-xs text-green-600 font-bold flex items-center justify-center gap-1">✔ 已到账</span>
                </TableCell>

                <!-- 操作区：直接一键跃迁到最核心的“五个维度单据生成台”或“财务核销台” -->
                <TableCell class="text-center">
                  <div class="flex justify-center gap-2.5">
                    <!-- 🎯 跃迁到第一步实现的 5维单据生成看板 -->
                    <Button as-child size="sm" variant="outline" class="h-7 text-xs">
                      <Link :href="`/contracts/${con.id}/documents`">📄 制单工作台</Link>
                    </Button>
                    
                    <!-- 🎯 跃迁到第三步实现的 财务核销大盘 -->
                    <Button as-child size="sm" variant="outline" class="h-7 text-xs">
                      <Link :href="`/contracts/${con.id}/financials`">💰 专票退税</Link>
                    </Button>
                    <Button as-child size="sm" variant="outline" class="h-7 text-xs">
                      <Link :href="`/contracts/${con.id}/items`">💰 合约</Link>
                    </Button>
                  </div>
                </TableCell>

              </TableRow>
              <TableRow v-if="contracts.data.length === 0">
                <TableCell colspan="6" class="text-center py-8 text-xs text-muted-foreground">当前没有处于生命周期内的出口合同记录</TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>
      </CardContent>
    </Card>
  </div>
  </AppLayout>
</template>
