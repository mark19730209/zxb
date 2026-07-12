<script setup lang="ts">
import { useForm, Link } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import AppLayout from '@/layouts/AppLayout.vue';
import exportersroute from '@/routes/exporters';
import type { Exporter } from '@/types'
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'ERP/TMS' },
    { title: 'Exporters', href: exportersroute.index().url },
    { title: 'Create Item' },
];
const props = defineProps<{
  exporter: Exporter
}>()

const form = useForm({
  company_name_cn: props.exporter.company_name_cn,
  company_name_en: props.exporter.company_name_en,
  company_address: props.exporter.company_address,
  contact_tel: props.exporter.contact_tel,
  tax_id: props.exporter.tax_id,
  customs_code: props.exporter.customs_code,
  bank_name: props.exporter.bank_name,
  bank_account: props.exporter.bank_account,
  swift_code: props.exporter.swift_code,
  bank_address: props.exporter.bank_address
})

const submit = () => {
  // 🎯 使用 Wayfinder 动态方法，指向精确资源的 PUT 覆盖更新
  form.submit(exportersroute.update(props.exporter.id))
}
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
  <div class="min-h-screen p-8 max-w-2xl mx-auto space-y-6 bg-background text-foreground">
    <div class="flex items-center gap-2 text-sm text-muted-foreground">
      <Link :href="exportersroute.index()" class="font-medium transition-colors hover:text-foreground">出口商名录</Link>
      <span>/</span>
      <span class="text-primary font-semibold">变更出口商数据</span>
    </div>

    <Card class="bg-card border-border rounded-2xl shadow-lg shadow-primary/5">
      <CardHeader class="border-b border-border">
        <CardTitle class="text-2xl font-bold tracking-tight bg-linear-to-r from-primary to-success bg-clip-text text-transparent">变更出口商合规档案</CardTitle>
        <CardDescription class="mt-2 text-muted-foreground leading-6">
          当前正在更新：<span class="font-bold text-primary">{{ exporter.company_name_en }}</span>
        </CardDescription>
      </CardHeader>
      <CardContent>
        <form @submit.prevent="submit" class="space-y-5">

          <div class="space-y-1.5">
            <Label for="company_name_cn" class="text-muted-foreground font-medium">报关用中文全称</Label>
            <Input id="company_name_cn" v-model="form.company_name_cn" required class="rounded-lg border-border bg-background focus-visible:ring-ring" />
            <div v-if="form.errors.company_name_cn" class="text-xs text-destructive">{{ form.errors.company_name_cn }}</div>
          </div>

          <div class="space-y-1.5">
            <Label for="company_name_en" class="text-muted-foreground font-medium">Invoice 用英文全称</Label>
            <Input id="company_name_en" v-model="form.company_name_en" font-serif uppercase required class="rounded-lg border-border bg-background focus-visible:ring-ring" />
            <div v-if="form.errors.company_name_en" class="text-xs text-destructive">{{ form.errors.company_name_en }}</div>
          </div>

          <div class="space-y-1.5">
            <Label for="company_address" class="text-muted-foreground font-medium">英文地址 (Invoice/提单抬头)</Label>
            <Textarea id="company_address" v-model="form.company_address" rows="3" required class="rounded-lg border-border bg-background focus-visible:ring-ring" />
            <div v-if="form.errors.company_address" class="text-xs text-destructive">{{ form.errors.company_address }}</div>
          </div>

          <div class="grid grid-cols-3 gap-4">
            <div class="space-y-1.5">
              <Label for="contact_tel" class="text-muted-foreground font-medium">联系电话</Label>
              <Input id="contact_tel" v-model="form.contact_tel" required class="rounded-lg border-border bg-background focus-visible:ring-ring" />
              <div v-if="form.errors.contact_tel" class="text-xs text-destructive">{{ form.errors.contact_tel }}</div>
            </div>
            <div class="space-y-1.5 col-span-2">
              <Label for="tax_id" class="text-muted-foreground font-medium">统一社会信用代码 (税号)</Label>
              <Input id="tax_id" v-model="form.tax_id" maxlength="18" required class="rounded-lg border-border bg-background focus-visible:ring-ring" />
              <div v-if="form.errors.tax_id" class="text-xs text-destructive">{{ form.errors.tax_id }}</div>
            </div>
          </div>

          <div class="space-y-1.5">
            <Label for="customs_code" class="text-muted-foreground font-medium">海关编码</Label>
            <Input id="customs_code" v-model="form.customs_code" maxlength="10" class="rounded-lg border-border bg-background focus-visible:ring-ring" />
            <div v-if="form.errors.customs_code" class="text-xs text-destructive">{{ form.errors.customs_code }}</div>
          </div>

          <div class="border-t border-border pt-5 space-y-5">
            <h3 class="text-sm font-bold text-muted-foreground">收汇银行信息</h3>

            <div class="space-y-1.5">
              <Label for="bank_name" class="text-muted-foreground font-medium">开户行全称</Label>
              <Input id="bank_name" v-model="form.bank_name" uppercase required class="rounded-lg border-border bg-background focus-visible:ring-ring" />
              <div v-if="form.errors.bank_name" class="text-xs text-destructive">{{ form.errors.bank_name }}</div>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div class="space-y-1.5">
                <Label for="bank_account" class="text-muted-foreground font-medium">银行账号</Label>
                <Input id="bank_account" v-model="form.bank_account" required class="rounded-lg border-border bg-background focus-visible:ring-ring font-mono" />
                <div v-if="form.errors.bank_account" class="text-xs text-destructive">{{ form.errors.bank_account }}</div>
              </div>
              <div class="space-y-1.5">
                <Label for="swift_code" class="text-muted-foreground font-medium">SWIFT CODE</Label>
                <Input id="swift_code" v-model="form.swift_code" uppercase required class="rounded-lg border-border bg-background focus-visible:ring-ring font-mono" />
                <div v-if="form.errors.swift_code" class="text-xs text-destructive">{{ form.errors.swift_code }}</div>
              </div>
            </div>

            <div class="space-y-1.5">
              <Label for="bank_address" class="text-muted-foreground font-medium">开户行地址</Label>
              <Textarea id="bank_address" v-model="form.bank_address" rows="2" required class="rounded-lg border-border bg-background focus-visible:ring-ring" />
              <div v-if="form.errors.bank_address" class="text-xs text-destructive">{{ form.errors.bank_address }}</div>
            </div>
          </div>

          <!-- 操作动作 -->
          <div class="flex justify-end gap-3 border-t border-border pt-4 mt-2">
            <Button type="button" variant="outline" as-child class="rounded-md">
              <Link :href="exportersroute.index()">放弃更新</Link>
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