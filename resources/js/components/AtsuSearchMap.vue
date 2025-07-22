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
            <div class="flex items-center space-x-2">
              <button
                @click="toggleFavorite(location)"
                :class="location.is_favorite ? 'text-red-500' : 'text-gray-400'"
                class="text-xl hover:scale-110 transition-transform"
              >
                {{ location.is_favorite ? '❤️' : '🤍' }}
              </button>
              <button
                @click="removeLocation(location)"
                class="w-6 h-6 flex items-center justify-center rounded-full text-gray-400 hover:text-red-500 hover:bg-red-50 text-lg font-bold hover:scale-110 transition-all"
                title="地点を削除"
              >
                ×
              </button>
            </div>
          </div>
          
          <p class="text-sm text-gray-600 mb-3">{{ location.address }}</p>
          
          <div class="wbgt-display">
            <div
              v-if="location.current_wbgt"
              :style="{ backgroundColor: location.wbgt_level_color || getWbgtLevelColor(location.current_wbgt) }"
              class="inline-block px-3 py-1 rounded-full text-white font-bold text-sm mb-2"
            >
              WBGT: {{ location.current_wbgt }}°C
            </div>
            
            <div v-else class="inline-block px-3 py-1 rounded-full bg-gray-400 text-white font-bold text-sm mb-2">
              WBGT: データなし
            </div>
            
            <!-- WBGT観測地点情報の表示 -->
            <div v-if="location.wbgt_station && location.current_wbgt" class="mt-1">
              <div class="text-xs text-gray-500">
                WBGT観測地点: {{ location.wbgt_station.name }}
                <span v-if="location.wbgt_station.distance">({{ location.wbgt_station.distance }}km)</span>
              </div>
            </div>
            
            <!-- 気温情報の表示 -->
            <div v-if="location.temperature_data" class="mt-2">
              <div class="inline-block px-3 py-1 rounded-full bg-blue-100 text-blue-800 text-sm">
                気温: {{ location.temperature_data.temperature }}°C
              </div>
              <div class="text-xs text-gray-500 mt-1">
                気温観測地点: {{ location.nearest_station?.name }}
                <span v-if="location.nearest_station?.distance">({{ location.nearest_station.distance }}km)</span>
              </div>
            </div>
            
            <p class="text-xs text-gray-600 mt-1">
              {{ location.wbgt_level_text || 'データ取得中...' }}
            </p>
            
            <!-- 情報源の表示 -->
            <div class="text-xs text-gray-400 mt-2 border-t pt-2">
              <div>WBGT: <a href="https://www.wbgt.env.go.jp/" target="_blank" class="text-blue-500 hover:underline">環境省</a></div>
              <div v-if="location.temperature_data">
                気温: <a href="https://www.data.jma.go.jp/stats/data/mdrr/index.html" target="_blank" class="text-blue-500 hover:underline">気象庁</a>
              </div>
            </div>
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
              :style="{ backgroundColor: detailLocation.wbgt_level_color || getWbgtLevelColor(detailLocation.current_wbgt) }"
              class="inline-block px-3 py-2 rounded text-white font-bold"
            >
              {{ detailLocation.current_wbgt }}°C
            </div>
          </div>
          
          <div>
            <p class="text-sm text-gray-600">警戒レベル</p>
            <p class="font-medium">{{ detailLocation.wbgt_level_text || getWbgtLevelText(detailLocation.current_wbgt) }}</p>
          </div>
          
          <div v-if="detailLocation.wbgt_station && detailLocation.current_wbgt">
            <p class="text-sm text-gray-600">WBGT観測地点</p>
            <div class="flex items-center space-x-2">
              <span class="font-medium">{{ detailLocation.wbgt_station.name }}</span>
              <span class="text-sm text-gray-500">
                ({{ detailLocation.wbgt_station.distance }}km)
              </span>
            </div>
          </div>
          
          <div v-if="detailLocation.temperature_data">
            <p class="text-sm text-gray-600">気温</p>
            <div class="flex items-center space-x-2">
              <span class="font-bold text-lg">{{ detailLocation.temperature_data.temperature }}°C</span>
              <span class="text-sm text-gray-500">
                ({{ detailLocation.nearest_station?.name }}観測所)
              </span>
            </div>
          </div>
          
          <div class="bg-gray-50 p-3 rounded">
            <p class="text-sm text-gray-600 mb-2">データ提供元</p>
            <div class="space-y-1 text-xs text-gray-500">
              <div>WBGT: <a href="https://www.wbgt.env.go.jp/" target="_blank" class="text-blue-500 hover:underline">環境省</a></div>
              <div v-if="detailLocation.temperature_data">
                気温: <a href="https://www.data.jma.go.jp/stats/data/mdrr/index.html" target="_blank" class="text-blue-500 hover:underline">気象庁</a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

