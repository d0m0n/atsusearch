<template>
  <div class="wbgt-timeline">
    <h4 class="text-sm font-medium text-dads-text mb-3">時間帯別WBGT予測</h4>

    <div v-if="items.length === 0" class="text-sm text-dads-text-sub py-4 text-center">
      データがありません
    </div>

    <div v-else class="overflow-x-auto -mx-1">
      <div class="flex gap-1 min-w-max px-1 pb-1">
        <div
          v-for="item in items"
          :key="item.hour"
          class="flex flex-col items-center gap-1 w-12"
        >
          <!-- 時刻 -->
          <span class="text-xs text-dads-text-sub tabular-nums">{{ formatHour(item.hour) }}</span>

          <!-- バー -->
          <div class="relative flex flex-col justify-end w-full" style="height: 60px">
            <div
              :class="getWbgtLevelClass(item.wbgt)"
              :style="{ height: barHeight(item.wbgt) }"
              class="w-full rounded-t-sm transition-all duration-page"
              :title="`${item.hour}時: ${item.wbgt}°C`"
            ></div>
          </div>

          <!-- WBGT値 -->
          <span
            :class="getWbgtTextClass(item.wbgt)"
            class="text-xs font-bold tabular-nums"
          >{{ item.wbgt }}</span>
        </div>
      </div>
    </div>

    <!-- 凡例 -->
    <div class="mt-3 flex flex-wrap gap-x-4 gap-y-1">
      <div v-for="legend in LEGENDS" :key="legend.label" class="flex items-center gap-1">
        <span :class="legend.class" class="inline-block w-3 h-3 rounded-sm"></span>
        <span class="text-xs text-dads-text-sub">{{ legend.label }}</span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface TimelineItem {
  hour: number
  wbgt: number
}

const props = defineProps<{
  items: TimelineItem[]
}>()

const LEGENDS = [
  { class: 'wbgt-danger',         label: '危険(31+)' },
  { class: 'wbgt-severe-warning', label: '厳重警戒(28+)' },
  { class: 'wbgt-warning',        label: '警戒(25+)' },
  { class: 'wbgt-caution',        label: '注意(21+)' },
  { class: 'wbgt-safe',           label: 'ほぼ安全' },
]

const maxWbgt = computed(() => Math.max(...props.items.map(i => i.wbgt), 35))

const barHeight = (wbgt: number): string => {
  const ratio = Math.max(0, wbgt) / maxWbgt.value
  return `${Math.round(ratio * 100)}%`
}

const getWbgtLevelClass = (wbgt: number): string => {
  if (wbgt >= 31) return 'wbgt-danger'
  if (wbgt >= 28) return 'wbgt-severe-warning'
  if (wbgt >= 25) return 'wbgt-warning'
  if (wbgt >= 21) return 'wbgt-caution'
  return 'wbgt-safe'
}

const getWbgtTextClass = (wbgt: number): string => {
  if (wbgt >= 31) return 'wbgt-text-danger'
  if (wbgt >= 28) return 'wbgt-text-severe-warning'
  if (wbgt >= 25) return 'wbgt-text-warning'
  if (wbgt >= 21) return 'wbgt-text-caution'
  return 'wbgt-text-safe'
}

const formatHour = (hour: number): string => `${String(hour).padStart(2, '0')}時`
</script>
