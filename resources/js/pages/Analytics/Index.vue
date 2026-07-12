<script setup lang="ts">
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Progress } from '@/components/ui/progress'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import AppLayout from '@/layouts/AppLayout.vue';
import type { AnalyticsPageProps } from '@/types' // 🎯 导入强类型保护
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'ERP/TMS' },
    { title: 'Analytics' },
];
// 🎯 使用 TypeScript 严格泛型约束 Props
const props = defineProps<AnalyticsPageProps>()

const statusStyles = {
  draft: 'bg-muted text-muted-foreground border-border',
  active: 'bg-primary/10 text-primary border-primary/20',
  shipped: 'bg-warning/10 text-warning border-warning/20',
  completed: 'bg-success/10 text-success border-success/20',
  cancelled: 'bg-destructive/10 text-destructive border-destructive/20'
}
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
  <div class="min-h-screen p-8 max-w-7xl mx-auto space-y-6 bg-background text-foreground">
    
    <!-- 大盘标题 -->
    <div>
      <h1 class="text-2xl font-bold tracking-tight bg-linear-to-r from-primary to-success bg-clip-text text-transparent">出口供应链财务与退税统计大盘</h1>
      <p class="text-sm text-muted-foreground mt-1">实时配平进销项票流、国库退税回流进度，透视全系统综合外贸毛利润</p>
    </div>

    <!-- ===== 1. 顶部四大核心财务数据指标卡片 ===== -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      
      <!-- 指标1：出口统计 -->
      <Card class="bg-card border-border rounded-2xl shadow-lg shadow-primary/5">
        <CardHeader class="pb-2 border-b border-border">
          <CardTitle class="text-xs font-bold text-muted-foreground uppercase tracking-wider">① 出口销售总额</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="text-xl font-extrabold text-foreground">￥{{ props.metrics.total_export_rmb.toLocaleString() }}</div>
          <p class="text-xs text-muted-foreground mt-1 font-mono">${{ props.metrics.total_export_usd.toLocaleString() }} USD</p>
        </CardContent>
      </Card>

      <!-- 指标2：进货统计 -->
      <Card class="bg-card border-border rounded-2xl shadow-lg shadow-primary/5">
        <CardHeader class="pb-2 border-b border-border">
          <CardTitle class="text-xs font-bold text-muted-foreground uppercase tracking-wider">② 国内工厂采购统计</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="text-xl font-extrabold text-foreground">￥{{ props.metrics.total_purchase_amount.toLocaleString() }}</div>
          <div class="flex items-center gap-2 mt-1">
            <span class="text-[10px] text-muted-foreground">发票回收率:</span>
            <Progress :model-value="props.metrics.invoice_recovery_rate" class="h-1.5 w-16" />
            <span class="text-[10px] font-bold text-primary">{{ props.metrics.invoice_recovery_rate }}%</span>
          </div>
        </CardContent>
      </Card>

      <!-- 指标3：退税额统计 -->
      <Card class="bg-card border-border rounded-2xl shadow-lg shadow-primary/5">
        <CardHeader class="pb-2 border-b border-border">
          <CardTitle class="text-xs font-bold text-muted-foreground uppercase tracking-wider">③ 出口退税大盘</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="text-xl font-black text-success">￥{{ props.metrics.total_actual_refund.toLocaleString() }}</div>
          <div class="flex items-center gap-2 mt-1">
            <span class="text-[10px] text-muted-foreground">预估可退:</span>
            <span class="text-[10px] font-mono font-bold text-muted-foreground">￥{{ props.metrics.total_estimated_refund.toLocaleString() }}</span>
          </div>
        </CardContent>
      </Card>

      <!-- 指标4：外贸纯利润统计 -->
      <Card class="bg-primary/5 border-primary/20 rounded-2xl shadow-lg shadow-primary/10">
        <CardHeader class="pb-2 border-b border-border">
          <CardTitle class="text-xs font-bold text-primary uppercase tracking-wider">④ 综合外贸净毛利</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="text-2xl font-black text-primary">￥{{ props.metrics.total_gross_profit.toLocaleString() }}</div>
          <p class="text-[10px] text-muted-foreground mt-1">公式：销项(RMB) - 进项含税 + 应收退税</p>
        </CardContent>
      </Card>

    </div>

    <!-- ===== 2. 下半部分：各出口合同核心利润与发票核销明细清单 ===== -->
    <Card class="bg-card border-border rounded-2xl shadow-lg shadow-primary/5">
      <CardHeader class="border-b border-border">
        <CardTitle class="text-base font-bold">出口订单盈利与合规透视台账</CardTitle>
        <CardDescription>按合同号穿透审计每一笔海外结汇、国内多商户工厂采购及发票到齐状态</CardDescription>
      </CardHeader>
      <CardContent>
        <div class="rounded-xl border border-border bg-card overflow-hidden">
          <Table>
            <TableHeader>
              <TableRow class="bg-primary text-primary-foreground text-xs">
                <TableHead>核心合同号</TableHead>
                <TableHead class="text-right">出口销项 (USD / RMB)</TableHead>
                <TableHead class="text-right">国内采购含税价</TableHead>
                <TableHead class="text-center">工厂发票</TableHead>
                <TableHead class="text-right">预估退税 / 已到账</TableHead>
                <TableHead class="text-right font-bold text-primary">单笔净毛利</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="item in props.performanceList" :key="item.id" class="hover:bg-secondary/40 transition-colors text-xs">
                
                <!-- 合同号与生命周期状态 -->
                <TableCell class="space-y-1">
                  <div class="font-extrabold text-primary tracking-wide">{{ item.contract_no }}</div>
                  <Badge variant="outline" :class="statusStyles[item.status]" class="text-[9px] uppercase px-1.5 font-bold">
                    {{ item.status }}
                  </Badge>
                </TableCell>
                
                <!-- 出口总额 -->
                <TableCell class="text-right font-mono space-y-0.5">
                  <div class="font-bold text-foreground">￥{{ item.export_amount_rmb.toLocaleString(undefined, {minimumFractionDigits: 2}) }}</div>
                  <div class="text-[10px] text-muted-foreground">${{ item.export_amount_usd.toLocaleString(undefined, {minimumFractionDigits: 2}) }}</div>
                </TableCell>
                
                <!-- 进货含税采购 -->
                <TableCell class="text-right font-mono font-medium text-muted-foreground">
                  ￥{{ item.purchase_total_amount.toLocaleString(undefined, {minimumFractionDigits: 2}) }}
                </TableCell>
                
                <!-- 多商户发票回收状态 -->
                <TableCell class="text-center">
                  <Badge v-if="item.invoice_status === 'none'" variant="secondary" class="bg-destructive/10 text-destructive border-destructive/20 text-[10px]">未收票</Badge>
                  <Badge v-else-if="item.invoice_status === 'partial'" variant="secondary" class="bg-warning/10 text-warning border-warning/20 text-[10px]">部分收齐</Badge>
                  <Badge v-else variant="secondary" class="bg-success/10 text-success border-success/20 text-[10px]">发票全齐</Badge>
                </TableCell>
                
                <!-- 退税额明细 -->
                <TableCell class="text-right font-mono space-y-0.5">
                  <div class="text-muted-foreground">应退: ￥{{ item.estimated_refund.toLocaleString(undefined, {minimumFractionDigits: 2}) }}</div>
                  <div class="text-[10px] text-success font-bold">已到: ￥{{ item.actual_refund_received.toLocaleString(undefined, {minimumFractionDigits: 2}) }}</div>
                </TableCell>
                
                <!-- 单笔纯毛利 -->
                <TableCell class="text-right font-mono font-black text-sm text-foreground">
                  <span :class="item.net_profit >= 0 ? 'text-success' : 'text-destructive'">
                    ￥{{ item.net_profit.toLocaleString(undefined, {minimumFractionDigits: 2}) }}
                  </span>
                </TableCell>

              </TableRow>
              <TableRow v-if="props.performanceList.length === 0">
                <TableCell colspan="6" class="text-center py-8 text-muted-foreground">目前没有任何生效的合同生成财务流水大盘</TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>
      </CardContent>
    </Card>

  </div>
  </AppLayout>
</template>
