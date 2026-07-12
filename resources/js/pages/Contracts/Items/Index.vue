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
  
  // 1. 从 Wayfinder 路由对象中提取出纯字符串 URL（通常可以直接调 .url()）
  const url = exportersroute.index.url() 

  // 2. 正确将 URL 字符串传递给 router.get
  router.get(
    url, 
    { search: value }, 
    { preserveState: true, replace: true }
  )
  
}, { debounce: 300 }) // 👈 这里的 300ms 才会真正生效

const deleteExporter = (id: any) => {
  if (confirm('确认注销该出口商档案？')) {
    router.delete(exportersroute.destroy(id))
  }
}
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
  <div class="min-h-screen p-8 max-w-7xl mx-auto space-y-6 bg-background text-foreground">
    <div class="flex justify-between items-center">
      <div>
        <h1 class="text-2xl font-bold tracking-tight bg-linear-to-r from-primary to-success bg-clip-text text-transparent">出口商名录</h1>
        <p class="text-sm text-muted-foreground mt-1">管理报关单及商业发票（Invoice）抬头对应的境内出口主体</p>
      </div>
      <Button as-child class="bg-primary hover:brightness-110 text-primary-foreground rounded-lg shadow-lg shadow-primary/20 transition-all duration-200 hover:scale-[1.02]">
        <Link :href="exportersroute.create()">+ 登记新出口商</Link>
      </Button>
    </div>

    <Card class="bg-card border-border rounded-2xl shadow-lg shadow-primary/5">
      <CardHeader class="pb-3 flex flex-row items-center justify-between space-y-0 border-b border-border">
        <CardTitle class="text-base font-bold">出口商台账</CardTitle>
        <div class="w-72"><Input v-model="search" placeholder="搜索中英文名称/税号..." class="h-9 rounded-lg border-border bg-background focus-visible:ring-ring" /></div>
      </CardHeader>
      <CardContent>
        <div class="rounded-xl border border-border bg-card overflow-hidden">
          <Table>
            <TableHeader>
              <TableRow class="bg-primary text-primary-foreground">
                <TableHead>中文全称</TableHead>
                <TableHead>英文全称</TableHead>
                <TableHead>联系电话</TableHead>
                <TableHead>统一社会信用代码</TableHead>
                <TableHead>海关编码</TableHead>
                <TableHead class="w-30 text-center font-bold">操作</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="exp in exporters.data" :key="exp.id" class="hover:bg-secondary/40 transition-colors">
                <TableCell class="font-medium text-sm text-foreground">{{ exp.company_name_cn }}</TableCell>
                <TableCell class="text-sm font-serif text-foreground">{{ exp.company_name_en }}</TableCell>
                <TableCell class="text-xs font-mono text-muted-foreground">{{ exp.contact_tel }}</TableCell>
                <TableCell class="text-xs font-mono">{{ exp.tax_id }}</TableCell>
                <TableCell>
                  <Badge v-if="exp.customs_code" variant="secondary" class="bg-primary/10 text-primary border border-primary/20 font-mono">{{ exp.customs_code }}</Badge>
                  <span v-else class="text-xs text-muted-foreground">—</span>
                </TableCell>
                <TableCell class="text-center">
                  <div class="flex justify-center gap-3">
                    <Link :href="exportersroute.edit(exp.id)" class="text-xs text-primary hover:underline font-medium">编辑</Link>
                    <button @click="deleteExporter(exp.id)" class="text-xs text-destructive hover:underline font-medium">注销</button>
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