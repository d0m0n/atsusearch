<template>
  <div class="da-input-wrapper">
    <label v-if="label" :for="inputId" class="block text-sm font-medium text-dads-text mb-1">
      {{ label }}
      <span v-if="required" class="text-dads-error ml-1" aria-hidden="true">*</span>
    </label>
    <div class="relative">
      <input
        :id="inputId"
        v-bind="$attrs"
        :value="modelValue"
        :type="type"
        :placeholder="placeholder"
        :disabled="disabled"
        :required="required"
        :class="[
          'w-full px-3 py-2 border rounded-sm text-sm text-dads-text bg-white',
          'focus:outline-none focus:ring-2 focus:ring-dads-primary/30 focus:border-dads-primary',
          'placeholder:text-dads-text-sub',
          'disabled:bg-dads-bg-section disabled:text-dads-text-sub disabled:cursor-not-allowed',
          error ? 'border-dads-error' : 'border-dads-border',
          'transition-colors duration-button',
        ]"
        @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
      />
    </div>
    <p v-if="error" class="mt-1 text-xs text-dads-error" role="alert">{{ error }}</p>
    <p v-else-if="hint" class="mt-1 text-xs text-dads-text-sub">{{ hint }}</p>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

defineOptions({ inheritAttrs: false })

const props = withDefaults(defineProps<{
  modelValue?: string
  label?: string
  type?: string
  placeholder?: string
  disabled?: boolean
  required?: boolean
  error?: string
  hint?: string
  id?: string
}>(), {
  type: 'text',
  disabled: false,
  required: false,
})

defineEmits<{
  'update:modelValue': [value: string]
}>()

const inputId = computed(() => props.id ?? `da-input-${Math.random().toString(36).slice(2, 8)}`)
</script>
