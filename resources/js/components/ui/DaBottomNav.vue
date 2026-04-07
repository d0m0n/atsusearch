<template>
  <nav
    class="fixed bottom-0 left-0 right-0 bg-white border-t border-dads-border safe-area-inset-bottom"
    aria-label="メインナビゲーション"
  >
    <ul class="flex justify-around items-center h-14">
      <li v-for="item in items" :key="item.href" class="flex-1">
        <a
          :href="item.href"
          :class="[
            'flex flex-col items-center justify-center gap-0.5 w-full h-full text-xs',
            'transition-colors duration-button',
            isActive(item.href)
              ? 'text-dads-primary font-medium'
              : 'text-dads-text-sub hover:text-dads-text',
          ]"
          :aria-current="isActive(item.href) ? 'page' : undefined"
        >
          <span class="text-xl leading-none" aria-hidden="true">{{ item.icon }}</span>
          <span>{{ item.label }}</span>
        </a>
      </li>
    </ul>
  </nav>
</template>

<script setup lang="ts">
interface NavItem {
  href: string
  icon: string
  label: string
}

withDefaults(defineProps<{
  items: NavItem[]
  currentPath?: string
}>(), {
  currentPath: '',
})

const isActive = (href: string): boolean => {
  if (typeof window === 'undefined') return false
  return window.location.pathname === href
}
</script>

<style scoped>
.safe-area-inset-bottom {
  padding-bottom: env(safe-area-inset-bottom, 0);
}
</style>
