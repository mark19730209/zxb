<script setup lang="ts">
import { useForm, Link } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
// 🎯 核心补齐：引入 Shadcn/Vue 标准下拉框组件
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import AppLayout from '@/layouts/AppLayout.vue';
import contractsRoute from '@/routes/contracts'
import { type BreadcrumbItem } from '@/types';
import type { Importer } from '@/types/tms'

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'ERP/TMS' },
  { title: 'Contracts', href: contractsRoute.index().url },
];

defineProps<{
  importers: Importer[]
  exporters: Array<{ id: number, company_name_cn: string }> // 🎯 强类型接收我方主体
}>()

const form = useForm({
  contract_no: '',
  exporter_id: '1',
  importer_id: '1',
  contract_date: new Date().toISOString().split('T')[0],
  currency: 'USD',
  incoterms: 'FOB',
  payment_terms: 'T/T 100% DEPOSIT, 0% AGAINST COPY OF B/L',

  // 🎯 核心新加：主单国际物流大盘三要素（默认值按常规出海规则对齐）
  transport_mode: '2',        // 默认 2 | 水路运输
  port_of_loading: 'YAN',     // 默认上海港
  port_of_destination: 'INC'     // 目的港等待进口商选择级联触发
})

const submit = () => {
  form.submit(contractsRoute.store())
}
</script>


