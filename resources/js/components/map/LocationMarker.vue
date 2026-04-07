<!--
  LocationMarker.vue
  Google Maps マーカーをVueコンポーネントとして管理する。
  マーカー自体はDOM外（Google Maps API）に描画されるため、
  このコンポーネントはマーカーのライフサイクルのみを担う。
-->
<template>
  <!-- マーカーはGoogle Maps APIが描画するため、このコンポーネントはDOMを持たない -->
</template>

<script setup lang="ts">
import { watch, onMounted, onUnmounted } from 'vue'

interface WbgtColors {
  danger: string
  severe_warning: string
  warning: string
  caution: string
  safe: string
}

const WBGT_COLORS: WbgtColors = {
  danger:         '#D32F2F',
  severe_warning: '#F57C00',
  warning:        '#FBC02D',
  caution:        '#0288D1',
  safe:           '#388E3C',
}

const props = withDefaults(defineProps<{
  map: google.maps.Map | null
  position: { lat: number; lng: number }
  title?: string
  wbgt?: number | null
  onClick?: () => void
}>(), {
  wbgt: null,
})

const emit = defineEmits<{
  click: []
}>()

let marker: google.maps.Marker | null = null
let clickListener: google.maps.MapsEventListener | null = null

const getMarkerColor = (wbgt: number | null): string => {
  if (wbgt === null) return '#D9D9DB'
  if (wbgt >= 31) return WBGT_COLORS.danger
  if (wbgt >= 28) return WBGT_COLORS.severe_warning
  if (wbgt >= 25) return WBGT_COLORS.warning
  if (wbgt >= 21) return WBGT_COLORS.caution
  return WBGT_COLORS.safe
}

const buildIcon = (wbgt: number | null): google.maps.Symbol => ({
  path: window.google.maps.SymbolPath.CIRCLE,
  scale: 9,
  fillColor: getMarkerColor(wbgt),
  fillOpacity: 0.9,
  strokeWeight: 2,
  strokeColor: '#FFFFFF',
})

const createMarker = () => {
  if (!props.map || !window.google) return

  marker = new window.google.maps.Marker({
    position: props.position,
    map: props.map,
    title: props.title,
    icon: buildIcon(props.wbgt),
  })

  clickListener = marker.addListener('click', () => emit('click'))
}

const updateIcon = () => {
  marker?.setIcon(buildIcon(props.wbgt))
}

onMounted(createMarker)

watch(() => props.wbgt,  updateIcon)
watch(() => props.title, (title) => marker?.setTitle(title ?? ''))
watch(() => props.map,   (newMap) => { if (newMap && !marker) createMarker() })

onUnmounted(() => {
  clickListener?.remove()
  marker?.setMap(null)
  marker = null
})
</script>
