<template>
  <div class="user-location-history bg-white rounded-lg shadow-lg p-6">
    <h2 class="text-2xl font-bold text-gray-800 mb-4">
      📍 検索履歴
    </h2>
    
    <!-- 履歴が空の場合 -->
    <div v-if="locations.length === 0" class="text-center py-8">
      <div class="text-gray-400 text-6xl mb-4">🔍</div>
      <p class="text-gray-500">まだ検索履歴がありません</p>
      <p class="text-gray-400 text-sm">地図上で場所をクリックして検索してみましょう</p>
    </div>

    <!-- 履歴一覧 -->
    <div v-else class="space-y-4">
      <div
        v-for="location in locations"
        :key="location.id"
        class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors"
      >
        <!-- 左側：地点情報 -->
        <div class="flex-1">
          <h3 class="font-semibold text-gray-800">{{ location.name }}</h3>
          <p class="text-gray-600 text-sm">{{ location.address }}</p>
          <div class="flex items-center mt-2 space-x-4">
            <span class="text-xs text-gray-500">
              📅 {{ formatDate(location.searched_at) }}
            </span>
            <span
              v-if="location.current_wbgt"
              :class="getWbgtLevelClass(location.current_wbgt)"
              class="px-2 py-1 rounded text-white text-xs font-bold"
            >
              WBGT: {{ location.current_wbgt }}
            </span>
          </div>
        </div>

        <!-- 右側：アクション -->
        <div class="flex items-center space-x-2">
          <!-- お気に入りボタン -->
          <button
            @click="toggleFavorite(location)"
            :class="location.is_favorite ? 'text-red-500' : 'text-gray-400'"
            class="p-2 hover:bg-gray-100 rounded-full transition-colors"
            :title="location.is_favorite ? 'お気に入りから削除' : 'お気に入りに追加'"
          >
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"/>
            </svg>
          </button>

          <!-- 地図で表示ボタン -->
          <button
            @click="showOnMap(location)"
            class="px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600 transition-colors text-sm"
          >
            地図で表示
          </button>

          <!-- 削除ボタン -->
          <button
            @click="removeLocation(location)"
            class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-full transition-colors"
            title="履歴から削除"
          >
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- お気に入り一覧 -->
    <div v-if="favoriteLocations.length > 0" class="mt-8">
      <h3 class="text-lg font-semibold text-gray-800 mb-3">
        ⭐ お気に入り
      </h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
        <div
          v-for="favorite in favoriteLocations"
          :key="favorite.id"
          @click="showOnMap(favorite)"
          class="p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-blue-50 hover:border-blue-300 transition-colors"
        >
          <h4 class="font-medium text-gray-800">{{ favorite.name }}</h4>
          <p class="text-gray-600 text-sm truncate">{{ favorite.address }}</p>
          <div
            v-if="favorite.current_wbgt"
            :class="getWbgtLevelClass(favorite.current_wbgt)"
            class="inline-block px-2 py-1 rounded text-white text-xs font-bold mt-1"
          >
            WBGT: {{ favorite.current_wbgt }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

// TypeScript型定義
interface Location {
  id: number
  name: string
  address: string
  latitude: number
  longitude: number
  is_favorite: boolean
  current_wbgt: number | null
  searched_at: string
}

// Props
const emit = defineEmits<{
  showLocation: [location: Location]
}>()

// リアクティブデータ
const locations = ref<Location[]>([])
const loading = ref(false)

// 計算プロパティ
const favoriteLocations = computed(() => 
  locations.value.filter(location => location.is_favorite)
)

// メソッド
const loadLocationHistory = async () => {
  loading.value = true
  try {
    const response = await axios.get('/api/locations/history')
    locations.value = response.data.data || []
  } catch (error) {
    console.error('履歴取得エラー:', error)
  } finally {
    loading.value = false
  }
}

const toggleFavorite = async (location: Location) => {
  try {
    const response = await axios.put(`/api/locations/${location.id}/favorite`)
    location.is_favorite = response.data.is_favorite
  } catch (error) {
    console.error('お気に入り設定エラー:', error)
  }
}

const showOnMap = (location: Location) => {
  emit('showLocation', location)
}

const removeLocation = async (location: Location) => {
  if (!confirm(`「${location.name}」を履歴から削除しますか？`)) {
    return
  }

  try {
    await axios.delete(`/api/locations/${location.id}`)
    locations.value = locations.value.filter(loc => loc.id !== location.id)
  } catch (error) {
    console.error('場所削除エラー:', error)
  }
}

const formatDate = (dateString: string): string => {
  const date = new Date(dateString)
  const now = new Date()
  const diffTime = now.getTime() - date.getTime()
  const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24))
  
  if (diffDays === 0) {
    return '今日'
  } else if (diffDays === 1) {
    return '昨日'
  } else if (diffDays < 7) {
    return `${diffDays}日前`
  } else {
    return date.toLocaleDateString('ja-JP')
  }
}

const getWbgtLevelClass = (wbgt: number): string => {
  if (wbgt >= 31) return 'bg-red-600'      // 危険
  if (wbgt >= 28) return 'bg-orange-500'   // 厳重警戒
  if (wbgt >= 25) return 'bg-yellow-500'   // 警戒
  if (wbgt >= 21) return 'bg-blue-500'     // 注意
  return 'bg-green-500'                    // ほぼ安全
}

// 新しい場所を履歴に追加する外部メソッド
const addLocation = (location: Location) => {
  const existingIndex = locations.value.findIndex(loc => loc.id === location.id)
  
  if (existingIndex >= 0) {
    // 既存の場合は更新
    locations.value[existingIndex] = { ...location, searched_at: new Date().toISOString() }
  } else {
    // 新規の場合は先頭に追加
    locations.value.unshift({ ...location, searched_at: new Date().toISOString() })
  }
}

// ライフサイクル
onMounted(() => {
  loadLocationHistory()
})

// 外部から呼び出し可能にするため
defineExpose({
  addLocation,
  loadLocationHistory
})
</script>

<style scoped>
.user-location-history {
  max-height: 600px;
  overflow-y: auto;
}

.user-location-history::-webkit-scrollbar {
  width: 6px;
}

.user-location-history::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 3px;
}

.user-location-history::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}

.user-location-history::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}
</style>