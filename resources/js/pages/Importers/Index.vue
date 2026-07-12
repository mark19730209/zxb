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
import importersroute from '@/routes/importers';
import { type BreadcrumbItem } from '@/types';
import type { ImporterPageProps } from '@/types/tms'

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'ERP/TMS' },
    { title: 'Suppliers', href: importersroute.index().url },
    { title: 'Create Item' },
];
const props = defineProps<ImporterPageProps>()
const search = ref(props.filters.search || '')

debouncedWatch(search, (value) => {
  
  // 1. 从 Wayfinder 路由对象中提取出纯字符串 URL（通常可以直接调 .url()）
  const url = importersroute.index.url() 

  // 2. 正确将 URL 字符串传递给 router.get
  router.get(
    url, 
    { search: value }, 
    { preserveState: true, replace: true }
  )
  
}, { debounce: 300 }) // 👈 这里的 300ms 才会真正生效

const deleteImporter = (id: any) => {
  if (confirm('确认注销该境外进口商档案？')) {
    router.delete(importersroute.destroy(id))
  }
}
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
  <div class="min-h-screen p-8 max-w-7xl mx-auto space-y-6 bg-background text-foreground">
    <div class="flex justify-between items-center">
      <div>
        <h1 class="text-2xl font-bold tracking-tight bg-linear-to-r from-primary to-success bg-clip-text text-transparent">境外买方/进口商名录</h1>
        <p class="text-sm text-muted-foreground mt-1">管理外销商业发票（Invoice）抬头及海关报关单境外收货人属性</p>
      </div>
      <Button as-child class="bg-primary hover:brightness-110 text-primary-foreground rounded-lg shadow-lg shadow-primary/20 transition-all duration-200 hover:scale-[1.02]">
        <Link :href="importersroute.create()">+ 登记新境外客户</Link>
      </Button>
    </div>

    <Card class="bg-card border-border rounded-2xl shadow-lg shadow-primary/5">
      <CardHeader class="pb-3 flex flex-row items-center justify-between space-y-0 border-b border-border">
        <CardTitle class="text-base font-bold">客户台账</CardTitle>
        <div class="w-72"><Input v-model="search" placeholder="搜索客户英文名/国别..." class="h-9 rounded-lg border-border bg-background focus-visible:ring-ring" /></div>
      </CardHeader>
      <CardContent>
        <div class="rounded-xl border border-border bg-card overflow-hidden">
          <Table>
            <TableHeader>
              <TableRow class="bg-primary text-primary-foreground">
                <TableHead>境外买方英文名称</TableHead>
                <TableHead>国家/地区代码</TableHead>
                <TableHead>境外清关地址</TableHead>
                <TableHead>境外税号/邮箱</TableHead>
                <TableHead class="w-30 text-center font-bold">操作</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="imp in importers.data" :key="imp.id" class="hover:bg-secondary/40 transition-colors">
                <TableCell class="font-medium text-sm font-serif text-foreground">{{ imp.company_name_en }}</TableCell>
                <TableCell>
                  <Badge variant="secondary" class="bg-primary/10 text-primary border border-primary/20 font-mono">{{ imp.country_code }}</Badge>
                </TableCell>
                <TableCell class="text-xs text-muted-foreground max-w-75 truncate">{{ imp.company_address_en }}</TableCell>
                <TableCell class="text-xs font-mono">
                  <div>{{ imp.tax_no || '—' }}</div>
                  <div class="text-muted-foreground">{{ imp.contact_email }}</div>
                </TableCell>
                <TableCell class="text-center">
                  <div class="flex justify-center gap-3">
                    <Link :href="importersroute.edit(imp.id)" class="text-xs text-primary hover:underline font-medium">编辑</Link>
                    <button @click="deleteImporter(imp.id)" class="text-xs text-destructive hover:underline font-medium">注销</button>
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
