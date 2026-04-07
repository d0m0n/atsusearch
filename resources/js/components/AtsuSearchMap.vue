<template>
  <div class="atsu-search-map">
    <div class="search-container mb-4">
      <div class="bg-white p-4 rounded-md border border-dads-border shadow-sm">
        <h2 class="text-xl font-bold text-dads-text mb-1">AtsuSearch — 暑さを検索、安全を発見</h2>
        <p class="text-sm text-dads-text-sub mb-4">地図上をクリックして、その地点の暑さ指数(WBGT)を確認できます</p>

        <div class="flex items-center gap-2">
          <button
            @click="getCurrentLocation"
            :disabled="loadingLocation"
            class="btn-press px-4 py-2 bg-dads-primary text-white rounded-sm text-sm font-medium hover:bg-dads-primary-hover disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-button"
          >
            {{ loadingLocation ? '取得中...' : '現在地で検索' }}
          </button>

          <select
            v-model="dataType"
            class="px-3 py-2 border border-dads-border rounded-sm text-sm text-dads-text focus:outline-none focus:border-dads-primary"
          >
            <option value="forecast">予測値</option>
            <option value="actual">実況値</option>
          </select>
        </div>
      </div>
    </div>

    <div class="map-container">
      <div id="map" class="w-full h-96 rounded-md border border-dads-border mb-4"></div>
    </div>

    <div v-if="selectedLocations.length > 0" class="locations-grid">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 card-stagger">
        <div
          v-for="location in selectedLocations"
          :key="location.id"
          class="location-card bg-white p-4 rounded-md border border-dads-border shadow-sm"
        >
          <div class="flex justify-between items-start mb-2">
            <h3 class="font-bold text-base text-dads-text leading-snug">{{ location.name }}</h3>
            <div class="flex items-center gap-2 ml-2 shrink-0">
              <button
                @click="toggleFavorite(location)"
                :class="location.is_favorite ? 'text-red-500' : 'text-dads-border'"
                class="btn-press text-xl leading-none"
                :aria-label="location.is_favorite ? 'お気に入りから削除' : 'お気に入りに追加'"
              >
                {{ location.is_favorite ? '♥' : '♡' }}
              </button>
              <button
                @click="removeLocation(location)"
                class="btn-press w-6 h-6 flex items-center justify-center rounded-full text-dads-text-sub hover:text-dads-error hover:bg-red-50 text-lg font-bold"
                aria-label="この地点を削除"
              >
                ×
              </button>
            </div>
          </div>

          <p class="text-sm text-dads-text-sub mb-3 leading-relaxed">{{ location.address }}</p>

          <div class="wbgt-display">
            <div v-if="location.current_wbgt !== null && location.current_wbgt !== undefined">
              <span
                :class="getWbgtLevelClass(location.current_wbgt)"
                class="inline-block px-3 py-1 rounded-sm text-sm font-bold mb-1"
              >
                WBGT {{ location.current_wbgt }}°C
              </span>
              <p class="text-xs text-dads-text-sub mt-1">{{ getWbgtLevelText(location.current_wbgt) }}</p>
            </div>
            <div v-else>
              <span class="inline-block px-3 py-1 rounded-sm bg-gray-200 text-dads-text-sub text-sm font-bold mb-1">
                WBGT データなし
              </span>
            </div>

            <div v-if="location.wbgt_station && location.current_wbgt !== null" class="mt-2 text-xs text-dads-text-sub">
              WBGT観測地点: {{ location.wbgt_station.name }}
              <span v-if="location.wbgt_station.distance">（{{ location.wbgt_station.distance }}km）</span>
            </div>

            <div v-if="location.temperature_data" class="mt-2">
              <span class="inline-block px-3 py-1 rounded-sm bg-blue-50 text-dads-caution text-sm">
                気温 {{ location.temperature_data.temperature }}°C
              </span>
              <div class="text-xs text-dads-text-sub mt-1">
                気温観測地点: {{ location.nearest_station?.name }}
                <span v-if="location.nearest_station?.distance">（{{ location.nearest_station.distance }}km）</span>
              </div>
            </div>

            <div class="mt-3 pt-2 border-t border-dads-border text-xs text-dads-text-sub">
              WBGT: <a href="https://www.wbgt.env.go.jp/" target="_blank" rel="noopener noreferrer" class="text-dads-primary hover:underline">環境省</a>
              <span v-if="location.temperature_data">
                ／ 気温: <a href="https://www.data.jma.go.jp/stats/data/mdrr/index.html" target="_blank" rel="noopener noreferrer" class="text-dads-primary hover:underline">気象庁</a>
              </span>
            </div>
          </div>

          <div class="mt-3 flex gap-2">
            <button
              @click="viewDetails(location)"
              class="btn-press px-3 py-1 bg-dads-bg-section text-dads-text text-xs rounded-sm border border-dads-border hover:border-dads-primary hover:text-dads-primary transition-colors duration-button"
            >
              詳細
            </button>
            <button
              v-if="isLoggedIn"
              @click="saveLocation(location)"
              class="btn-press px-3 py-1 bg-dads-primary text-white text-xs rounded-sm hover:bg-dads-primary-hover transition-colors duration-button"
            >
              保存
            </button>
          </div>
        </div>
      </div>
    </div>

    <div v-else-if="!mapLoaded" class="text-center py-12 text-dads-text-sub">
      地図を読み込み中...
    </div>

    <div v-else class="text-center py-12 text-dads-text-sub">
      地図上をクリックして暑さ指数を検索してください
    </div>

    <!-- 詳細モーダル -->
    <Teleport to="body">
      <div
        v-if="showDetails"
        class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
        @click="closeDetails"
      >
        <div class="bg-white p-6 rounded-lg max-w-md w-full mx-4 shadow-xl" @click.stop>
          <div class="flex justify-between items-start mb-4">
            <h3 class="text-lg font-bold text-dads-text">{{ detailLocation?.name }}</h3>
            <button @click="closeDetails" class="btn-press text-dads-text-sub hover:text-dads-text text-xl leading-none ml-4">×</button>
          </div>

          <div class="space-y-4 text-sm">
            <div>
              <p class="text-dads-text-sub mb-1">住所</p>
              <p class="font-medium text-dads-text">{{ detailLocation?.address }}</p>
            </div>

            <div v-if="detailLocation?.current_wbgt !== null && detailLocation?.current_wbgt !== undefined">
              <p class="text-dads-text-sub mb-1">現在のWBGT</p>
              <span
                :class="getWbgtLevelClass(detailLocation.current_wbgt)"
                class="inline-block px-4 py-2 rounded-sm font-bold"
              >
                {{ detailLocation.current_wbgt }}°C — {{ getWbgtLevelText(detailLocation.current_wbgt) }}
              </span>
            </div>

            <div v-if="detailLocation?.wbgt_station">
              <p class="text-dads-text-sub mb-1">WBGT観測地点</p>
              <p class="font-medium text-dads-text">
                {{ detailLocation.wbgt_station.name }}
                <span class="text-dads-text-sub font-normal">（{{ detailLocation.wbgt_station.distance }}km）</span>
              </p>
            </div>

            <div v-if="detailLocation?.temperature_data">
              <p class="text-dads-text-sub mb-1">気温</p>
              <p class="font-bold text-dads-text text-base">
                {{ detailLocation.temperature_data.temperature }}°C
                <span class="text-dads-text-sub font-normal text-sm">（{{ detailLocation.nearest_station?.name }}観測所）</span>
              </p>
            </div>

            <div class="bg-dads-bg-section p-3 rounded-sm">
              <p class="text-dads-text-sub mb-1">データ提供元</p>
              <div class="space-y-1 text-xs text-dads-text-sub">
                <div>WBGT: <a href="https://www.wbgt.env.go.jp/" target="_blank" rel="noopener noreferrer" class="text-dads-primary hover:underline">環境省</a></div>
                <div v-if="detailLocation?.temperature_data">
                  気温: <a href="https://www.data.jma.go.jp/stats/data/mdrr/index.html" target="_blank" rel="noopener noreferrer" class="text-dads-primary hover:underline">気象庁</a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import axios from 'axios'

