<!-- resources/js/Pages/Items/Index.vue [完全体 - 基于Category大类分割级联列表版] -->
<script setup lang="ts">
import { router, Link, Head } from '@inertiajs/vue3'
import { debouncedWatch } from '@vueuse/core' 
import { ref } from 'vue'
import { Badge } from '@/components/ui/badge' // 🎯 核心补齐：引入 Shadcn Badge 挂件
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import AppLayout from '@/layouts/AppLayout.vue';
import itemsroute from '@/routes/items';
import { type BreadcrumbItem } from '@/types';
import type { ItemPageProps } from '@/types/tms'

const props = defineProps<ItemPageProps>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'ERP/TMS' },
    { title: '大货款式 SKU 库' }, // 🎯 规范语义约束
];

const search = ref(props.filters.search || '')

// 使用 debouncedWatch 实现 300ms 纯净防抖检索
debouncedWatch(search, (value) => {
  const url = itemsroute.index.url() 
  router.get(
    url, 
    { search: value }, 
    { preserveState: true, replace: true }
  )
}, { debounce: 300 })

const deleteItem = (id: any) => {
  if (confirm('确定要安全注销该具体大货款式 SKU 档案吗？这将检查是否有未结清的出口业务合同。')) {
    router.delete(itemsroute.destroy(id))
  }
}

// 🎯 核心高能：逻辑下架状态机快捷跃迁切换
const toggleItemActiveStatus = (id: number, sku: string, currentStatus: boolean) => {
  const actionText = currentStatus ? '下架封存' : '重新激活';
  if (confirm(`状态变迁确认：您确信要对款式 [${sku}] 实施 [${actionText}] 操作吗？`)) {
    router.patch(`/items/${id.toString()}/toggle-active`, {
      preserveScroll: true,
    })
  }
}
</script>

<template>
  <Head title="大货款式 SKU 库" />
  <AppLayout :breadcrumbs="breadcrumbs">
  <div class="min-h-screen p-8 max-w-7xl mx-auto space-y-6 bg-background text-foreground">
    <div class="flex justify-between items-center">
      <div>
        <h1 class="text-2xl font-bold tracking-tight bg-linear-to-r from-primary to-success bg-clip-text text-transparent">大货款式 SKU 库</h1>
        <p class="text-sm text-muted-foreground mt-1">管理各工厂具体的款式货号（SKU）、外销品名描述及国内基准采购含税进价</p>
      </div>
      <Button as-child class="bg-primary hover:brightness-110 text-primary-foreground rounded-lg shadow-lg shadow-primary/20 transition-all duration-200 hover:scale-[1.02]">
         <Link :href="itemsroute.create()">+ 新建款式 SKU</Link> 
      </Button>
    </div>

    <Card class="bg-card border-border rounded-2xl shadow-lg shadow-primary/5">
      <CardHeader class="pb-3 flex flex-row items-center justify-between space-y-0 border-b border-border">
        <div class="space-y-1">
          <CardTitle class="text-base font-bold">大货款式台账列表</CardTitle>
          <CardDescription>当前系统内各个国内工厂负责开票及配套的大货具体货号清单</CardDescription>
        </div>
        <div class="w-72">
          <Input v-model="search" placeholder="输入款式名/SKU/海关品类搜索..." class="h-9 rounded-lg border-border bg-background focus-visible:ring-ring" />
        </div>
      </CardHeader>
      <CardContent>
        <div class="rounded-xl border border-border bg-card overflow-hidden">
          <Table>
            <TableHeader>
              <TableRow class="bg-primary text-primary-foreground">
                <TableHead class="w-30">内部货号 (SKU)</TableHead>
                <TableHead>款式大货名称 (中/英)</TableHead>
                <TableHead>归属海关品类 (H.S. 编码)</TableHead>
                <TableHead class="text-center">状态</TableHead> <!-- 🎯 状态列 -->
                <TableHead class="text-center">法定单位</TableHead>
                <TableHead class="text-right">出口退税率</TableHead>
                <TableHead class="text-right">预估含税进价</TableHead>
                <TableHead class="w-25 text-center">操作区</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <!-- <TableRow v-for="item in items.data" :key="item.id" class="hover:bg-secondary/40 transition-colors"> -->
              <TableRow 
                v-for="item in items.data" 
                :key="item.id" 
                class="hover:bg-secondary/40 transition-colors"
                :class="{ 'opacity-60 bg-slate-50/50 dark:bg-slate-900/10 italic text-slate-400': !item.is_actived }"
              >
                <!-- 1. SKU 货号列 -->
                <TableCell class="font-mono font-bold text-xs text-muted-foreground tracking-wide">
                  {{ item.sku }}
                </TableCell>
                
                <!-- 2. 中英文详细品名列 -->
                <!-- <TableCell>
                  <div class="font-bold text-sm text-slate-800 dark:text-slate-100">{{ item.name_cn }}</div>
                  <div class="text-xs text-muted-foreground font-serif max-w-70 truncate mt-0.5">{{ item.name_en }}</div>
                </TableCell> -->
