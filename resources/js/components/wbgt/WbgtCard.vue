<template>
  <div class="bg-white rounded-md border border-dads-border shadow-sm p-4">
    <div class="flex items-start justify-between mb-3">
      <div class="min-w-0">
        <h3 class="font-bold text-dads-text truncate">{{ stationName }}</h3>
        <p v-if="address" class="text-sm text-dads-text-sub mt-0.5 truncate">{{ address }}</p>
      </div>
      <WbgtLevelBadge :wbgt="wbgt" class="ml-3 shrink-0" />
    </div>

    <div v-if="wbgt !== null" class="mb-3">
      <!-- WBGT値 大表示 -->
      <div class="flex items-baseline gap-1">
        <span
          :class="levelTextClass"
          class="text-3xl font-bold tabular-nums transition-colors duration-page"
        >{{ wbgt }}</span>
        <span class="text-sm text-dads-text-sub">°C</span>
      </div>
      <p class="text-sm text-dads-text-sub mt-1">{{ levelAdvice }}</p>
    </div>

    <div v-else class="mb-3 text-sm text-dads-text-sub">
      データを取得中...
    </div>

    <div v-if="updatedAt" class="text-xs text-dads-text-sub border-t border-dads-border pt-2 mt-2">
      更新: {{ formattedUpdatedAt }}
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import WbgtLevelBadge from './WbgtLevelBadge.vue'

const props = withDefaults(defineProps<{
  wbgt: number | null
  stationName: string
  address?: string
  updatedAt?: string
}>(), {
  wbgt: null,
})

interface LevelDef {
  textClass: string
  advice: string
}

const getLevel = (wbgt: number | null): LevelDef => {
  if (wbgt === null) return { textClass: 'text-dads-text-sub', advice: '' }
  if (wbgt >= 31) return { textClass: 'wbgt-text-danger',         advice: '運動は原則中止。外出をなるべく避ける' }
  if (wbgt >= 28) return { textClass: 'wbgt-text-severe-warning', advice: '激しい運動は中止。10〜20分おきに休憩' }
  if (wbgt >= 25) return { textClass: 'wbgt-text-warning',        advice: '積極的に休憩。水分・塩分補給' }
  if (wbgt >= 21) return { textClass: 'wbgt-text-caution',        advice: '水分補給を忘れずに' }
  return              { textClass: 'wbgt-text-safe',              advice: '適宜水分補給' }
}

const levelTextClass = computed(() => getLevel(props.wbgt).textClass)
const levelAdvice    = computed(() => getLevel(props.wbgt).advice)

const formattedUpdatedAt = computed(() => {
  if (!props.updatedAt) return ''
  const date = new Date(props.updatedAt)
  if (isNaN(date.getTime())) return props.updatedAt
  const now = new Date()
  const diffMin = Math.floor((now.getTime() - date.getTime()) / 60000)
  if (diffMin < 1)  return 'たった今'
  if (diffMin < 60) return `${diffMin}分前`
  return date.toLocaleTimeString('ja-JP', { hour: '2-digit', minute: '2-digit' })
})
</script>