interface WbgtStation {
  name: string
  distance?: number
}

interface TemperatureData {
  temperature: number
}

interface NearestStation {
  name: string
  distance?: number
}

interface LocationData {
  id: number
  name: string
  address: string
  latitude: number
  longitude: number
  is_favorite: boolean
  current_wbgt: number | null
  wbgt_level?: string
  wbgt_station?: WbgtStation
  temperature_data?: TemperatureData
  nearest_station?: NearestStation
}

const props = defineProps<{
  apiKey: string
  isLoggedIn?: boolean
}>()

const map = ref<google.maps.Map | null>(null)
const selectedLocations = ref<LocationData[]>([])
const loadingLocation = ref(false)
const dataType = ref<'forecast' | 'actual'>('forecast')
const mapLoaded = ref(false)
const showDetails = ref(false)
const detailLocation = ref<LocationData | null>(null)
const locationMarkers = ref(new Map<number, google.maps.Marker>())

// WBGT危険度レベル（DADS準拠カラー）
const WBGT_LEVELS = {
  danger:         { min: 31,  class: 'wbgt-danger',         text: '危険：運動は原則中止。外出をなるべく避ける', color: '#D32F2F' },
  severe_warning: { min: 28,  class: 'wbgt-severe-warning', text: '厳重警戒：激しい運動は中止。10〜20分おきに休憩', color: '#F57C00' },
  warning:        { min: 25,  class: 'wbgt-warning',        text: '警戒：積極的に休憩。水分・塩分補給', color: '#FBC02D' },
  caution:        { min: 21,  class: 'wbgt-caution',        text: '注意：水分補給を忘れずに', color: '#0288D1' },
  safe:           { min: -Infinity, class: 'wbgt-safe',     text: 'ほぼ安全：適宜水分補給', color: '#388E3C' },
} as const