<TableCell>
  <div class="flex items-center gap-3">
    
    <!-- 🎯 核心高能新加：行内高拟真微缩图控制视窗 (带 Hover 放大放大镜特效) -->
    <div class="relative group w-10 h-10 border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 rounded-lg overflow-hidden shrink-0 shadow-2xs select-none">
      <img 
        v-if="item.image_path" 
        :src="`/storage/${item.image_path}`" 
        class="w-full h-full object-cover transition-transform duration-200" 
        alt="款式微缩图" 
      />
      <div v-else class="w-full h-full flex items-center justify-center bg-slate-100 dark:bg-slate-800 text-[10px] text-slate-400">
        无
      </div>

      <!-- ⚡ 视觉悬浮彩蛋：当鼠标悬停在此缩略图上方时，在页面右前方瞬间绽放出一枚 200px 的超高清款式悬浮卡，实现盲操核对！ -->
      <div v-if="item.image_path" class="absolute left-12 top-0 z-50 hidden group-hover:block w-48 h-48 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-2xl p-1 overflow-hidden animate-fadeIn pointer-events-none">
        <img :src="`/storage/${item.image_path}`" class="w-full h-full object-cover rounded-lg" />
      </div>
    </div>

    <!-- 原有的中英文具体品名描述段（保持原样，完美横向排开） -->
    <div class="space-y-0.5">
      <div class="font-bold text-sm text-slate-800 dark:text-slate-100 flex items-center gap-1.5">
        <span>{{ item.name_cn }}</span>
      </div>
      <div class="text-xs text-slate-400 dark:text-slate-500 font-serif max-w-60 truncate">
        {{ item.name_en }}
      </div>
    </div>

  </div>
</TableCell>                
                <!-- 3. 🎯 核心升级：跨表穿透，高紧凑渲染大类名称及 10位 H.S. 编码 -->
                <TableCell>
                  <div class="flex items-center gap-1.5 flex-wrap">
                    <span class="font-semibold text-xs text-slate-700 dark:text-slate-300">
                      {{ item.category?.category_name || '未划转大类' }}
                    </span>
                    <Badge v-if="item.category?.hs_code" class="font-mono tracking-wider text-[10px] py-0 px-1 border-primary/20 bg-primary/5 text-primary whitespace-nowrap">
                      {{ item.category.hs_code }}
                    </Badge>
                  </div>
                </TableCell>
                <!-- 🎯 核心新加：动态渲染激活/启用状态 Badge -->
                <TableCell class="text-center">
                  <Badge v-if="item.is_actived" class="bg-green-50 text-green-700 dark:bg-green-950/20 dark:text-green-400 border-green-200/50 text-[10px] font-bold px-1.5 py-0">使用中</Badge>
                  <Badge v-else class="bg-slate-100 text-slate-400 dark:bg-slate-800/40 dark:text-slate-500 border-slate-200/50 text-[10px] px-1.5 py-0">已封存下架</Badge>
                </TableCell>
                <!-- 4. 🎯 核心升级：跨表穿透法定单位，彻底杜绝数据空白 -->
                <TableCell class="text-center text-xs font-bold text-slate-600 dark:text-muted-foreground">
                  {{ item.category?.unit || 'PCS' }}
                </TableCell>
                
                <TableCell class="text-right text-success font-black text-xs font-mono">
                  {{ item.tax_refund_rate }}%
                </TableCell>
                
                <TableCell class="text-right font-mono font-bold text-xs text-slate-900 dark:text-slate-100">
                  ￥{{ item.purchase_price ? (item.purchase_price).toLocaleString() : '0.00' }}
                </TableCell>
                
                <TableCell class="text-center">
                  <div class="flex justify-center gap-3">
                    <Link :href="itemsroute.edit(item.id)" class="text-primary font-bold hover:underline text-xs">编辑</Link>
                    <button @click="deleteItem(item.id)" class="text-destructive font-bold hover:underline text-xs">删除</button>
                                        <!-- 🎯 核心逻辑改造：替代危险的物理删除，提供一键快捷上/下架开关 -->
                    <button 
                      type="button" 
                      @click="toggleItemActiveStatus(item.id, item.sku, item.is_actived)" 
                      :class="item.is_actived ? 'text-amber-600 hover:text-amber-700 hover:underline' : 'text-green-600 hover:text-green-700 hover:underline'"
                    >
                      {{ item.is_actived ? '下架' : '激活' }}
                    </button>
                  </div>
                </TableCell>
              </TableRow>
              
              <TableRow v-if="items.data.length === 0">
                <TableCell colspan="7" class="text-center py-10 text-xs text-muted-foreground italic">
                  ⚡ 暂无符合条件的商品款式 SKU 档案数据，请点击右上角进行初始化建档
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>

        <!-- 分页导航栏 -->
        <div class="flex justify-end gap-2 mt-4" v-if="items.links && items.links.length > 3">
          <Component 
            v-for="(link, index) in items.links" 
            :is="link.url ? Link : 'span'" 
            :key="index"
            :href="link.url || ''"
            class="px-2.5 py-1 text-xs border rounded transition-colors font-semibold"
            :class="{ 
              'bg-primary text-primary-foreground border-primary shadow-md shadow-primary/10': link.active, 
              'text-muted-foreground bg-muted/40 cursor-not-allowed': !link.url, 
              'hover:bg-secondary text-foreground': link.url && !link.active 
            }"
          >
            <span v-html="link.label"></span>
          </Component>
        </div>
      </CardContent>
    </Card>
  </div>
  </AppLayout>
</template>
