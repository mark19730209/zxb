<script setup lang="ts">
import { useForm, Link } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import AppLayout from '@/layouts/AppLayout.vue';
import importersroute from '@/routes/importers';
import type { Importer } from '@/types'
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'ERP/TMS' },
    { title: 'Importers', href: importersroute.index().url },
    { title: 'Create Item' },
];
const props = defineProps<{
  importer: Importer
}>()

const form = useForm({
  company_name_en: props.importer.company_name_en,
  company_address_en: props.importer.company_address_en,
  country_code: props.importer.country_code,
  contact_email: props.importer.contact_email,
  tax_no: props.importer.tax_no
})

const submit = () => {
  // 🎯 使用 Wayfinder 动态方法，指向精确资源的 PUT 覆盖更新
  form.submit(importersroute.update(props.importer.id))
}
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
  <div class="min-h-screen p-8 max-w-2xl mx-auto space-y-6 bg-background text-foreground">
    <div class="flex items-center gap-2 text-sm text-muted-foreground">
      <Link :href="importersroute.index()" class="font-medium transition-colors hover:text-foreground">境外客户名录</Link>
      <span>/</span>
      <span class="text-primary font-semibold">变更买方数据</span>
    </div>

    <Card class="bg-card border-border rounded-2xl shadow-lg shadow-primary/5">
      <CardHeader class="border-b border-border">
        <CardTitle class="text-2xl font-bold tracking-tight bg-linear-to-r from-primary to-success bg-clip-text text-transparent">变更境外客户合规档案</CardTitle>
        <CardDescription class="mt-2 text-muted-foreground leading-6">
          当前正在更新：<span class="font-bold text-primary">{{ importer.company_name_en }}</span>
        </CardDescription>
      </CardHeader>
      <CardContent>
        <form @submit.prevent="submit" class="space-y-5">
          
          <div class="space-y-1.5">
            <Label for="company_name_en" class="text-muted-foreground font-medium">境外进口商英文全称</Label>
            <Input id="company_name_en" v-model="form.company_name_en" font-serif uppercase required class="rounded-lg border-border bg-background focus-visible:ring-ring" />
            <div v-if="form.errors.company_name_en" class="text-xs text-destructive">{{ form.errors.company_name_en }}</div>
          </div>

          <div class="space-y-1.5">
            <Label for="company_address_en" class="text-muted-foreground font-medium">境外法定注册及清关送货地址</Label>
            <Textarea id="company_address_en" v-model="form.company_address_en" rows="3" required class="rounded-lg border-border bg-background focus-visible:ring-ring" />
            <div v-if="form.errors.company_address_en" class="text-xs text-destructive">{{ form.errors.company_address_en }}</div>
          </div>

          <div class="grid grid-cols-3 gap-4">
            <div class="space-y-1.5">
              <Label for="country_code" class="text-muted-foreground font-medium">贸易国别代码</Label>
              <Input id="country_code" v-model="form.country_code" maxlength="3" uppercase required class="rounded-lg border-border bg-background focus-visible:ring-ring" />
              <div v-if="form.errors.country_code" class="text-xs text-destructive">{{ form.errors.country_code }}</div>
            </div>
            <div class="space-y-1.5 col-span-2">
              <Label for="tax_no" class="text-muted-foreground font-medium">境外客户税号</Label>
              <Input id="tax_no" v-model="form.tax_no" class="rounded-lg border-border bg-background focus-visible:ring-ring" />
            </div>
          </div>

          <div class="space-y-1.5">
            <Label for="contact_email" class="text-muted-foreground font-medium">境外买方收单邮箱</Label>
            <Input id="contact_email" type="email" v-model="form.contact_email" class="rounded-lg border-border bg-background focus-visible:ring-ring" />
            <div v-if="form.errors.contact_email" class="text-xs text-destructive">{{ form.errors.contact_email }}</div>
          </div>

          <!-- 操作动作 -->
          <div class="flex justify-end gap-3 border-t border-border pt-4 mt-2">
            <Button type="button" variant="outline" as-child class="rounded-md">
              <Link :href="importersroute.index()">放弃更新</Link>
            </Button>
            <Button
              type="submit"
              :disabled="form.processing"
              class="rounded-md bg-primary hover:brightness-110 text-primary-foreground shadow-lg shadow-primary/20 transition-all duration-200 hover:scale-[1.02]"
            >
              {{ form.processing ? '正在保存...' : '确认更新档案' }}
            </Button>
          </div>

        </form>
      </CardContent>
    </Card>
  </div>
  </AppLayout>
</template>