const getWbgtLevelKey = (wbgt: number): keyof typeof WBGT_LEVELS => {
  if (wbgt >= 31) return 'danger'
  if (wbgt >= 28) return 'severe_warning'
  if (wbgt >= 25) return 'warning'
  if (wbgt >= 21) return 'caution'
  return 'safe'
}

const getWbgtLevelClass = (wbgt: number): string => WBGT_LEVELS[getWbgtLevelKey(wbgt)].class
const getWbgtLevelText  = (wbgt: number): string => WBGT_LEVELS[getWbgtLevelKey(wbgt)].text
const getWbgtLevelColor = (wbgt: number): string => WBGT_LEVELS[getWbgtLevelKey(wbgt)].color

const getMarkerIcon = (wbgt: number | null): google.maps.Symbol => ({
  path: window.google.maps.SymbolPath.CIRCLE,
  scale: 8,
  fillColor: wbgt !== null ? getWbgtLevelColor(wbgt) : '#D9D9DB',
  fillOpacity: 0.9,
  strokeWeight: 2,
  strokeColor: '#FFFFFF',
})

const initMap = () => {
  const mapElement = document.getElementById('map')
  if (!mapElement || !window.google) return

  map.value = new window.google.maps.Map(mapElement, {
    center: { lat: 35.6812, lng: 139.7671 },
    zoom: 10,
    styles: [{ featureType: 'poi', elementType: 'labels', stylers: [{ visibility: 'off' }] }],
  })

  map.value.addListener('click', async (event: google.maps.MapMouseEvent) => {
    if (event.latLng) await addLocationMarker(event.latLng)
  })

  mapLoaded.value = true
}

