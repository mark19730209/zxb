<script setup lang="ts">
import { useForm, Link } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import AppLayout from '@/layouts/AppLayout.vue';
import suppliersroute from '@/routes/suppliers' // 🎯 引入您的 Wayfinder 路由模块
import type { Supplier } from '@/types'
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'ERP / TMS' },
    { title: '供应商档案', href: suppliersroute.index().url },
    { title: '编辑供应商' },
];
const props = defineProps<{
  supplier: Supplier
}>()

const form = useForm({
  company_name: props.supplier.company_name,
  tax_id: props.supplier.tax_id,
  company_address: props.supplier.company_address,
  company_phone: props.supplier.company_phone,
  bank_name: props.supplier.bank_name,
  bank_account: props.supplier.bank_account,
  bank_code: props.supplier.bank_code,
  contact_person: props.supplier.contact_person
})

const submit = () => {
  // 🎯 使用 Wayfinder 动态方法发送修改覆盖
  form.submit(suppliersroute.update(props.supplier.id))
}
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="min-h-screen p-8 max-w-3xl mx-auto space-y-6 bg-background text-foreground">
      <div class="flex items-center gap-2 text-sm text-muted-foreground">
        <Link
          :href="suppliersroute.index()"
          class="font-medium transition-colors hover:text-foreground"
        >供应商档案</Link>
        <span>/</span>
        <span class="text-primary font-semibold">编辑供应商</span>
      </div>

      <Card class="bg-card border-border rounded-2xl shadow-lg shadow-primary/5">
        <CardHeader class="border-b border-border">
          <CardTitle class="text-2xl font-bold tracking-tight bg-linear-to-r from-primary to-success bg-clip-text text-transparent">
            编辑供应商档案
          </CardTitle>
          <CardDescription class="mt-2 text-muted-foreground leading-6">
            当前修改供应商：<span class="text-primary font-bold">{{ supplier.company_name }}</span>
          </CardDescription>
        </CardHeader>
        <CardContent>
          <form @submit.prevent="submit" class="space-y-6">
            <!-- 工商基本台账 -->
            <div class="space-y-4">
              <h3 class="text-xs font-bold uppercase tracking-wider text-muted-foreground">1. 工商基本台账</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-1.5">
                  <Label for="company_name" class="text-muted-foreground font-medium">供应商公司全称 (抬头)</Label>
                  <Input id="company_name" v-model="form.company_name" required class="rounded-lg border-border bg-background focus-visible:ring-ring" />
                  <div v-if="form.errors.company_name" class="text-xs text-destructive">{{ form.errors.company_name }}</div>
                </div>
                <div class="space-y-1.5">
                  <Label for="tax_id" class="text-muted-foreground font-medium">统一社会信用代码</Label>
                  <Input id="tax_id" v-model="form.tax_id" maxlength="18" class="font-mono rounded-lg border-border bg-background focus-visible:ring-ring" required />
                  <div v-if="form.errors.tax_id" class="text-xs text-destructive">{{ form.errors.tax_id }}</div>
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-2 space-y-1.5">
                  <Label for="company_address" class="text-muted-foreground font-medium">法定注册地址</Label>
                  <Input id="company_address" v-model="form.company_address" required class="rounded-lg border-border bg-background focus-visible:ring-ring" />
                </div>
                <div class="space-y-1.5">
                  <Label for="company_phone" class="text-muted-foreground font-medium">企业电话</Label>
                  <Input id="company_phone" v-model="form.company_phone" required class="rounded-lg border-border bg-background focus-visible:ring-ring" />
                </div>
              </div>
            </div>

            <!-- 银行对公账号 -->
            <div class="space-y-4 border-t border-border pt-4">
              <h3 class="text-xs font-bold uppercase tracking-wider text-primary">2. 增值税专票开票及银行信息</h3>
              <div class="space-y-1.5">
                <Label for="bank_name" class="text-muted-foreground font-medium">开户银行全称</Label>
                <Input id="bank_name" v-model="form.bank_name" required class="rounded-lg border-border bg-background focus-visible:ring-ring" />
              </div>

              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-2 space-y-1.5">
                  <Label for="bank_account" class="text-muted-foreground font-medium">对公银行账号</Label>
                  <Input id="bank_account" v-model="form.bank_account" class="font-mono rounded-lg border-border bg-background focus-visible:ring-ring" required />
                  <div v-if="form.errors.bank_account" class="text-xs text-destructive">{{ form.errors.bank_account }}</div>
                </div>
                <div class="space-y-1.5">
                  <Label for="bank_code" class="text-muted-foreground font-medium">大额支付银行行号 (联行号)</Label>
                  <Input id="bank_code" v-model="form.bank_code" maxlength="12" class="font-mono rounded-lg border-border bg-background focus-visible:ring-ring" />
                </div>
              </div>
            </div>

            <div class="space-y-1.5 border-t border-border pt-4">
              <Label for="contact_person" class="text-muted-foreground font-medium">工厂业务对接人 (备忘)</Label>
              <Input id="contact_person" v-model="form.contact_person" class="rounded-lg border-border bg-background focus-visible:ring-ring" />
            </div>

            <!-- 操作按钮 -->
            <div class="flex justify-end gap-3 border-t border-border pt-4 mt-2">
              <Button type="button" variant="outline" as-child class="rounded-md">
                <Link :href="suppliersroute.index()">放弃修改</Link>
              </Button>
              <Button
                type="submit"
                :disabled="form.processing"
                class="rounded-md bg-primary hover:brightness-110 text-primary-foreground shadow-lg shadow-primary/20 transition-all duration-200 hover:scale-[1.02]"
              >
                确认更新合规档案
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
