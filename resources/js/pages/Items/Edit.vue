<!-- resources/js/Pages/Items/Edit.vue [完全体 - 基于Category大类编辑版] -->
<script setup lang="ts">
import { useForm, Link, Head } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import AppLayout from '@/layouts/AppLayout.vue';
import itemsroute from '@/routes/items';
import type { Item } from '@/types'
import { type BreadcrumbItem } from '@/types';

// 🎯 补齐大类契约类型
interface CategoryStub {
  id: number
  hs_code: string
  category_name: string
  unit: string
  elements_template: string[]
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'ERP / TMS' },
    { title: '大货款式 SKU 库', href: itemsroute.index().url },
    { title: '编辑款式 SKU' },
];

const props = defineProps<{
  item: Item
  categories: CategoryStub[] // 🎯 核心新加：接收后端控制层投递来的海关大类名录
}>()

// 🎯 核心更新：表单字段与数据库新结构100%配平，彻底剔除原大类接管的死属性
const form = useForm({
  category_id: props.item.category_id || '', // 👈 绑定所属海关大类
  sku: props.item.sku,
  name_cn: props.item.name_cn,
  name_en: props.item.name_en,
  tax_refund_rate: props.item.tax_refund_rate,
  purchase_price: props.item.purchase_price,
  item_image: null as File | null // 🎯 核心新加：承载图片文件对象
})

// 🎯 响应式核心：一秒捕获当前选中的海关大类实体，驱动右侧卡片高紧凑渲染
const selectedCategory = computed(() => {
  return props.categories.find(c => c.id === Number(form.category_id)) || null
})

const imagePreviewUrl = ref<string | null>(null)
const initialImagePath = props.item.image_path ? `/storage/${props.item.image_path}` : null
if (initialImagePath) {
  imagePreviewUrl.value = initialImagePath
}
// 🎯 响应式核心：一秒捕捉本地文件流，生成内存预览 URL
const handleImageFileChange = (e: Event) => {
  const target = e.target as HTMLInputElement
  if (target.files && target.files.length > 0) {
    const file = target.files[0]
    form.item_image = file
    imagePreviewUrl.value = URL.createObjectURL(file) // 👈 动态吐出预览
  }
}

const submit = () => {
  // 🎯 核心更新：强力强制转化为 Form 物理文件二进制对流上送
  form.put(itemsroute.update(props.item.id).url, {
    forceFormData: true,
    preserveScroll: true
  })
}
// const submit = () => {
//   // 🎯 使用最正宗的 Inertia 别名方式提交 PUT 请求，保障单页状态机平滑刷新
//   form.put(itemsroute.update(props.item.id).url)
// }
</script>

