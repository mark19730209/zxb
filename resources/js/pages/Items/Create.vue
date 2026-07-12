<!-- resources/js/Pages/Items/Create.vue [完全体 - 基于Category大类级联选配版] -->
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
import { type BreadcrumbItem } from '@/types';

// 🎯 补齐大类强类型契约
interface CategoryStub {
  id: number
  hs_code: string
  category_name: string
  unit: string
  elements_template: string[]
}

const props = defineProps<{
  categories: CategoryStub[] // 🎯 接收后端控制层投递来的海关大类名录
}>()

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'ERP / TMS' },
  { title: '大货款式 SKU 库', href: itemsroute.index().url }, // 🎯 规范语义
  { title: '新建款式 SKU' },
];

// 🎯 核心分割：表单对象中彻底剔除原大类的 hs_code, unit, elements_template
const form = useForm({
  category_id: '', // 👈 笔直塞入大类外键指针
  sku: '',
  name_cn: '',
  name_en: '',
  tax_refund_rate: 13.00,
  purchase_price: '',
  item_image: null as File | null // 🎯 核心新加：承载图片文件对象
})

// 🎯 响应式核心：一秒嗅探捕捉当前选中的海关大类实体
const selectedCategory = computed(() => {
  return props.categories.find(c => c.id === Number(form.category_id)) || null
})

const imagePreviewUrl = ref<string | null>(null)

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
  form.post(itemsroute.store().url, {
    forceFormData: true,
    preserveScroll: true
  })
}

// const submit = () => {
//   // 🎯 完全使用标准的 Ziggy 别名端点向后端 store 抛送款式大包
//   form.post(itemsroute.store().url)
// }
</script>

