<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { ref, onMounted } from 'vue'
import { PanelLeftClose, PanelLeftOpen } from "lucide-vue-next"
import { cn } from "@/lib/utils"
import { Button } from '@/components/ui/button'
import { useSidebar } from "./utils"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()

const { isMobile, state, toggleSidebar } = useSidebar()
const mounted = ref(false)
onMounted(() => { mounted.value = true })
</script>

<template>
  <Button
    data-sidebar="trigger"
    data-slot="sidebar-trigger"
    variant="ghost"
    size="icon"
    :class="cn('h-7 w-7', props.class)"
    @click="toggleSidebar"
  >
    <!-- 挂载完成后，再根据真实状态动态切换 -->
    <template v-if="!mounted">
      <PanelLeftClose />
    </template>
    <template v-else>
      <PanelLeftOpen v-if="isMobile || state === 'collapsed'" />
      <PanelLeftClose v-else />
    </template>
    <span class="sr-only">Toggle Sidebar</span>
  </Button>
</template>
