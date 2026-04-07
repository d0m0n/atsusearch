<template>
  <div
    :class="[
      'bg-white rounded-md border border-dads-border',
      elevated ? 'shadow-sm' : '',
      clickable ? 'cursor-pointer hover:border-dads-primary transition-colors duration-page' : '',
    ]"
    v-bind="$attrs"
  >
    <div v-if="$slots.header" class="px-4 py-3 border-b border-dads-border">
      <slot name="header" />
    </div>
    <div :class="padded ? 'p-4' : ''">
      <slot />
    </div>
    <div v-if="$slots.footer" class="px-4 py-3 border-t border-dads-border bg-dads-bg-section rounded-b-md">
      <slot name="footer" />
    </div>
  </div>
</template>

<script setup lang="ts">
withDefaults(defineProps<{
  elevated?: boolean
  clickable?: boolean
  padded?: boolean
}>(), {
  elevated: true,
  clickable: false,
  padded: true,
})
</script>