<template>
  <Head title="大货款式 SKU 库" />
  <AppLayout :breadcrumbs="breadcrumbs">
  <div class="min-h-screen p-8 max-w-3xl mx-auto space-y-6 bg-background text-foreground">
    <div class="flex items-center gap-2 text-sm text-muted-foreground">
      <Link :href="itemsroute.index()" class="font-medium transition-colors hover:text-foreground">大货款式库</Link>
      <span>/</span>
      <span class="text-primary font-semibold">编辑商品档案</span>
    </div>

    <Card class="bg-card border-border rounded-2xl shadow-lg shadow-primary/5">
      <CardHeader class="border-b border-border">
        <CardTitle class="text-2xl font-bold tracking-tight bg-linear-to-r from-primary to-success bg-clip-text text-transparent">修改款式 SKU 档案</CardTitle>
        <CardDescription class="mt-2 text-muted-foreground leading-6">当前正在修改商品货号：<span class="font-mono text-primary font-bold">{{ item.sku }}</span></CardDescription>
      </CardHeader>
      <CardContent class="pt-6">
        <form @submit.prevent="submit" class="space-y-5">
          
          <!-- 🎯 核心重构：选配与切换海关大类（内嵌高紧凑防抖要素雷达） -->
          <div class="p-4 border border-dashed rounded-xl bg-slate-50/50 dark:bg-slate-900/20 grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
            <div class="space-y-1.5 md:col-span-1">
              <Label class="text-primary font-bold">划转/归属海关大类</Label>
              <select 
                v-model="form.category_id" 
                class="w-full h-10 rounded-lg border border-border bg-background px-3 py-1 text-sm shadow-sm focus:border-primary focus:ring-1 focus:ring-primary"
                required
              >
                <option value="" disabled>请选择所属海关品类...</option>
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                  {{ cat.category_name }}
                </option>
              </select>
              <div v-if="form.errors.category_id" class="text-xs text-destructive">{{ form.errors.category_id }}</div>
            </div>

            <!-- 🎯 高紧凑要素渲染区：使用 .trim() 与 whitespace-nowrap 物理清洗绝平臃肿外观 -->
            <div class="md:col-span-2 text-xs text-muted-foreground space-y-1 bg-background p-2.5 border rounded-lg min-h-10 flex flex-col justify-center">
              <div v-if="selectedCategory" class="grid grid-cols-2 gap-x-4 gap-y-1 animate-fadeIn font-medium">
                <div>海关 H.S. 编码: <span class="font-mono font-bold text-primary">{{ selectedCategory.hs_code }}</span></div>
                <div>法定计量单位: <span class="font-bold text-slate-800 dark:text-slate-200">{{ selectedCategory.unit }}</span></div>
                <div class="col-span-2 mt-1 flex items-center flex-wrap gap-y-1">
                  <span class="text-muted-foreground mr-1 shrink-0">依法申报要素:</span>
                  <span class="inline-flex flex-wrap gap-1">
                    <Badge 
                      v-for="(tag, idx) in selectedCategory.elements_template" 
                      :key="idx" 
                      variant="outline" 
                      class="text-[10px] py-0.5 px-1.5 font-sans font-medium border-primary/20 bg-primary/5 text-primary whitespace-nowrap"
                    >
                      {{ typeof tag === 'string' ? tag.trim() : tag }}
                    </Badge>
                  </span>
                </div>
              </div>
              <span v-else class="text-center italic py-2 text-muted-foreground">⚡ 请选择大类以配平单证税则指纹...</span>
            </div>
          </div>

          <!-- 货号与退税率 -->
          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1.5">
              <Label for="sku" class="text-muted-foreground font-medium">企业内部具体款式货号 (SKU)</Label>
              <Input id="sku" v-model="form.sku" required class="rounded-lg border-border bg-background focus-visible:ring-ring" />
              <div v-if="form.errors.sku" class="text-xs text-destructive">{{ form.errors.sku }}</div>
            </div>
            <div class="space-y-1.5">
              <Label for="tax_refund_rate" class="text-muted-foreground font-medium">出口法定退税率 (%)</Label>
              <Input id="tax_refund_rate" type="number" step="0.01" v-model="form.tax_refund_rate" required class="rounded-lg border-border bg-background focus-visible:ring-ring" />
              <div v-if="form.errors.tax_refund_rate" class="text-xs text-destructive">{{ form.errors.tax_refund_rate }}</div>
            </div>
          </div>

          <div class="space-y-1.5">
            <Label for="name_cn" class="text-muted-foreground font-medium">款式具体中文品名</Label>
            <Input id="name_cn" v-model="form.name_cn" required class="rounded-lg border-border bg-background focus-visible:ring-ring" />
            <div v-if="form.errors.name_cn" class="text-xs text-destructive">{{ form.errors.name_cn }}</div>
          </div>

          <div class="space-y-1.5">
            <Label for="name_en" class="text-muted-foreground font-medium">外销具体英文品名</Label>
            <Input id="name_en" v-model="form.name_en" required class="rounded-lg border-border bg-background focus-visible:ring-ring" />
            <div v-if="form.errors.name_en" class="text-xs text-destructive">{{ form.errors.name_en }}</div>
          </div>

          <!-- 实际采购单价 -->
          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1.5">
              <Label for="purchase_price" class="text-muted-foreground font-medium">预估国内含税进价 (元/基准参考价)</Label>
              <div class="relative">
                <span class="absolute left-3 top-2.5 text-xs text-muted-foreground font-bold">￥</span>
                <Input id="purchase_price" type="number" step="0.01" v-model="form.purchase_price" required class="pl-7 rounded-lg border-border bg-background focus-visible:ring-ring" />
              </div>
              <div v-if="form.errors.purchase_price" class="text-xs text-destructive">{{ form.errors.purchase_price }}</div>
            </div>
          </div>
    <!-- 🎯 核心高能新加：大货具体款式影像存根管理框 (完美支持暗黑模式) -->
    <div class="border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/40 rounded-xl p-4 grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
      <div class="md:col-span-2 space-y-1.5">
        <Label class="text-slate-800 dark:text-slate-200 font-bold flex items-center gap-1">
          📷 款式大货实物影像存根 (Product Photo)
        </Label>
        <p class="text-[11px] text-muted-foreground leading-normal">
          请上载该款号对应的款式细节图。此图将常驻台账，作为面对海关查验与税局退税穿透抽查时的核心证明影像。仅支持 JPG、PNG 格式，最大限速 5MB。
        </p>
        <input 
          type="file" 
          accept=".jpg,.jpeg,.png"
          @change="handleImageFileChange"
          class="block w-full text-xs text-slate-500 file:mr-4 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-primary file:text-primary-foreground hover:file:opacity-90 cursor-pointer"
        />
      </div>

      <!-- 右侧：响应式高清预览视窗 -->
      <div class="md:col-span-1 flex justify-center">
        <div class="w-28 h-28 border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 rounded-xl overflow-hidden shadow-inner flex items-center justify-center text-xs text-slate-400">
          <img v-if="imagePreviewUrl" :src="imagePreviewUrl" class="w-full h-full object-cover animate-fadeIn" alt="SKU大货图" />
          <span v-else class="text-[10px] text-center font-medium p-2 italic text-slate-400 dark:text-slate-500">📷 暂无大货实物快照</span>
        </div>
      </div>
    </div>
          <!-- 底部控制区 -->
          <div class="flex justify-end gap-3 border-t pt-4 mt-2 border-border">
            <Button type="button" variant="outline" as-child class="rounded-md">
              <Link :href="itemsroute.index()">放弃更新</Link>
            </Button>
            <Button type="submit" class="rounded-md bg-primary text-primary-foreground shadow-lg shadow-primary/20 transition-all duration-200 hover:scale-[1.02] hover:brightness-110 hover:text-primary-foreground" :disabled="form.processing">
              {{ form.processing ? '正在保存...' : '更新商品档案' }}
            </Button>
          </div>

        </form>
      </CardContent>
    </Card>
  </div>
  </AppLayout>
</template>
