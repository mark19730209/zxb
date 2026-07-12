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
import exportersroute from '@/routes/exporters';
import { type BreadcrumbItem } from '@/types';
import type { ExporterPageProps } from '@/types/tms'

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'ERP/TMS' },
    { title: 'Exporters', href: exportersroute.index().url },
    { title: 'Create Item' },
];
const props = defineProps<ExporterPageProps>()
const search = ref(props.filters.search || '')

debouncedWatch(search, (value) => {
  const url = exportersroute.index.url() 
  router.get(
    url, 
    { search: value }, 
    { preserveState: true, replace: true }
  )
}, { debounce: 300 })

const deleteExporter = (id: any) => {
  if (confirm('确认注销该出口商档案？')) {
    router.delete(exportersroute.destroy(id), {
      preserveScroll: true // 优化：注销后保持滚动条位置
    })
  }
}
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
  <div class="min-h-screen p-8 max-w-7xl mx-auto space-y-6 bg-background text-foreground transition-colors duration-300">
    
    <!-- 头部区域 -->
    <div class="flex justify-between items-center">
      <div>
        <!-- 优化：调整渐变色，使其在亮暗模式下均具备高对比度 -->
        <h1 class="text-2xl font-bold tracking-tight bg-linear-to-r from-primary to-emerald-500 dark:to-emerald-400 bg-clip-text text-transparent">
          出口商名录
        </h1>
        <p class="text-sm text-muted-foreground mt-1">管理报关单及商业发票（Invoice）抬头对应的境内出口主体</p>
      </div>
      <!-- 优化：增强暗黑模式下的按钮悬浮反馈 dark:hover:brightness-125 -->
      <Button as-child class="bg-primary hover:brightness-110 dark:hover:brightness-125 text-primary-foreground rounded-lg shadow-lg shadow-primary/20 dark:shadow-none transition-all duration-200 hover:scale-[1.02]">
        <Link :href="exportersroute.create()">+ 登记新出口商</Link>
      </Button>
    </div>

    <!-- 台账卡片 -->
    <!-- 优化：暗黑模式下关闭多余阴影 (dark:shadow-none)，让界面更通透 -->
    <Card class="bg-card border-border rounded-2xl shadow-lg shadow-primary/5 dark:shadow-none transition-all">
      <CardHeader class="pb-3 flex flex-row items-center justify-between space-y-0 border-b border-border">
        <CardTitle class="text-base font-bold">出口商台账</CardTitle>
        <div class="w-72">
          <Input v-model="search" placeholder="搜索中英文名称/税号..." class="h-9 rounded-lg border-border bg-background focus-visible:ring-ring" />
        </div>
      </CardHeader>
      
      <CardContent class="pt-6">
        <div class="rounded-xl border border-border bg-card overflow-hidden">
          <Table>
            <TableHeader>
              <!-- 优化：将纯色背景改为语义化的 bg-muted/60，暗黑模式下更加柔和优雅 -->
              <TableRow class="bg-muted/60 border-b border-border">
                <TableHead class="font-semibold text-foreground">中文全称</TableHead>
                <TableHead class="font-semibold text-foreground">英文全称</TableHead>
                <TableHead class="font-semibold text-foreground">统一社会信用代码</TableHead>
                <TableHead class="font-semibold text-foreground">海关编码</TableHead>
                <TableHead class="w-30 text-center font-semibold text-foreground">操作</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <!-- 优化：hover 时使用更细腻的 bg-muted/40 变化 -->
              <TableRow v-for="exp in exporters.data" :key="exp.id" class="hover:bg-muted/40 transition-colors border-b border-border last:border-0">
                <TableCell class="font-medium text-sm text-foreground">{{ exp.company_name_cn }}</TableCell>
                <!-- 优化：英文全称换用 sans 字体，在暗黑模式下可读性更强 -->
                <TableCell class="text-sm font-sans text-muted-foreground dark:text-zinc-300">{{ exp.company_name_en }}</TableCell>
                <TableCell class="text-xs font-mono tracking-wider text-muted-foreground">{{ exp.tax_id }}</TableCell>
                <TableCell>
                  <!-- 优化：Badge 的背景在暗黑模式下使用更亮一点的半透明色，避免太暗看不清 -->
                  <Badge v-if="exp.customs_code" variant="secondary" class="bg-primary/10 dark:bg-primary/20 text-primary border border-primary/20 font-mono">
                    {{ exp.customs_code }}
                  </Badge>
                  <span v-else class="text-xs text-muted-foreground">—</span>
                </TableCell>
                <TableCell class="text-center">
                  <div class="flex justify-center gap-3">
                    <!-- 优化：暗黑模式下文字链接增加 hover:text-primary 动画 -->
                    <Link :href="exportersroute.edit(exp.id)" class="text-xs text-primary hover:text-primary/80 transition-colors hover:underline font-medium">编辑</Link>
                    <button @click="deleteExporter(exp.id)" class="text-xs text-destructive hover:text-destructive/80 transition-colors hover:underline font-medium">注销</button>
                  </div>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>
      </CardContent>
    </Card>
  </div>
  </AppLayout>
</template>