console.log('🔥 AtsuSearchMap.vue - Script setup executed!')
console.log('Vue imports loaded successfully')
console.log('Axios loaded:', axios ? 'OK' : 'Failed')

const props = defineProps({
  apiKey: {
    type: String,
    required: true
  },
  isLoggedIn: {
    type: Boolean,
    default: false
  }
})

console.log('AtsuSearchMap.vue - Props defined:', props)
console.log('AtsuSearchMap.vue - Props values:', {
  apiKey: props.apiKey ? props.apiKey.substring(0, 20) + '...' : 'Missing',
  isLoggedIn: props.isLoggedIn
})

const map = ref(null)
const selectedLocations = ref([])
const loadingLocation = ref(false)
const dataType = ref('forecast')
const mapLoaded = ref(false)
const showDetails = ref(false)
const detailLocation = ref({})
const locationMarkers = ref(new Map()) // 地点IDとマーカーのマッピング

const initMap = () => {
  console.log('🗺️ Initializing Google Maps...')
  
  if (!window.google) {
    console.error('Google Maps API not loaded')
    return
  }

  const mapElement = document.getElementById('map')
  if (!mapElement) {
    console.error('Map DOM element not found!')
    return
  }

  console.log('Creating Google Maps instance...')
  map.value = new window.google.maps.Map(mapElement, {
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

  console.log('Google Maps instance created:', map.value)

  // 地図クリックイベント
  map.value.addListener('click', async (event) => {
    console.log('🗺️ Map clicked!')
    if (event.latLng) {
      await addLocationMarker(event.latLng)
    }
  })
  
  mapLoaded.value = true
  console.log('🗺️ Google Maps initialization completed!')
}

const addLocationMarker = async (latLng) => {
  try {
    const latitude = latLng.lat()
    const longitude = latLng.lng()
    
    // 逆ジオコーディングで地点情報を取得（気温データも含む）
    const reverseResponse = await axios.post('/api/locations/reverse-geocode', {
      latitude,
      longitude
    })
    
    const locationData = reverseResponse.data
    
    // 地点を作成
    const locationResponse = await axios.post('/api/locations', {
      name: locationData.name,
      address: locationData.address,
      latitude,
      longitude
    })
    
    const newLocation = locationResponse.data.location
    
    // WBGTデータを取得
    try {
      console.log('🌡️ Fetching WBGT data for location ID:', newLocation.id)
      const wbgtResponse = await axios.get(`/api/wbgt/${newLocation.id}`, {
        params: { type: dataType.value }
      })
      
      console.log('🌡️ WBGT API response:', wbgtResponse.data)
      
      // 現在のWBGTデータを設定
      if (wbgtResponse.data.wbgt_data && wbgtResponse.data.wbgt_data.length > 0) {
        const currentHour = new Date().getHours()
        const currentData = wbgtResponse.data.wbgt_data.find(data => data.hour <= currentHour) || wbgtResponse.data.wbgt_data[0]
        
        console.log('✅ WBGT data found:', currentData)
        
        const wbgtValue = parseFloat(currentData.wbgt_value)
        
        newLocation.current_wbgt = wbgtValue
        newLocation.wbgt_level = getWbgtLevel(wbgtValue)
        newLocation.wbgt_level_text = getWbgtLevelText(wbgtValue)
        newLocation.wbgt_level_color = getWbgtLevelColor(wbgtValue)
        
        // WBGT観測地点情報を追加
        if (wbgtResponse.data.wbgt_station) {
          newLocation.wbgt_station = wbgtResponse.data.wbgt_station
        }
        
        console.log('✅ WBGT processed:', {
          value: wbgtValue,
          level: newLocation.wbgt_level,
          text: newLocation.wbgt_level_text,
          color: newLocation.wbgt_level_color
        })
      } else {
        console.warn('⚠️ No WBGT data found in response')
      }
    } catch (wbgtError) {
      console.warn('WBGT data fetch failed:', wbgtError)
      console.warn('Error details:', wbgtError.response?.data)
    }
    
    // 気温データを取得
    try {
      console.log('🌡️ Fetching temperature data for:', latitude, longitude)
      const tempResponse = await axios.post('/api/locations/temperature', {
        latitude,
        longitude
      })
      
      console.log('🌡️ Temperature API response:', tempResponse.data)
      console.log('🌡️ Response keys:', Object.keys(tempResponse.data))
      console.log('🌡️ Station data:', tempResponse.data.station)
      console.log('🌡️ Temperature data:', tempResponse.data.temperature_data)
      
      if (tempResponse.data.temperature_data) {
        newLocation.temperature_data = tempResponse.data.temperature_data
        newLocation.nearest_station = tempResponse.data.station
        console.log('✅ Temperature data added:', tempResponse.data.temperature_data)
      } else {
        console.warn('⚠️ No temperature data received')
        console.warn('⚠️ Full response:', tempResponse.data)
      }
    } catch (tempError) {
      console.warn('Temperature data fetch failed:', tempError)
      console.warn('Error details:', tempError.response?.data)
    }
    
    addOrUpdateLocation(newLocation)
    
    // マーカーを追加
    const marker = new window.google.maps.Marker({
      position: latLng,
      map: map.value,
      title: newLocation.name,
      icon: getMarkerIcon(newLocation.wbgt_level || 'safe')
    })
    
    // マーカーをマッピングに保存
    locationMarkers.value.set(newLocation.id, marker)
    
  } catch (error) {
    console.error('地点追加エラー:', error)
    alert('地点の追加に失敗しました: ' + (error.response?.data?.message || error.message))
  }
}

const addOrUpdateLocation = (location) => {
  const existingIndex = selectedLocations.value.findIndex(loc => loc.id === location.id)
  
  if (existingIndex >= 0) {
    // 既存の地点を更新
    selectedLocations.value[existingIndex] = location
  } else {
    // 新しい地点を配列の先頭に追加（上に表示）
    selectedLocations.value.unshift(location)
  }
}

const getWbgtLevel = (wbgt) => {
  if (wbgt >= 31) return 'danger'
  if (wbgt >= 28) return 'severe_warning'
  if (wbgt >= 25) return 'warning'
  if (wbgt >= 21) return 'caution'
  return 'safe'
}

const getWbgtLevelText = (wbgt) => {
  if (wbgt >= 31) return '危険：運動は原則中止'
  if (wbgt >= 28) return '厳重警戒：激しい運動は中止'
  if (wbgt >= 25) return '警戒：積極的に休憩'
  if (wbgt >= 21) return '注意：水分補給を忘れずに'
  return 'ほぼ安全'
}

const getWbgtLevelColor = (wbgt) => {
  if (wbgt >= 31) return '#dc2626'      // 危険（赤）
  if (wbgt >= 28) return '#f97316'      // 厳重警戒（オレンジ）
  if (wbgt >= 25) return '#eab308'      // 警戒（黄）
  if (wbgt >= 21) return '#3b82f6'      // 注意（青）
  return '#16a34a'                      // ほぼ安全（緑）
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

const removeLocation = (locationToRemove) => {
  const index = selectedLocations.value.findIndex(loc => loc.id === locationToRemove.id)
  if (index > -1) {
    // 地点リストから削除
    selectedLocations.value.splice(index, 1)
    
    // 対応するマーカーを地図から削除
    const marker = locationMarkers.value.get(locationToRemove.id)
    if (marker) {
      marker.setMap(null) // マーカーを地図から削除
      locationMarkers.value.delete(locationToRemove.id) // マッピングからも削除
    }
    
    // 詳細モーダルが開いている場合は閉じる
    if (showDetails.value && detailLocation.value.id === locationToRemove.id) {
      showDetails.value = false
    }
    
    console.log('✅ Location and marker removed:', locationToRemove.name)
  }
}

onMounted(() => {
  console.log('🗺️ AtsuSearchMap.vue mounted!')
  console.log('Props received:', props)
  console.log('API Key:', props.apiKey ? props.apiKey.substring(0, 20) + '...' : 'Missing')
  
  // DOM要素の確認
  const mapElement = document.getElementById('map')
  console.log('Map DOM element:', mapElement)
  
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