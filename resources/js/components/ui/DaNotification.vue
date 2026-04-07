<template>
  <Transition name="notification">
    <div
      v-if="visible"
      role="alert"
      :class="[
        'flex items-start gap-3 px-4 py-3 rounded-md border text-sm',
        variantClasses,
      ]"
    >
      <span class="shrink-0 mt-0.5" aria-hidden="true">{{ icon }}</span>
      <div class="flex-1 min-w-0">
        <p v-if="title" class="font-medium mb-0.5">{{ title }}</p>
        <p class="leading-relaxed">
          <slot>{{ message }}</slot>
        </p>
      </div>
      <button
        v-if="dismissible"
        @click="$emit('dismiss')"
        class="shrink-0 text-current opacity-60 hover:opacity-100 transition-opacity leading-none"
        aria-label="閉じる"
      >
        ×
      </button>
    </div>
  </Transition>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const props = withDefaults(defineProps<{
  variant?: 'info' | 'success' | 'warning' | 'error'
  title?: string
  message?: string
  visible?: boolean
  dismissible?: boolean
}>(), {
  variant: 'info',
  visible: true,
  dismissible: false,
})

defineEmits<{ dismiss: [] }>()

const variantClasses = computed(() => ({
  info:    'bg-blue-50 border-dads-caution text-blue-900',
  success: 'bg-green-50 border-dads-success text-green-900',
  warning: 'bg-yellow-50 border-wbgt-warning text-yellow-900',
  error:   'bg-red-50 border-dads-error text-red-900',
}[props.variant]))

const icon = computed(() => ({
  info:    'ℹ',
  success: '✓',
  warning: '⚠',
  error:   '✕',
}[props.variant]))
</script>

<style scoped>
.notification-enter-active {
  transition: opacity 200ms ease-out, transform 200ms ease-out;
}
.notification-leave-active {
  transition: opacity 150ms ease-in;
}
.notification-enter-from {
  opacity: 0;
  transform: translateY(-4px);
}
.notification-leave-to {
  opacity: 0;
}

@media (prefers-reduced-motion: reduce) {
  .notification-enter-active,
  .notification-leave-active {
    transition: none;
  }
}
</style>