const addLocationMarker = async (latLng: google.maps.LatLng) => {
  const latitude  = latLng.lat()
  const longitude = latLng.lng()

  try {
    const reverseResponse = await axios.post('/api/geocoding/reverse', { latitude, longitude })
    const locationData = reverseResponse.data

    const locationResponse = await axios.post('/api/locations', {
      name:    locationData.name,
      address: locationData.address,
      latitude,
      longitude,
    })

    const newLocation: LocationData = { ...locationResponse.data.location, current_wbgt: null }

    // WBGTデータ取得
    try {
      const wbgtResponse = await axios.get(`/api/wbgt/${newLocation.id}`, { params: { type: dataType.value } })
      if (wbgtResponse.data.wbgt_data?.length > 0) {
        const currentHour = new Date().getHours()
        const currentData = wbgtResponse.data.wbgt_data.find((d: { hour: number }) => d.hour <= currentHour)
          ?? wbgtResponse.data.wbgt_data[0]
        newLocation.current_wbgt = parseFloat(currentData.wbgt_value)
        if (wbgtResponse.data.wbgt_station) {
          newLocation.wbgt_station = wbgtResponse.data.wbgt_station
        }
      }
    } catch {
      // WBGTデータなしは許容（マーカーは追加する）
    }

    // 気温データ取得
    try {
      const tempResponse = await axios.post('/api/locations/temperature', { latitude, longitude })
      if (tempResponse.data.temperature_data) {
        newLocation.temperature_data = tempResponse.data.temperature_data
        newLocation.nearest_station  = tempResponse.data.station
      }
    } catch {
      // 気温データなしは許容
    }

    addOrUpdateLocation(newLocation)

    const marker = new window.google.maps.Marker({
      position: latLng,
      map: map.value,
      title: newLocation.name,
      icon: getMarkerIcon(newLocation.current_wbgt),
    })
    locationMarkers.value.set(newLocation.id, marker)
  } catch (error: unknown) {
    const msg = axios.isAxiosError(error) ? error.response?.data?.message ?? error.message : String(error)
    alert('地点の追加に失敗しました: ' + msg)
  }
}

const addOrUpdateLocation = (location: LocationData) => {
  const idx = selectedLocations.value.findIndex(l => l.id === location.id)
  if (idx >= 0) {
    selectedLocations.value[idx] = location
  } else {
    selectedLocations.value.unshift(location)
  }
}

const getCurrentLocation = () => {
  if (!navigator.geolocation) {
    alert('お使いのブラウザでは位置情報がサポートされていません')
    return
  }

  loadingLocation.value = true

  navigator.geolocation.getCurrentPosition(
    async (position) => {
      const { latitude: lat, longitude: lng } = position.coords
      map.value?.setCenter({ lat, lng })
      map.value?.setZoom(12)
      await addLocationMarker(new window.google.maps.LatLng(lat, lng))
      loadingLocation.value = false
    },
    () => {
      alert('位置情報の取得に失敗しました')
      loadingLocation.value = false
    }
  )
}

const toggleFavorite = async (location: LocationData) => {
  if (!props.isLoggedIn) {
    alert('お気に入り機能を利用するにはログインが必要です')
    return
  }
  try {
    await axios.put(`/api/locations/${location.id}`, { is_favorite: !location.is_favorite })
    location.is_favorite = !location.is_favorite
  } catch {
    alert('お気に入りの更新に失敗しました')
  }
}

const saveLocation = async (location: LocationData) => {
  try {
    await axios.post('/api/locations', {
      name: location.name,
      address: location.address,
      latitude: location.latitude,
      longitude: location.longitude,
      is_favorite: false,
    })
    alert('地点を保存しました')
  } catch {
    alert('地点の保存に失敗しました')
  }
}

const viewDetails = (location: LocationData) => {
  detailLocation.value = location
  showDetails.value = true
}

const closeDetails = () => {
  showDetails.value = false
}

const removeLocation = (target: LocationData) => {
  const idx = selectedLocations.value.findIndex(l => l.id === target.id)
  if (idx > -1) {
    selectedLocations.value.splice(idx, 1)
    const marker = locationMarkers.value.get(target.id)
    marker?.setMap(null)
    locationMarkers.value.delete(target.id)
    if (showDetails.value && detailLocation.value?.id === target.id) {
      closeDetails()
    }
  }
}

onMounted(() => {
  if (!props.apiKey) return

  if (window.google?.maps) {
    initMap()
    return
  }

  const script = document.createElement('script')
  script.src = `https://maps.googleapis.com/maps/api/js?key=${props.apiKey}&libraries=places`
  script.async = true
  script.defer = true
  script.onload = () => setTimeout(initMap, 100)
  document.head.appendChild(script)
})
</script>

<style scoped>
.atsu-search-map {
  max-width: 1200px;
  margin: 0 auto;
  padding: 1rem;
}

.location-card {
  transition: transform 200ms ease-out, box-shadow 200ms ease-out;
}

.location-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

@media (prefers-reduced-motion: reduce) {
  .location-card {
    transition: none;
  }
  .location-card:hover {
    transform: none;
  }
}

.wbgt-display {
  min-height: 60px;
}
</style>
