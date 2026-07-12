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

const statusTextMap: Record<Contract['status'], string> = {
  draft: '草稿起草 (DRAFT)',
  active: '合同生效 (ACTIVE)',
  shipped: '货物已发运 (SHIPPED)',
  completed: '业务结案 (COMPLETED)',
  cancelled: '业务作废 (CANCELLED)'
}

const statusStyles = {
  draft: 'bg-muted text-muted-foreground border-border',
  active: 'bg-primary/10 text-primary border-primary/20',
  shipped: 'bg-warning/15 text-warning border-warning/20',
  completed: 'bg-success/15 text-success border-success/20',
  cancelled: 'bg-destructive/15 text-destructive border-destructive/20'
}

const formatSmartNumber = (val: any) => {
  if (val === undefined || val === null || isNaN(val)) return '0';
  return Number(val).toLocaleString('zh-CN', {
    minimumFractionDigits: 0, // 整数不显示小数点
    maximumFractionDigits: 2  // 最多显示2位小数（可调）
  });
};
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
  <div class="min-h-screen p-8 max-w-7xl mx-auto space-y-6 bg-background text-foreground">
    <div class="flex justify-between items-center">
      <div>
        <h1 class="text-2xl font-bold tracking-tight bg-linear-to-r from-primary to-success bg-clip-text text-transparent">出口业务合同综合集成大盘</h1>
        <p class="text-sm text-muted-foreground mt-1">一页贯通全系统出口合同号，向下穿透穿视大货物品名称、订舱数量及外销价格明细</p>
      </div>
      <Button class="bg-primary hover:brightness-110 text-primary-foreground shadow-lg shadow-primary/20 transition-all duration-200 hover:scale-[1.02] rounded-md">
        <Link :href="contractsRoute.create()">+ 起草新出口合同</Link>
      </Button>
    </div>

    <Card class="bg-card border-border rounded-2xl shadow-lg shadow-primary/5">
      <CardHeader class="pb-3 flex flex-row items-center justify-between space-y-0 border-b border-border">
        <CardTitle class="text-base font-bold text-foreground">外贸主台账大盘</CardTitle>
        <div class="w-72">
          <Input v-model="search" placeholder="输入合同号搜索..." class="h-9 rounded-lg border-border bg-background focus-visible:ring-ring" />
        </div>
      </CardHeader>
      <CardContent>
        <div class="rounded-xl border border-border overflow-hidden bg-card">
          <Table>
            <TableHeader>
              <TableRow class="bg-primary text-primary-foreground hover:bg-primary text-xs">
                <TableHead class="text-white font-bold">合同号/签约日期</TableHead>
                <TableHead class="text-white font-bold">涉外贸易条件</TableHead>
                <TableHead class="text-white font-bold text-center w-40">业务生命周期</TableHead>
                <TableHead class="text-white font-bold text-center">多商户专票</TableHead>
                <TableHead class="text-white font-bold text-center">退税进度</TableHead>
                <TableHead class="text-white font-bold w-45 text-center">单证工作台</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              
              <!-- 🎯 双层级循环大招：外层遍历每一个合同主体 -->
              <template v-for="con in contracts.data" :key="con.id">
                
                <!-- 1. 母行 (Master Row)：展现出口主合同属性 -->
                <TableRow class="bg-secondary/30 hover:bg-secondary/50 border-t border-border font-medium text-foreground transition-colors cursor-pointer">
                  <TableCell>
                    <div class="font-extrabold text-primary tracking-wide text-sm">{{ con.contract_no }}</div>
                    <div class="text-[10px] text-muted-foreground font-mono">{{ con.contract_date }}</div>
                  </TableCell>
                  <TableCell>
                    <div class="font-mono text-xs font-bold text-primary">{{ con.incoterms }} | {{ con.currency }}</div>
                    <div class="text-[11px] text-muted-foreground truncate max-w-35 font-serif">{{ con.payment_terms }}</div>
                  </TableCell>
                  <TableCell class="text-center">
                    <select 
                      v-model="con.status" 
                      @change="handleStatusTransition(con.id, con.status)"
                      class="h-8 rounded-md border border-border bg-background text-foreground px-2 text-xs font-semibold shadow-sm focus:ring-2 focus:ring-ring focus:outline-none transition-colors"
                      :class="statusStyles[con.status]"
                    >
                      <option v-for="(text, val) in statusTextMap" :key="val" :value="val">{{ text }}</option>
                    </select>
                  </TableCell>
                  <TableCell class="text-center">
                    <Badge v-if="con.invoice_status === 'none'" variant="secondary" class="bg-destructive/10 text-destructive border-destructive/20 text-[10px]">未开票</Badge>
                    <Badge v-else-if="con.invoice_status === 'partial'" variant="secondary" class="bg-warning/10 text-warning border-warning/20 text-[10px]">部分收齐</Badge>
                    <Badge v-else variant="secondary" class="bg-success/10 text-success border-success/20 text-[10px]">专票全齐</Badge>
                  </TableCell>
                  <TableCell class="text-center text-xs font-mono">
                    <span v-if="con.refund_status === 'none'" class="text-muted-foreground">未申报</span>
                    <span v-else-if="con.refund_status === 'processing'" class="text-warning font-bold">审核中</span>
                    <span v-else class="text-success font-bold">✔ 已到账</span>
                  </TableCell>
                  <TableCell class="text-center">
                    <div class="flex justify-center gap-1.5">
                      <Button as-child size="sm" variant="outline" class="h-7 text-[10px] border-primary/20 bg-primary/5 text-primary hover:bg-primary hover:text-primary-foreground transition-colors rounded-md">
                        <Link :href="`/contracts/${con.id}/documents`">📄 5维制单</Link>
                      </Button>
                      <Button as-child size="sm" variant="outline" class="h-7 text-[10px] border-border bg-background text-foreground hover:bg-secondary transition-colors rounded-md">
                        <Link :href="`/contracts/${con.id}/financials`">📦 货物管控</Link>
                      </Button>
                    </div>
                  </TableCell>
                </TableRow>

                <!-- 2. 🎯 子行 (Detail Row)：不加任何遮挡，当场展开渲染该合同号下面所有的【商品、数量、外销价明细】 -->
                <TableRow class="hover:bg-transparent border-b-2">
                  <TableCell colspan="6" class="p-2 bg-primary/5">
                    <div class="mx-4 border border-border rounded-xl overflow-hidden bg-card shadow-lg shadow-primary/10">
                      <table class="w-full text-left text-[11px]">
                        <thead>
                          <tr class="bg-primary/10 text-primary font-bold border-border text-[10px] uppercase">
                            <th class="p-1.5 pl-3 w-8">#</th>
                            <th class="p-1.5 w-35">海关商品编号 / SKU</th>
                            <th class="p-1.5">大货商品品名 (中/英)</th>
                            <th class="p-1.5 text-foreground font-medium max-w-35 truncate">开票供货工厂</th>
                            <th class="p-1.5 text-right w-28">选配出口数量</th>
                            <th class="p-1.5 text-right w-28">离岸单价</th>
                            <th class="p-1.5 text-right w-32 pr-3">外销款合计</th>
                          </tr>
                        </thead>
                        <tbody>
                          <!-- 内层循环：迭代当前合同名下的所有货物实体 -->
                          <tr 
                            v-for="(ci, subIdx) in con.contract_items" 
                            :key="ci.id" 
                            class="border-b border-border last:border-b-0 hover:bg-secondary/40 transition-colors text-foreground"
                          >
                            <td class="p-1.5 pl-3 text-muted-foreground font-mono">#{{ subIdx + 1 }}</td>
                            <td class="p-1.5 font-mono text-xs">
                              <div class="text-primary font-semibold">{{ ci.item?.category?.hs_code }}</div>
                              <div class="text-muted-foreground text-[9px] font-serif max-w-50 truncate">SKU: {{ ci.item?.sku }}</div>
                            </td>
                            <td class="p-1.5">
                              <div class="font-bold text-foreground">{{ ci.item?.name_cn }}</div>
                              <div class="text-muted-foreground text-[9px] font-serif max-w-50 truncate">{{ ci.item?.name_en }}</div>
                            </td>
                            <td class="p-1.5 text-foreground font-medium max-w-35 truncate">
                              {{ ci.supplier?.company_name || '未指定工厂' }}
                            </td>
                            <td class="p-1.5 text-right font-mono font-bold text-foreground tracking-wide">
                              {{ formatSmartNumber(ci.quantity).toLocaleString() }} <span class="text-[9px] font-sans font-normal text-muted-foreground">{{ ci.item?.category?.unit }}</span>
                            </td>
                            <td class="p-1.5 text-right font-mono text-muted-foreground tracking-wide">
                              ${{ ci.unit_price }}
                            </td>
                            <td class="p-1.5 text-right font-mono font-black text-success pr-3 text-xs tracking-wide">
                              ${{ Number(ci.total_amount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                            </td>
                          </tr>

                          <!-- 空物品存根兜底 -->
                          <tr v-if="!con.contract_items || con.contract_items.length === 0">
                            <td colspan="7" class="p-3 text-center text-muted-foreground italic text-[10px]">
                              ⚠️ 警告：当前合同下无任何货品明细，请立刻点击右侧“货物管控”进行初始化选配！
                            </td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </TableCell>
                </TableRow>

              </template>
            </TableBody>
          </Table>
        </div>
        
        <!-- 🎯 补齐：标准的 Ziggy 别名驱动型分页导航栏 -->
        <div class="flex justify-end gap-2 mt-4" v-if="contracts.links && contracts.links.length > 3">
          <Component 
            v-for="(link, index) in contracts.links" 
            :is="link.url ? Link : 'span'" 
            :key="index"
            :href="link.url || ''"
            class="px-2.5 py-1 text-xs border rounded-md min-w-8 text-center transition-colors font-medium"
            :class="{ 
              'bg-primary text-primary-foreground border-primary shadow-md shadow-primary/20': link.active, 
              'text-muted-foreground bg-muted cursor-not-allowed': !link.url, 
              'hover:bg-secondary text-foreground': link.url && !link.active 
            }"
          ><span v-html="link.label"></span></Component>
        </div>

      </CardContent>
    </Card>
  </div>
  </AppLayout>
</template>