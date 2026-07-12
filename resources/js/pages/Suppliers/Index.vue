<script setup lang="ts">
import { router, Link } from '@inertiajs/vue3'
import { debouncedWatch } from '@vueuse/core' 
import { ref } from 'vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import AppLayout from '@/layouts/AppLayout.vue';
import suppliersroute from '@/routes/suppliers';
import { type BreadcrumbItem } from '@/types';
import type { SupplierPageProps } from '@/types'

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'ERP/TMS' },
    { title: 'Items' },
];

const props = defineProps<SupplierPageProps>()
const search = ref(props.filters.search || '')

// 🎯 Wayfinder 规范：显式将搜索请求导向商户列表基础端点
// watch(search, debounce((value) => {
//   router.get(WF_PATHS.suppliers.index, { search: value }, { preserveState: true, replace: true })
// }, 300))
// 💡 推荐：使用 debouncedWatch 实现真正的 300ms 防抖监听
debouncedWatch(search, (value) => {
  
  // 1. 从 Wayfinder 路由对象中提取出纯字符串 URL（通常可以直接调 .url()）
  const url = suppliersroute.index.url() 

  // 2. 正确将 URL 字符串传递给 router.get
  router.get(
    url, 
    { search: value }, 
    { preserveState: true, replace: true }
  )
  
}, { debounce: 300 }) // 👈 这里的 300ms 才会真正生效

const deleteSupplier = (id: any) => {
  if (confirm('确定要移除该国内供应商档案吗？系统将自动审计该商户项下是否存在未结清的增值税专用发票流水。')) {
    // 🎯 显式调用 Wayfinder 的销毁行为动态端点
    router.delete(suppliersroute.index(id))
  }
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
  <div class="min-h-screen p-8 max-w-7xl mx-auto space-y-6 bg-background text-foreground">
    <!-- 页面页头 -->
    <div class="flex justify-between items-center">
      <div>
        <h1 class="text-2xl font-bold tracking-tight bg-linear-to-r from-primary to-success bg-clip-text text-transparent">国内供货商名录 (Wayfinder)</h1>
        <p class="text-sm text-muted-foreground mt-1">管理上游供货商开票抬头、开户行信息及进货发票合规核销状态</p>
      </div>
      <!-- 🎯 替换为 Wayfinder 硬编码跳转路径 -->
      <Button as-child class="bg-primary hover:brightness-110 text-primary-foreground rounded-lg shadow-lg shadow-primary/20 transition-all duration-200 hover:scale-[1.02]">
        <Link :href="suppliersroute.create()">+ 登记新供货工厂</Link>
      </Button>
    </div>

    <!-- 数据表格卡片 -->
    <Card class="bg-card border-border rounded-2xl shadow-lg shadow-primary/5">
      <CardHeader class="pb-3 flex flex-row items-center justify-between space-y-0 border-b border-border">
        <div class="space-y-1">
          <CardTitle class="text-base font-bold">合作工厂台账</CardTitle>
          <CardDescription>用于在财务大盘核销进项税额的法定纳税人名册</CardDescription>
        </div>
        <div class="w-72">
          <Input v-model="search" placeholder="输入工厂名称或纳税人识别号..." class="h-9 rounded-lg border-border bg-background focus-visible:ring-ring" />
        </div>
      </CardHeader>
      <CardContent>
        <div class="rounded-xl border border-border bg-card overflow-hidden">
          <Table>
            <TableHeader>
              <TableRow class="bg-primary text-primary-foreground">
                <TableHead class="w-15">ID</TableHead>
                <TableHead>供应商/商户开票名称</TableHead>
                <TableHead>统一社会信用代码 (税号)</TableHead>
                <TableHead>结算开户银行及账号</TableHead>
                <TableHead class="w-25">联系人</TableHead>
                <TableHead class="w-30 text-center">档案操作</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="supplier in suppliers.data" :key="supplier.id" class="hover:bg-secondary/40 transition-colors">
                <TableCell class="font-mono text-xs text-muted-foreground">#{{ supplier.id }}</TableCell>
                <TableCell class="font-medium text-foreground">{{ supplier.company_name }}</TableCell>
                <TableCell>
                  <Badge variant="outline" class="font-mono text-xs tracking-wider bg-primary/10 text-primary border border-primary/20">
                    {{ supplier.tax_id }}
                  </Badge>
                </TableCell>
                <TableCell class="text-xs text-muted-foreground font-mono truncate max-w-70">
                  {{ supplier.bank_account }}
                </TableCell>
                <TableCell class="text-sm text-foreground">
                  {{ supplier.contact_person || '—' }}
                </TableCell>
                <TableCell class="text-center">
                  <div class="flex justify-center gap-3">
                    <!-- 🎯 使用 Wayfinder 动态方法生成对应 ID 的静态编辑跳转地址 -->
                    <Link :href="suppliersroute.edit(supplier.id)" class="text-primary font-medium hover:underline">
                      基本更正
                    </Link>
                    <button @click="deleteSupplier(supplier.id)" class="text-destructive font-medium hover:underline">
                      安全注销
                    </button>
                  </div>
                </TableCell>
              </TableRow>
              <TableRow v-if="suppliers.data.length === 0">
                <TableCell colspan="6" class="text-center py-8 text-sm text-muted-foreground">
                  系统内暂未登记任何合作工厂数据
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>

        <!-- 兼容 Wayfinder 的通用单页分页栏 -->
        <div class="flex justify-end gap-2 mt-4" v-if="suppliers.links && suppliers.links.length > 3">
          <Component 
            v-for="(link, index) in suppliers.links" 
            :is="link.url ? Link : 'span'" 
            :key="index"
            :href="link.url"
            
            class="px-3 py-1 text-xs border rounded transition-colors"
            :class="{ 
              'bg-primary text-primary-foreground border-primary shadow-md shadow-primary/20': link.active, 
              'text-muted-foreground bg-muted': !link.url, 
              'hover:bg-secondary text-foreground': link.url && !link.active
            }"
          ><span v-html="link.label"></span></Component>
        </div>
      </CardContent>
    </Card>
  </div>
  </AppLayout>
</template>
