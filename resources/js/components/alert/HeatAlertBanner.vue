<template>
  <Transition name="alert-banner">
    <div
      v-if="visible && alerts.length > 0"
      role="alert"
      aria-live="assertive"
      class="alert-banner fixed top-0 left-0 right-0 z-50 bg-wbgt-danger text-white shadow-lg"
    >
      <div class="max-w-screen-lg mx-auto px-4 py-3 flex items-center justify-between gap-4">
        <div class="flex items-center gap-3 min-w-0">
          <span class="shrink-0 text-xl" aria-hidden="true">⚠</span>
          <div class="min-w-0">
            <p class="font-bold text-sm leading-tight">熱中症警戒アラート発表中</p>
            <p class="text-xs text-white/80 truncate mt-0.5">
              {{ alertSummary }}
            </p>
          </div>
        </div>
        <div class="flex items-center gap-2 shrink-0">
          <a
            v-if="detailUrl"
            :href="detailUrl"
            target="_blank"
            rel="noopener noreferrer"
            class="text-xs text-white/90 hover:text-white underline whitespace-nowrap"
          >
            詳細
          </a>
          <button
            @click="$emit('dismiss')"
            class="text-white/80 hover:text-white text-xl leading-none p-1"
            aria-label="アラートを閉じる"
          >
            ×
          </button>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface HeatAlert {
  prefecture_code: string
  prefecture_name: string
  alert_type: string
  target_date: string
}

const props = withDefaults(defineProps<{
  alerts: HeatAlert[]
  visible?: boolean
  detailUrl?: string
}>(), {
  visible: true,
})

defineEmits<{ dismiss: [] }>()

const alertSummary = computed(() => {
  if (props.alerts.length === 0) return ''
  const prefectures = [...new Set(props.alerts.map(a => a.prefecture_name))]
  if (prefectures.length === 1) return `${prefectures[0]}に発表`
  if (prefectures.length <= 3) return `${prefectures.join('・')}に発表`
  return `${prefectures.slice(0, 2).join('・')} ほか${prefectures.length - 2}都道府県`
})
</script>

<style scoped>
.alert-banner-enter-active {
  animation: alertSlideIn 300ms ease-out forwards;
}
.alert-banner-leave-active {
  transition: opacity 150ms ease-in, transform 150ms ease-in;
}
.alert-banner-leave-to {
  opacity: 0;
  transform: translateY(-100%);
}

@keyframes alertSlideIn {
  from { opacity: 0; transform: translateY(-100%); }
  to   { opacity: 1; transform: translateY(0); }
}

@media (prefers-reduced-motion: reduce) {
  .alert-banner-enter-active,
  .alert-banner-leave-active {
    animation: none;
    transition: none;
  }
}
</style>
