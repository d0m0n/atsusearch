<template>
  <div class="atsu-search-map">
    <!-- デバッグ情報 -->
    <div class="bg-yellow-100 p-2 mb-4 rounded text-sm">
      <p><strong>Debug Info:</strong></p>
      <p>API Key: {{ apiKey ? apiKey.substring(0, 20) + '...' : 'Not provided' }}</p>
      <p>Is Logged In: {{ isLoggedIn }}</p>
      <p>Map Loaded: {{ mapLoaded }}</p>
    </div>
    
    <div class="search-container mb-4">
      <div class="search-box bg-white p-4 rounded-lg shadow-md">
        <h2 class="text-xl font-bold text-gray-800 mb-4">🌡️ AtsuSearch - 暑さを検索、安全を発見</h2>
        <p class="text-sm text-gray-600 mb-4">地図上をクリックして、その地点の暑さ指数(WBGT)を確認できます</p>
        
        <div class="flex items-center space-x-2 mb-4">
          <button 
            @click="getCurrentLocation"
            :disabled="loadingLocation"
            class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 disabled:opacity-50"
          >
            <span v-if="loadingLocation">📍 取得中...</span>
            <span v-else>📍 現在地を取得</span>
          </button>
          
          <select 
            v-model="dataType" 
            class="px-3 py-2 border border-gray-300 rounded focus:outline-none focus:border-blue-500"
          >
            <option value="forecast">予測値</option>
            <option value="actual">実況値</option>
          </select>
        </div>
      </div>
    </div>

    <div class="map-container">
      <div id="map" class="w-full h-96 rounded-lg shadow-lg mb-4"></div>
    </div>

    <div v-if="selectedLocations.length > 0" class="locations-grid">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div
          v-for="location in selectedLocations"
          :key="location.id"
          class="location-card bg-white p-4 rounded-lg shadow-md"
        >
          <div class="flex justify-between items-start mb-2">
            <h3 class="font-bold text-lg text-gray-800">{{ location.name }}</h3>
            <button
              @click="toggleFavorite(location)"
              :class="location.is_favorite ? 'text-red-500' : 'text-gray-400'"
              class="text-xl hover:scale-110 transition-transform"
            >
              {{ location.is_favorite ? '❤️' : '🤍' }}
            </button>
          </div>
          
          <p class="text-sm text-gray-600 mb-3">{{ location.address }}</p>
          
          <div class="wbgt-display">
            <div
              v-if="location.current_wbgt"
              :style="{ backgroundColor: location.wbgt_level_color }"
              class="inline-block px-3 py-1 rounded-full text-white font-bold text-sm mb-2"
            >
              WBGT: {{ location.current_wbgt }}°C
            </div>
            
            <div v-else class="inline-block px-3 py-1 rounded-full bg-gray-400 text-white font-bold text-sm mb-2">
              WBGT: データなし
            </div>
            
            <p class="text-xs text-gray-600">
              {{ location.wbgt_level_text || 'データ取得中...' }}
            </p>
          </div>
          
          <div class="mt-3 flex space-x-2">
            <button
              @click="viewDetails(location)"
              class="px-3 py-1 bg-green-500 text-white text-xs rounded hover:bg-green-600"
            >
              詳細表示
            </button>
            
            <button
              v-if="isLoggedIn"
              @click="saveLocation(location)"
              class="px-3 py-1 bg-blue-500 text-white text-xs rounded hover:bg-blue-600"
            >
              保存
            </button>
          </div>
        </div>
      </div>
    </div>
    
    <div v-else-if="!mapLoaded" class="text-center py-8 text-gray-500">
      地図を読み込み中...
    </div>
    
    <div v-else class="text-center py-8 text-gray-500">
      地図上をクリックして暑さ指数を検索してください
    </div>

    <!-- 詳細モーダル -->
    <div v-if="showDetails" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click="closeDetails">
      <div class="bg-white p-6 rounded-lg max-w-md w-full mx-4" @click.stop>
        <div class="flex justify-between items-start mb-4">
          <h3 class="text-xl font-bold">{{ detailLocation.name }}</h3>
          <button @click="closeDetails" class="text-gray-500 hover:text-gray-700 text-xl">×</button>
        </div>
        
        <div class="space-y-4">
          <div>
            <p class="text-sm text-gray-600">住所</p>
            <p class="font-medium">{{ detailLocation.address }}</p>
          </div>
          
          <div>
            <p class="text-sm text-gray-600">現在のWBGT</p>
            <div
              :style="{ backgroundColor: detailLocation.wbgt_level_color }"
              class="inline-block px-3 py-2 rounded text-white font-bold"
            >
              {{ detailLocation.current_wbgt }}°C
            </div>
          </div>
          
          <div>
            <p class="text-sm text-gray-600">警戒レベル</p>
            <p class="font-medium">{{ detailLocation.wbgt_level_text }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, inject } from 'vue'
import axios from 'axios'

console.log('AtsuSearchMap.vue - Script setup loading')

const props = defineProps({
  apiKey: String,
  isLoggedIn: {
    type: Boolean,
    default: false
  }
})

console.log('AtsuSearchMap.vue - Props defined:', props)

const map = ref(null)
const selectedLocations = ref([])
const loadingLocation = ref(false)
const dataType = ref('forecast')
const mapLoaded = ref(false)
const showDetails = ref(false)
const detailLocation = ref({})

