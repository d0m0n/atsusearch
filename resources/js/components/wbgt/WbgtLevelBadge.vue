<template>
  <span
    :class="[levelClass, 'inline-block font-bold rounded-sm', sizeClasses]"
    :aria-label="`WBGT ${wbgt}℃ — ${levelText}`"
  >
    <slot>WBGT {{ wbgt }}°C</slot>
  </span>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const props = withDefaults(defineProps<{
  wbgt: number | null
  size?: 'sm' | 'md' | 'lg'
}>(), {
  size: 'md',
})

interface LevelDef {
  class: string
  text: string
}

const LEVELS: LevelDef[] = [
  { class: 'wbgt-danger',         text: '危険' },
  { class: 'wbgt-severe-warning', text: '厳重警戒' },
  { class: 'wbgt-warning',        text: '警戒' },
  { class: 'wbgt-caution',        text: '注意' },
  { class: 'wbgt-safe',           text: 'ほぼ安全' },
]

const getLevel = (wbgt: number | null): LevelDef => {
  if (wbgt === null) return { class: 'bg-gray-200 text-dads-text-sub', text: 'データなし' }
  if (wbgt >= 31) return LEVELS[0]
  if (wbgt >= 28) return LEVELS[1]
  if (wbgt >= 25) return LEVELS[2]
  if (wbgt >= 21) return LEVELS[3]
  return LEVELS[4]
}

const levelClass = computed(() => getLevel(props.wbgt).class)
const levelText  = computed(() => getLevel(props.wbgt).text)

const sizeClasses = computed(() => ({
  sm: 'px-2 py-0.5 text-xs',
  md: 'px-3 py-1 text-sm',
  lg: 'px-4 py-2 text-base',
}[props.size]))
</script>
