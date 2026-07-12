<script setup lang="ts">
import { router, Link, Head } from '@inertiajs/vue3'
import { debouncedWatch } from '@vueuse/core' 
import { ref } from 'vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import AppLayout from '@/layouts/AppLayout.vue';
import categoriesroute from '@/routes/categories'
import { type BreadcrumbItem } from '@/types';
import type { CategoryPageProps } from '@/types/tms'
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/components/ui/alert-dialog'

const props = defineProps<CategoryPageProps>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'ERP/TMS' },
    { title: 'categories' },
];

const search = ref(props.filters.search || '')
// 💡 推荐：使用 debouncedWatch 实现真正的 300ms 防抖监听
debouncedWatch(search, (value) => {
  // 1. 从 Wayfinder 路由对象中提取出纯字符串 URL（通常可以直接调 .url()）
  const url = categoriesroute.index.url() 

  // 2. 正确将 URL 字符串传递给 router.get
  router.get(
    url, 
    { search: value }, 
    { preserveState: true, replace: true }
  )
  
}, { debounce: 300 }) // 👈 这里的 300ms 才会真正生效

// 🛠 1. 控制弹窗的显示隐藏
const isDeleteDialogOpen = ref(false)
// 🛠 2. 暂存当前点击要删除的商品 ID
const activeDeleteId = ref<any>(null)

// 🛠 3. 触发弹窗的函数：不再直接弹 confirm，而是存下 id 并打开对话框
const openDeleteDialog = (id: any) => {
  activeDeleteId.value = id
  isDeleteDialogOpen.value = true
}

// 🛠 4. 确认删除后执行的真实请求
const confirmDelete = () => {
  if (activeDeleteId.value) {
    router.delete(categoriesroute.destroy(activeDeleteId.value), {
      onSuccess: () => {
        // 请求成功后关闭弹窗
        isDeleteDialogOpen.value = false
        activeDeleteId.value = null
      },
      preserveScroll: true,
    })
  }
}
</script>

<template>
  <Head title="海关 H.S. 品类库" />
  <AppLayout :breadcrumbs="breadcrumbs">
  <div class="min-h-screen p-8 max-w-7xl mx-auto space-y-6 bg-background text-foreground">
    <div class="flex justify-between categories-center">
      <div>
        <h1 class="text-2xl font-bold tracking-tight bg-linear-to-r from-primary to-success bg-clip-text text-transparent">海关 H.S. 品类库</h1>
        <p class="text-sm text-muted-foreground mt-1">统一管理海关 H.S. 编码体系、品类申报要素模板及出口退税率配置</p>
      </div>
      <Button as-child class="bg-primary hover:brightness-110 text-primary-foreground rounded-lg shadow-lg shadow-primary/20 transition-all duration-200 hover:scale-[1.02]">
        <!-- <Link :href="route('categories.create')">+ 新增商品档案</Link> -->
         <Link :href="categoriesroute.create()">+ 新增 H.S. 品类</Link> 
        <!-- <button @click="createItem">新增商品档案</button> -->
      </Button>
    </div>

    <Card class="bg-card border-border rounded-2xl shadow-lg shadow-primary/5">
      <CardHeader class="pb-3 flex flex-row categories-center justify-between space-y-0 border-b border-border">
        <div class="space-y-1">
          <CardTitle class="text-base font-bold">H.S. 品类列表</CardTitle>
          <CardDescription>当前系统中已维护的海关 H.S. 品类数据</CardDescription>
        </div>
        <div class="w-72">
          <Input v-model="search" placeholder="输入品名/SKU/H.S.编码搜索..." class="h-9 rounded-lg border-border bg-background focus-visible:ring-ring" />
        </div>
      </CardHeader>
      <CardContent>
        <div class="rounded-xl border border-border bg-card overflow-hidden">
          <Table>
            <TableHeader>
              <TableRow class="bg-primary text-primary-foreground">
                <TableHead class="w-30">海关 H.S. 编码</TableHead>
                <TableHead>品类通关名称</TableHead>
                <TableHead class="text-center">法定单位</TableHead>
                <TableHead class="w-25 text-center">操作</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="category in categories.data" :key="category.id" class="hover:bg-secondary/40 transition-colors">
                <TableCell>
                  <Badge class="font-mono tracking-wider bg-primary/10 text-primary border border-primary/20">
                    {{ category.hs_code }}
                  </Badge>
                </TableCell>
                <TableCell>
                  <div class="font-medium text-sm text-foreground">{{ category.category_name }}</div>
                  <div class="text-xs text-muted-foreground font-serif">{{  }}</div>
                </TableCell>
                <TableCell class="text-center text-sm">{{ category.unit }}</TableCell>
                <TableCell class="text-center">
                  <div class="flex justify-center gap-2">
                    <Link :href="categoriesroute.edit(category.id)" class="text-primary font-medium hover:underline text-xs">编辑</Link>
                      <!-- <Button variant="destructive" @click="openDeleteDialog(category.id)">安全移除</Button> -->
                    <button @click="openDeleteDialog(category.id)" class="text-destructive font-medium hover:underline text-xs">删除</button>
                  </div>
                </TableCell>
              </TableRow>
              <TableRow v-if="categories.data.length === 0">
                <TableCell colspan="7" class="text-center py-8 text-sm text-muted-foreground">暂无符合条件的商品档案数据</TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>

        <!-- 简易分页栏 -->
        <div class="flex justify-end gap-2 mt-4" v-if="categories.links && categories.links.length > 3">
          <Component 
            v-for="(link, index) in categories.links" 
            :is="link.url ? Link : 'span'" 
            :key="index"
            :href="link.url"
            
            class="px-3 py-1 text-xs border rounded transition-colors"
            :class="{ 'bg-primary text-primary-foreground border-primary shadow-md shadow-primary/20': link.active, 'text-muted-foreground bg-muted cursor-not-allowed': !link.url, 'hover:bg-secondary text-foreground': link.url && !link.active }"
          ><span v-html="link.label"></span></Component>
        </div>
      </CardContent>
    </Card>
  </div>
    <!-- 🛠 全局唯一的安全移除确认弹窗 -->
  <AlertDialog :open="isDeleteDialogOpen" @update:open="isDeleteDialogOpen = $event">
    <AlertDialogContent>
      <AlertDialogHeader>
        <AlertDialogTitle>确定要安全移除该商品档案吗？</AlertDialogTitle>
        <AlertDialogDescription>
          此操作不可逆。系统将自动检查该商品是否有未结清的出口业务。如果有活动订单，移除将会失败。
        </AlertDialogDescription>
      </AlertDialogHeader>
      <AlertDialogFooter>
        <!-- 取消按钮 -->
        <AlertDialogCancel @click="activeDeleteId = null">
          取消
        </AlertDialogCancel>
        <!-- 确认删除按钮（使用 destructive 样式强调风险） -->
        <AlertDialogAction 
          class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
          @click="confirmDelete"
        >
          安全移除
        </AlertDialogAction>
      </AlertDialogFooter>
    </AlertDialogContent>
  </AlertDialog>
  </AppLayout>
</template>
