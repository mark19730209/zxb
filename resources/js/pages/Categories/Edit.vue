<script setup lang="ts">
import { useForm, Link, Head } from '@inertiajs/vue3'
import { ref } from 'vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select' // 🎯 引入 Shadcn Select 组件
import AppLayout from '@/layouts/AppLayout.vue';
import categories from '@/routes/categories'
import type { Category } from '@/types'
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'ERP / TMS' },
    { title: '海关 H.S. 品类库', href: categories.index().url },
    { title: '编辑 H.S. 品类' },
];
const props = defineProps<{
  category: Category
}>()

// 🎯 严格对齐中国海关总署（GACC）标准法定计量单位词典（与创建页完全同步）
const CUSTOMS_UNITS = [
  { value: '个', label: '个 (035) - 多数机电、箱包、日用品' },
  { value: '条', label: '条 (041) - 纺织织物、服装配饰、长条硬五金' }, // 🎯 已为您成功补入
  { value: '双', label: '双 (047) - 手套、鞋袜、足部防护' },
  { value: '千克', label: '千克 (011) - 螺丝、五金、化工、原材料' },
  { value: '台', label: '台 (040) - 机械设备、大件器具' },
  { value: '套', label: '套 (044) - 组合工具、套装服装' },
  { value: '米', label: '米 (140) - 纺织面料、线材、管材' },
  { value: '升', label: '升 (095) - 液体、涂料、成品油' },
]

const form = useForm({
  category_name: props.category.category_name,
  hs_code: props.category.hs_code,
  unit: props.category.unit, // 🎯 自动绑定回显后端传入的已有单位
  elements_template: props.category.elements_template || [] // 后端已在 Controller 内转回数组
})

const newElementTag = ref('')

const addElementTag = () => {
  if (newElementTag.value.trim() && !form.elements_template.includes(newElementTag.value.trim())) {
    form.elements_template.push(newElementTag.value.trim())
    newElementTag.value = ''
  }
}

const removeElementTag = (index : any) => {
  form.elements_template.splice(index, 1)
}

const submit = () => {
  // 🎯 优化：将老旧的 form.submit('put', ...) 升级为 Inertia 标准的简写 put 语法
  form.put(categories.update(props.category.id).url) 
}
</script>

<template>
  <Head title="海关 H.S. 品类库" />
  <AppLayout :breadcrumbs="breadcrumbs">
  <div class="min-h-screen p-8 max-w-3xl mx-auto space-y-6 bg-background text-foreground">
    <div class="flex items-center gap-2 text-sm text-muted-foreground">
      <Link :href="categories.index()" class="font-medium transition-colors hover:text-foreground">H.S. 品类库</Link>
      <span>/</span>
      <span class="text-primary font-semibold">编辑 H.S. 品类</span>
    </div>

    <Card class="bg-card border-border rounded-2xl shadow-lg shadow-primary/5">
      <CardHeader class="border-b border-border">
        <CardTitle class="text-2xl font-bold tracking-tight bg-linear-to-r from-primary to-success bg-clip-text text-transparent">修改 H.S. 品类</CardTitle>
        <CardDescription class="mt-2 text-muted-foreground leading-6">当前正在编辑 H.S. 编码：<span class="font-mono text-primary font-bold">{{ category.hs_code }}</span></CardDescription>
      </CardHeader>
      <CardContent>
        <form @submit.prevent="submit" class="space-y-5">
          <div class="space-y-1.5">
            <Label for="category_name" class="text-muted-foreground font-medium">报关中文品名</Label>
            <Input id="category_name" v-model="form.category_name" required class="rounded-lg border-border bg-background focus-visible:ring-ring" />
            <div v-if="form.errors.category_name" class="text-xs text-destructive">{{ form.errors.category_name }}</div>
          </div>

          <!-- 物流与退税参数 -->
          <div class="grid grid-cols-1 gap-4">
            <div class="space-y-1.5">
              <Label for="unit" class="text-muted-foreground font-medium">海关法定第一计量单位</Label>
              <!-- 🎯 使用 Shadcn UI Select 替换原本易填错的 Input，完美回显和覆盖 -->
              <Select v-model="form.unit">
                <SelectTrigger id="unit" class="w-full rounded-lg border-border bg-background focus-visible:ring-ring">
                  <SelectValue placeholder="请选择海关标准单位" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="u in CUSTOMS_UNITS" :key="u.value" :value="u.value">
                    {{ u.label }}
                  </SelectItem>
                </SelectContent>
              </Select>
              <div v-if="form.errors.unit" class="text-xs text-destructive">{{ form.errors.unit }}</div>
            </div>
          </div>

          <!-- 海关申报要素调整区 -->
          <div class="border border-primary/20 bg-primary/5 rounded-xl p-4 space-y-3">
            <div>
              <Label class="text-primary font-bold">调整海关规范申报要素模板</Label>
              <p class="text-xs text-muted-foreground mt-0.5">注意：修改此处只会影响新建立的出口合同，老合同已存根的要素串不会改变。</p>
            </div>

            <div class="flex flex-wrap gap-2 p-3 bg-card border border-border rounded-xl min-h-12 items-center">
              <span 
                v-for="(tag, index) in form.elements_template" 
                :key="index" 
                class="inline-flex items-center gap-1.5 px-2 py-1 bg-primary/10 text-primary border border-primary/20 rounded-md shadow-sm text-xs font-medium"
              >
                {{ index + 1 }}. {{ tag }}
                <button type="button" @click="removeElementTag(index)" class="text-destructive hover:text-destructive font-bold text-[10px]">✕</button>
              </span>
              <span v-if="form.elements_template.length === 0" class="text-muted-foreground text-xs">尚未添加任何要素要求，请在下方追加</span>
            </div>

            <div class="flex gap-2 w-1/2">
              <Input v-model="newElementTag" placeholder="追加新要素..." h-8 text-xs class="rounded-lg border-border bg-background focus-visible:ring-ring" @keydown.enter.prevent="addElementTag" />
              <Button type="button" variant="outline" size="sm" class="border-primary/20 bg-primary/5 text-primary hover:bg-primary rounded-md" @click="addElementTag">追加</Button>
            </div>
          </div>

          <div class="flex justify-end gap-3 border-t pt-4 mt-2 border-border">
            <Button type="button" variant="outline" as-child class="rounded-md">
              <Link :href="categories.index()">返回 H.S. 品类库</Link>
            </Button>
            <Button type="submit" class="rounded-md bg-primary text-primary-foreground shadow-lg shadow-primary/20 transition-all duration-200 hover:scale-[1.02] hover:brightness-110 hover:text-primary-foreground" :disabled="form.processing">
              {{ form.processing ? '正在保存...' : '更新 H.S. 品类' }}
            </Button>
          </div>

        </form>
      </CardContent>
    </Card>
  </div>
  </AppLayout>
</template>