<template>

  <Head title="大货款式 SKU 库" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="min-h-screen p-8 max-w-3xl mx-auto space-y-6 bg-background text-foreground">
      <div class="flex items-center gap-2 text-sm text-muted-foreground">
        <Link :href="itemsroute.index()" class="hover:text-foreground font-medium transition-colors">大货款式库</Link>
        <span>/</span>
        <span class="text-primary font-semibold">建立具体大货款式 SKU</span>
      </div>

      <Card class="bg-card border-border rounded-2xl shadow-lg shadow-primary/5">
        <CardHeader class="border-b border-border">
          <CardTitle
            class="text-2xl font-bold tracking-tight bg-linear-to-r from-primary to-success bg-clip-text text-transparent">
            新建大货款式 SKU 档案
          </CardTitle>
          <CardDescription class="mt-2 text-muted-foreground leading-6">
            请先指定归属的海关 H.S. 品类。具体款式的通关要素和第一单位将自动跟随母体大类锁定。
          </CardDescription>
        </CardHeader>
        <CardContent class="pt-6">
          <form @submit.prevent="submit" class="space-y-5">
            <!-- 🎯 核心升级：步骤 ① 级联选配海关商品大类 -->
            <div
              class="p-4 border border-dashed rounded-xl bg-slate-50/50 dark:bg-slate-900/20 grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
              <div class="space-y-1.5 md:col-span-1">
                <Label class="text-primary font-bold">归属海关大类品类</Label>
                <select v-model="form.category_id"
                  class="w-full h-10 rounded-lg border border-border bg-background px-3 py-1 text-sm shadow-sm focus:border-primary focus:ring-1 focus:ring-primary">
                  <option value="" disabled>请选择所属海关品类...</option>
                  <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                    {{ cat.category_name }}
                  </option>
                </select>
                <div v-if="form.errors.category_id" class="text-xs text-destructive">{{ form.errors.category_id }}</div>
              </div>

              <!-- 🎯 高能视觉联动呈现区（大类未选时静默，选中时瞬间透视法理参数） -->
              <div
                class="md:col-span-2 text-xs text-muted-foreground space-y-1 bg-background p-2.5 border rounded-lg min-h-10 flex flex-col justify-center">
                <div v-if="selectedCategory" class="grid grid-cols-2 gap-x-4 gap-y-1 animate-fadeIn font-medium">
                  <div>海关 H.S. 编码: <span class="font-mono font-bold text-primary">{{ selectedCategory.hs_code }}</span>
                  </div>
                  <div>法定计量单位: <span class="font-bold text-slate-800 dark:text-slate-200">{{ selectedCategory.unit
                      }}</span></div>
                  <!-- resources/js/Pages/Items/Create.vue 级联联动提示区块核心修正 -->

                  <div class="col-span-2 mt-1 flex items-center flex-wrap gap-y-1">
                    <span class="text-muted-foreground mr-1 shrink-0">依法申报要素:</span>
                    <span class="inline-flex flex-wrap gap-1">
                      <!-- 🎯 核心修正 1：使用 .trim() 动态清洗掉后端带过来的 "\n" 换行和空格 -->
                      <!-- 🎯 核心修正 2：加注 whitespace-nowrap 确保汉字在 Badge 内部绝对不垂直换行 -->
                      <Badge v-for="(tag, idx) in selectedCategory.elements_template" :key="idx" variant="outline"
                        class="text-[10px] py-0.5 px-1.5 font-sans font-medium border-primary/20 bg-primary/5 text-primary whitespace-nowrap">
                        {{ typeof tag === 'string' ? tag.trim() : tag }}
                      </Badge>
                    </span>
                  </div>

                </div>
                <span v-else class="text-center italic py-2 text-muted-foreground">⚡ 请在左侧选择大类，系统将自动对齐海关税则指纹...</span>
              </div>
            </div>

            <!-- 基本商务信息 -->
            <div class="grid grid-cols-2 gap-4">
              <div class="space-y-1.5">
                <Label for="sku" class="text-muted-foreground font-medium">企业内部具体款式货号 (SKU)</Label>
                <Input id="sku" v-model="form.sku" placeholder="例如: HAT-HY-POMPOM-RED（简称-厂家-款式-颜色/规格）"
                  class="rounded-lg border-border bg-background focus-visible:ring-ring" />
                <div v-if="form.errors.sku" class="text-xs text-destructive">{{ form.errors.sku }}</div>
              </div>

              <div class="space-y-1.5">
                <Label for="tax_refund_rate" class="text-muted-foreground font-medium">出口法定退税率 (%)</Label>
                <Input id="tax_refund_rate" type="number" step="0.01" v-model="form.tax_refund_rate"
                  class="rounded-lg border-border bg-background focus-visible:ring-ring" />
                <div v-if="form.errors.tax_refund_rate" class="text-xs text-destructive">{{ form.errors.tax_refund_rate
                  }}</div>
              </div>
            </div>

            <div class="space-y-1.5">
              <Label for="name_cn" class="text-muted-foreground font-medium">款式具体中文品名 (Customs Item Description)</Label>
              <Input id="name_cn" v-model="form.name_cn" placeholder="用于国内开票与精细化报关，如: 304不锈钢带垫圈大头螺丝"
                class="rounded-lg border-border bg-background focus-visible:ring-ring" />
              <div v-if="form.errors.name_cn" class="text-xs text-destructive">{{ form.errors.name_cn }}</div>
            </div>

            <div class="space-y-1.5">
              <Label for="name_en" class="text-muted-foreground font-medium">外销具体英文品名 (Commercial Invoice Item
                Name)</Label>
              <Input id="name_en" v-model="form.name_en" placeholder="用于印在清关商业发票上，如: STAINLESS STEEL SCREW WITH GASKET"
                class="rounded-lg border-border bg-background focus-visible:ring-ring" />
              <div v-if="form.errors.name_en" class="text-xs text-destructive">{{ form.errors.name_en }}</div>
            </div>

            <!-- 实际预估含税采购进价 -->
            <div class="grid grid-cols-2 gap-4">
              <div class="space-y-1.5">
                <Label for="purchase_price" class="text-muted-foreground font-medium">预估国内含税进价 (元/基准参考价)</Label>
                <div class="relative">
                  <span class="absolute left-3 top-2.5 text-xs text-muted-foreground font-bold">￥</span>
                  <Input id="purchase_price" type="number" step="0.01" v-model="form.purchase_price" placeholder="0.00"
                    class="pl-7 rounded-lg border-border bg-background focus-visible:ring-ring" />
                </div>
                <div v-if="form.errors.purchase_price" class="text-xs text-destructive">{{ form.errors.purchase_price }}
                </div>
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
            <!-- 提交动作 -->
            <div class="flex justify-end gap-3 mt-2 border-t border-border pt-4">
              <Button type="button" variant="outline" as-child class="rounded-md">
                <Link :href="itemsroute.index()">取消返回</Link>
              </Button>
              <Button type="submit"
                class="rounded-md bg-primary hover:brightness-110 text-primary-foreground shadow-lg shadow-primary/20 transition-all duration-200 hover:scale-[1.02]"
                :disabled="form.processing">
                {{ form.processing ? '正在保存...' : '确认建立款式 SKU 档案' }}
              </Button>
            </div>

          </form>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
