<script setup lang="ts">
import { usePage, router } from '@inertiajs/vue3'
import { onUnmounted } from 'vue'
import { toast } from 'vue-sonner' // 🎯 引入官方组件驱动
import { Toaster } from '@/components/ui/sonner' // 🎯 引入 Shadcn 官方 Toaster
import 'vue-sonner/style.css' // 🎯 引入 Sonner 必要流式样式

import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItem } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
        subtitle?: string;
    }>(),
    {
        breadcrumbs: () => [],
        subtitle: '',
    },
);

const page = usePage()

let lastFlashKey = ''

function showFlash() {
  const m = (page.props.flash as any)?.message
  if (!m) return

  // 用内容做唯一key，同一条消息只弹一次
  const key = JSON.stringify(m)
  if (key === lastFlashKey) return
  lastFlashKey = key

  // 短暂后清空，避免影响下一条相同内容的消息
  setTimeout(() => { lastFlashKey = '' }, 1000)

  if (typeof m === 'object') {
    const isError = m.type === 'error' || m.type === 'danger'
    const method = isError ? toast.error : toast.success
    method(m.title || m.message, {
      description: m.description,
      action: m.action_url ? {
        label: m.action_label,
        onClick: () => router.visit(m.action_url)
      } : undefined,
      duration: 5000,
    })
  } else {
    toast(m)
  }
}

// 跳转后的新页面（store → index 这类）
const removeNavigate = router.on('navigate', () => showFlash())

// 同页面请求完成后（destroy、patch 这类不跳转的）
const removeFinish = router.on('finish', () => showFlash())

onUnmounted(() => {
  removeNavigate()
  removeFinish()
})
</script>

<template>
        <Toaster theme="system" position="top-right" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            v-if="subtitle"
            data-test="layout-subtitle"
            class="border-b border-sidebar-border/70 bg-primary/5 px-6 py-2 text-sm text-primary md:px-4"
        >
            {{ subtitle }}
        </div>
        <slot />
        <!-- 🎯 彻底抛弃手写组件，将 Shadcn 官方底盘常驻最顶层，提供全网气泡分发 -->
    </AppLayout>

</template>