const initMap = () => {
  if (!window.google) {
    console.error('Google Maps API not loaded')
    return
  }

  map.value = new window.google.maps.Map(document.getElementById('map'), {
    center: { lat: 35.6812, lng: 139.7671 }, // 東京駅
    zoom: 10,
    styles: [
      {
        featureType: 'poi',
        elementType: 'labels',
        stylers: [{ visibility: 'off' }]
      }
    ]
  })

  // 地図クリックイベント
  map.value.addListener('click', async (event) => {
    if (event.latLng) {
      await addLocationMarker(event.latLng)
    }
  })
  
  mapLoaded.value = true
}

const addLocationMarker = async (latLng) => {
  try {
    const latitude = latLng.lat()
    const longitude = latLng.lng()
    
    // 近隣の地点を検索
    const response = await axios.get('/api/wbgt/nearby', {
      params: { latitude, longitude, radius: 50 }
    })
    
    if (response.data.locations.length > 0) {
      // 既存の地点が見つかった場合
      const location = response.data.locations[0]
      addOrUpdateLocation(location)
      
      // マーカーを追加
      const marker = new window.google.maps.Marker({
        position: { lat: parseFloat(location.latitude), lng: parseFloat(location.longitude) },
        map: map.value,
        title: location.name,
        icon: getMarkerIcon(location.wbgt_level)
      })
      
    } else {
      // 新しい地点を作成
      const locationResponse = await axios.post('/api/locations', {
        latitude,
        longitude
      })
      
      const newLocation = locationResponse.data.location
      
      // WBGTデータを取得
      const wbgtResponse = await axios.get(`/api/wbgt/${newLocation.id}`, {
        params: { type: dataType.value }
      })
      
      // 現在のWBGTデータを設定
      if (wbgtResponse.data.wbgt_data.length > 0) {
        const currentHour = new Date().getHours()
        const currentData = wbgtResponse.data.wbgt_data.find(data => data.hour <= currentHour) || wbgtResponse.data.wbgt_data[0]
        
        newLocation.current_wbgt = currentData.wbgt_value
        newLocation.wbgt_level = currentData.wbgt_level
        newLocation.wbgt_level_text = currentData.wbgt_level_text
        newLocation.wbgt_level_color = currentData.wbgt_level_color
      }
      
      addOrUpdateLocation(newLocation)
      
      // マーカーを追加
      const marker = new window.google.maps.Marker({
        position: latLng,
        map: map.value,
        title: newLocation.name,
        icon: getMarkerIcon(newLocation.wbgt_level)
      })
    }
    
  } catch (error) {
    console.error('地点追加エラー:', error)
    alert('地点の追加に失敗しました')
  }
}

const addOrUpdateLocation = (location) => {
  const existingIndex = selectedLocations.value.findIndex(loc => loc.id === location.id)
  
  if (existingIndex >= 0) {
    selectedLocations.value[existingIndex] = location
  } else {
    selectedLocations.value.push(location)
  }
}

const getMarkerIcon = (wbgtLevel) => {
  const colors = {
    'danger': '#dc2626',
    'severe_warning': '#f97316',
    'warning': '#eab308',
    'caution': '#3b82f6',
    'safe': '#16a34a'
  }
  
  return {
    path: window.google.maps.SymbolPath.CIRCLE,
    scale: 8,
    fillColor: colors[wbgtLevel] || '#6b7280',
    fillOpacity: 0.8,
    strokeWeight: 2,
    strokeColor: '#ffffff'
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
      const lat = position.coords.latitude
      const lng = position.coords.longitude
      
      map.value.setCenter({ lat, lng })
      map.value.setZoom(12)
      
      await addLocationMarker({ lat: () => lat, lng: () => lng })
      loadingLocation.value = false
    },
    (error) => {
      console.error('位置情報取得エラー:', error)
      alert('位置情報の取得に失敗しました')
      loadingLocation.value = false
    }
  )
}

const toggleFavorite = async (location) => {
  if (!props.isLoggedIn) {
    alert('お気に入り機能を利用するにはログインが必要です')
    return
  }
  
  try {
    await axios.put(`/api/locations/${location.id}`, {
      is_favorite: !location.is_favorite
    })
    
    location.is_favorite = !location.is_favorite
  } catch (error) {
    console.error('お気に入り更新エラー:', error)
    alert('お気に入りの更新に失敗しました')
  }
}

const saveLocation = async (location) => {
  try {
    await axios.post('/api/locations', {
      name: location.name,
      address: location.address,
      latitude: location.latitude,
      longitude: location.longitude,
      is_favorite: false
    })
    
    alert('地点を保存しました')
  } catch (error) {
    console.error('地点保存エラー:', error)
    alert('地点の保存に失敗しました')
  }
}

const viewDetails = (location) => {
  detailLocation.value = location
  showDetails.value = true
}

const closeDetails = () => {
  showDetails.value = false
}

onMounted(() => {
  console.log('AtsuSearchMap mounted with API key:', props.apiKey)
  
  // Google Maps APIの読み込み
  if (window.google && window.google.maps) {
    console.log('Google Maps API already loaded')
    initMap()
  } else {
    console.log('Loading Google Maps API...')
    const script = document.createElement('script')
    script.src = `https://maps.googleapis.com/maps/api/js?key=${props.apiKey}&libraries=places`
    script.async = true
    script.defer = true
    script.onload = () => {
      console.log('Google Maps API loaded successfully')
      initMap()
    }
    script.onerror = (error) => {
      console.error('Failed to load Google Maps API:', error)
    }
    document.head.appendChild(script)
    console.log('Google Maps API script added to head')
  }
})
</script>

<style scoped>
.atsu-search-map {
  max-width: 1200px;
  margin: 0 auto;
  padding: 1rem;
}

.location-card {
  transition: transform 0.2s, box-shadow 0.2s;
}

.location-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.wbgt-display {
  min-height: 60px;
}
</style>