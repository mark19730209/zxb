<script setup lang="ts">
import { useForm, Link } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import AppLayout from '@/layouts/AppLayout.vue';
import suppliersroute from '@/routes/suppliers' // 🎯 引入您的 Wayfinder 路由模块
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'ERP / TMS' },
    { title: '供应商档案', href: suppliersroute.index().url },
    { title: '新建供应商' },
];
const form = useForm({
  company_name: '',
  tax_id: '',
  company_address: '',
  company_phone: '',
  bank_name: '',
  bank_account: '',
  bank_code: '',
  contact_person: ''
})

const submit = () => {
  // 🎯 使用 Wayfinder 规范：推送高合规商务负载数据至存储端点
  form.post(suppliersroute.store.url())
}
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
  <div class="min-h-screen p-8 max-w-3xl mx-auto space-y-6 bg-background text-foreground">
    <div class="flex items-center gap-2 text-sm text-muted-foreground">
      <Link :href="suppliersroute.index()" class="font-medium transition-colors hover:text-foreground">供应商名录</Link>
      <span>/</span>
      <span class="text-primary font-semibold">登记新供货工厂</span>
    </div>

    <Card class="bg-card border-border rounded-2xl shadow-lg shadow-primary/5">
      <CardHeader class="border-b border-border">
        <CardTitle class="text-2xl font-bold tracking-tight bg-linear-to-r from-primary to-success bg-clip-text text-transparent">登记国内供货工厂档案</CardTitle>
        <CardDescription class="mt-2 text-muted-foreground leading-6">请根据工厂最新的法定专票资质严密填写，确保进项增值税发票核销链条完全合规</CardDescription>
      </CardHeader>
      <CardContent>
        <form @submit.prevent="submit" class="space-y-6">
          
          <!-- 区域 1：企业基本工商信息 -->
          <div class="space-y-4">
            <h3 class="text-xs font-bold uppercase tracking-wider text-muted-foreground">1. 工商基本台账</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="space-y-1.5">
                <Label for="company_name" class="text-muted-foreground font-medium">供应商公司全称 (抬头)</Label>
                <Input id="company_name" v-model="form.company_name" placeholder="如: 桐庐华艺针织有限公司" required class="rounded-lg border-border bg-background focus-visible:ring-ring" />
                <div v-if="form.errors.company_name" class="text-xs text-destructive">{{ form.errors.company_name }}</div>
              </div>
              <div class="space-y-1.5">
                <Label for="tax_id" class="text-muted-foreground font-medium">统一社会信用代码 (18位税号)</Label>
                <Input id="tax_id" v-model="form.tax_id" placeholder="如: 91330122720040617G" maxlength="18" class="font-mono rounded-lg border-border bg-background focus-visible:ring-ring" required />
                <div v-if="form.errors.tax_id" class="text-xs text-destructive">{{ form.errors.tax_id }}</div>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="md:col-span-2 space-y-1.5">
                <Label for="company_address" class="text-muted-foreground font-medium">法定注册地址</Label>
                <Input id="company_address" v-model="form.company_address" placeholder="如: 浙江省杭州市桐庐县横村镇桐千路898号" required class="rounded-lg border-border bg-background focus-visible:ring-ring" />
              </div>
              <div class="space-y-1.5">
                <Label for="company_phone" class="text-muted-foreground font-medium">企业电话</Label>
                <Input id="company_phone" v-model="form.company_phone" placeholder="如: 0571-58587378" required class="rounded-lg border-border bg-background focus-visible:ring-ring" />
              </div>
            </div>
          </div>

          <!-- 区域 2：财务开票与对公银行账号 -->
          <div class="space-y-4 border-t border-border pt-4">
            <h3 class="text-xs font-bold uppercase tracking-wider text-primary">2. 增值税专票开票及银行信息</h3>
            
            <div class="space-y-1.5">
              <Label for="bank_name" class="text-muted-foreground font-medium">开户银行全称（含网点分支行）</Label>
              <Input id="bank_name" v-model="form.bank_name" placeholder="如: 桐庐农村商业银行横村支行" required class="rounded-lg border-border bg-background focus-visible:ring-ring" />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="md:col-span-2 space-y-1.5">
                <Label for="bank_account" class="text-muted-foreground font-medium">对公银行账号</Label>
                <Input id="bank_account" v-model="form.bank_account" placeholder="如: 201000003721565" class="font-mono rounded-lg border-border bg-background focus-visible:ring-ring" required />
                <div v-if="form.errors.bank_account" class="text-xs text-destructive">{{ form.errors.bank_account }}</div>
              </div>
              <div class="space-y-1.5">
                <Label for="bank_code" class="text-muted-foreground font-medium">大额支付银行行号 (联行号)</Label>
                <Input id="bank_code" v-model="form.bank_code" placeholder="12位数字，如: 402331006063" maxlength="12" class="font-mono rounded-lg border-border bg-background focus-visible:ring-ring" />
              </div>
            </div>
          </div>

          <div class="space-y-1.5 border-t border-border pt-4">
            <Label for="contact_person" class="text-muted-foreground font-medium">工厂业务对接人 (备忘)</Label>
            <Input id="contact_person" v-model="form.contact_person" placeholder="可留空，如: 服装跟单经理" class="rounded-lg border-border bg-background focus-visible:ring-ring" />
          </div>

          <!-- 操作按钮 -->
          <div class="flex justify-end gap-3 border-t border-border pt-4 mt-2">
            <Button type="button" variant="outline" as-child class="rounded-md">
              <Link :href="suppliersroute.index()">取消返回</Link>
            </Button>
            <Button type="submit" class="rounded-md bg-primary hover:brightness-110 text-primary-foreground shadow-lg shadow-primary/20 transition-all duration-200 hover:scale-[1.02]" :disabled="form.processing">
              {{ form.processing ? '正在保存...' : '建立供货商合规档案' }}
            </Button>
          </div>

        </form>
      </CardContent>
    </Card>
  </div>
  </AppLayout>
</template>