<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="min-h-screen p-8 max-w-2xl mx-auto space-y-6 bg-background text-foreground">
      <!-- 面包屑导航 -->
      <div class="flex items-center gap-2 text-sm text-muted-foreground">
        <Link :href="contractsRoute.index()" class="hover:text-foreground font-medium transition-colors">合同台账</Link>
        <span>/</span>
        <span class="text-primary font-medium">起草新合同</span>
      </div>

      <Card class="bg-card border-border rounded-2xl shadow-lg shadow-primary/5">
        <CardHeader class="border-b border-border">
          <CardTitle
            class="text-2xl font-bold tracking-tight bg-linear-to-r from-primary to-success bg-clip-text text-transparent">
            起草外贸出口合同 (Contract)</CardTitle>
          <CardDescription class="text-muted-foreground mt-2 leading-6">
            在此初始化主订单的商务销项核心属性。保存后可进入制单工作台填充货物明细和海关要素。
          </CardDescription>
        </CardHeader>
        <CardContent>
          <form @submit.prevent="submit" class="space-y-6">

            <!-- 排一：合同号与签约日期 -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="space-y-1.5">
                <Label for="contract_no">出口合同号 (Contract No.)</Label>
                <Input id="contract_no" v-model="form.contract_no" placeholder="如: CT20260625-01"
                  class="font-mono uppercase rounded-lg border-border bg-background focus-visible:ring-ring" required />
                <div v-if="form.errors.contract_no" class="text-xs text-destructive">{{ form.errors.contract_no }}</div>
              </div>
              <div class="space-y-1.5">
                <Label for="contract_date">签约日期 (Date of Agreement)</Label>
                <Input id="contract_date" type="date" v-model="form.contract_date"
                  class="rounded-lg border-border bg-background focus-visible:ring-ring" required />
              </div>
            </div>

            <!-- 排二：选择境外进口商买方 (Shadcn 改造版) -->
  <!-- 表单内部追加我方主体勾选区域 -->
  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    
    <!-- 🎯 核心新增：选择我方出口公司主体 -->
    <div class="space-y-1.5">
            <Label for="exporter_id">步骤 ①：选择我方经营出口主体 (Exporter)</Label>
              <Select :model-value="form.exporter_id ? String(form.exporter_id) : ''"
                @update:model-value="(val) => form.exporter_id = val ? String(val) : ''">
                <SelectTrigger class="w-full h-9 text-xs bg-background">
                  <SelectValue placeholder="请挑选本次出口的经营抬头..." />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="exp in exporters" :key="exp.id" :value="String(exp.id)">
                    {{ exp.company_name_cn }} (CHN)
                  </SelectItem>
                </SelectContent>
              </Select>
              <div v-if="form.errors.exporter_id" class="text-xs text-destructive">{{ form.errors.exporter_id }}</div>
    </div>

    <!-- 选择境外进口商 -->
    <div class="space-y-1.5">
              <Label for="importer_id">境外进口商 / 客户抬头 (Buyer Corporate)</Label>
              <Select :model-value="form.importer_id ? String(form.importer_id) : ''"
                @update:model-value="(val) => form.importer_id = val ? String(val) : ''">
                <SelectTrigger class="w-full h-9 text-xs bg-background">
                  <SelectValue placeholder="请选择此项合同绑定的境外买方..." />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="imp in importers" :key="imp.id" :value="String(imp.id)">
                    {{ imp.company_name_en }} ({{ imp.country_code }})
                  </SelectItem>
                </SelectContent>
              </Select>
              <div v-if="form.errors.importer_id" class="text-xs text-destructive">{{ form.errors.importer_id }}</div>
    </div>

  </div>
            <!-- 排三：外销结算币种与贸易术语 (Shadcn 改造版) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="space-y-1.5">
                <Label for="currency">结汇币种 (Currency)</Label>
                <Select :model-value="form.currency"
                  @update:model-value="(val) => form.currency = val ? String(val) : ''">
                  <SelectTrigger class="w-full h-9 text-xs bg-background">
                    <SelectValue placeholder="选择币种..." />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="USD">USD - 美元</SelectItem>
                    <SelectItem value="EUR">EUR - 欧元</SelectItem>
                    <SelectItem value="GBP">GBP - 英镑</SelectItem>
                    <SelectItem value="CNY">CNY - 跨境人民币</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div class="space-y-1.5">
                <Label for="incoterms">价格/贸易术语 (Incoterms)</Label>
                <Select :model-value="form.incoterms"
                  @update:model-value="(val) => form.incoterms = val ? String(val) : ''">
                  <SelectTrigger class="w-full h-9 text-xs bg-background">
                    <SelectValue placeholder="选择术语..." />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="FOB">FOB - 船上交货（离岸价）</SelectItem>
                    <SelectItem value="CIF">CIF - 成本加保险费、运费（到岸价）</SelectItem>
                    <SelectItem value="EXW">EXW - 工厂交付</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>

            <!-- 🌟 排四（核心新增）：国际物流核心大盘三要素三栏网格平平铺 -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 border-y border-dashed border-border py-4 my-2">

              <div class="space-y-1.5">
                <Label>运输方式 (Mode)</Label>
                <Select :model-value="form.transport_mode"
                  @update:model-value="(val) => form.transport_mode = val ? String(val) : ''">
                  <SelectTrigger class="w-full h-9 text-xs bg-background">
                    <SelectValue placeholder="请选择运输方式" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="2">2 | 水路运输 (SEA)</SelectItem>
                    <SelectItem value="5">5 | 航空运输 (AIR)</SelectItem>
                    <SelectItem value="3">3 | 铁路运输 (RAIL)</SelectItem>
                    <SelectItem value="4">4 | 公路运输 (ROAD)</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div class="space-y-1.5">
                <Label>出口港 (Loading Port)</Label>
                <Select :model-value="form.port_of_loading"
                  @update:model-value="(val) => form.port_of_loading = val ? String(val) : ''">
                  <SelectTrigger class="w-full h-9 text-xs bg-background">
                    <SelectValue placeholder="请选择出运港口" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="YAN">烟台港 (SHANGHAI, CN)</SelectItem>
                    <SelectItem value="SHA">上海港 (SHANGHAI, CN)</SelectItem>
                    <SelectItem value="TAO">青岛港 (QINGDAO, CN)</SelectItem>
                    <SelectItem value="NGB">宁波港 (NINGBO, CN)</SelectItem>
                    <SelectItem value="SZX">深圳港 (SHENZHEN, CN)</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div class="space-y-1.5">
                <Label>进口港 (Destination)</Label>
                <Select :model-value="form.port_of_destination"
                  @update:model-value="(val) => form.port_of_destination = val ? String(val) : ''">
                  <SelectTrigger class="w-full h-9 text-xs bg-background">
                    <SelectValue placeholder="选择境外目的港..." />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="PUS">釜山港 (BUSAN, KR)</SelectItem>
                    <SelectItem value="INC">仁川港 (INCHEON, KR)</SelectItem>
                    <SelectItem value="TYO">东京港 (TOKYO, JP)</SelectItem>
                    <SelectItem value="LAX">洛杉矶港 (LOS ANGELES, US)</SelectItem>
                  </SelectContent>
                </Select>
                <div v-if="form.errors.port_of_destination" class="text-[11px] text-destructive mt-0.5">{{
                  form.errors.port_of_destination }}</div>
              </div>

            </div>

            <!-- 排五：收汇付款条件 -->
            <div class="space-y-1.5">
              <Label for="payment_terms">收汇及付款条款 (Terms of Payment)</Label>
              <Input id="payment_terms" v-model="form.payment_terms"
                placeholder="如: T/T 30% deposit, 70% against scan BL"
                class="rounded-lg border-border bg-background focus-visible:ring-ring" required />
              <div v-if="form.errors.payment_terms" class="text-xs text-destructive">{{ form.errors.payment_terms }}
              </div>
            </div>

            <!-- 底部操作按钮 -->
            <div class="flex justify-end gap-3 border-t border-border pt-4 mt-2">
              <Button type="button" variant="outline" as-child class="rounded-md">
                <Link :href="contractsRoute.index()">取消返回</Link>
              </Button>
              <Button type="submit" :disabled="form.processing"
                class="rounded-md bg-primary hover:brightness-110 text-primary-foreground shadow-lg shadow-primary/20 transition-all duration-200 hover:scale-[1.02]">
                {{ form.processing ? '正在创建...' : '建立合同并锁定销项' }}
              </Button>
            </div>

          </form>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
