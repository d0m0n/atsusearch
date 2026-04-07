<template>
  <button
    :type="type"
    :disabled="disabled || loading"
    :class="[
      'btn-press inline-flex items-center justify-center font-medium rounded-sm transition-colors',
      sizeClasses,
      variantClasses,
      { 'opacity-50 cursor-not-allowed': disabled || loading },
    ]"
  >
    <span v-if="loading" class="mr-2 animate-spin" aria-hidden="true">⏳</span>
    <slot />
  </button>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const props = withDefaults(defineProps<{
  variant?: 'primary' | 'secondary' | 'ghost' | 'danger'
  size?: 'sm' | 'md' | 'lg'
  type?: 'button' | 'submit' | 'reset'
  disabled?: boolean
  loading?: boolean
}>(), {
  variant: 'primary',
  size: 'md',
  type: 'button',
  disabled: false,
  loading: false,
})

const sizeClasses = computed(() => ({
  sm: 'px-3 py-1 text-xs',
  md: 'px-4 py-2 text-sm',
  lg: 'px-6 py-3 text-base',
}[props.size]))

const variantClasses = computed(() => ({
  primary:   'bg-dads-primary text-white hover:bg-dads-primary-hover duration-button',
  secondary: 'bg-dads-bg-section text-dads-text border border-dads-border hover:border-dads-primary hover:text-dads-primary duration-button',
  ghost:     'text-dads-primary hover:bg-dads-bg-section duration-button',
  danger:    'bg-dads-error text-white hover:opacity-90 duration-button',
}[props.variant]))
</script>
