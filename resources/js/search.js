import { createApp, h } from 'vue'
import AtsuSearchMap from './components/AtsuSearchMap.vue'
import axios from 'axios'

console.log('=== AtsuSearch: search.js loading ===')
console.log('AtsuSearchMap component imported:', AtsuSearchMap)

// Axiosの設定
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'

// CSRFトークンの設定
const token = document.head.querySelector('meta[name="csrf-token"]')
if (token) {
  axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content
  console.log('CSRF token found:', token.content.substring(0, 20) + '...')
} else {
  console.error('CSRF token not found')
}

// Vueアプリの初期化関数
function initializeVueApp() {
  console.log('=== Initializing Vue App ===')
  
  const mountElement = document.getElementById('atsu-search-app')
  if (!mountElement) {
    console.error('Mount element #atsu-search-app not found!')
    return
  }
  
  console.log('Mount element found:', mountElement)

  // Bladeテンプレートからpropsを抽出
  const mapElement = mountElement.querySelector('atsu-search-map')
  const apiKey = mapElement?.getAttribute('api-key') || import.meta.env.VITE_GOOGLE_MAPS_API_KEY || ''
  const isLoggedIn = mapElement?.getAttribute(':is-logged-in') === 'true'
  
  // APIキーが設定されていない場合の警告
  if (!apiKey) {
    console.error('⚠️ Google Maps API key is not configured!')
    console.error('Please set GOOGLE_MAPS_API_KEY in your .env file')
    return
  }
  
  console.log('Props extracted from Blade:', { 
    apiKey: apiKey.substring(0, 20) + '...', 
    isLoggedIn 
  })

  // Vue 3アプリケーション作成（render関数使用）
  const app = createApp({
    render() {
      console.log('Render function called with props:', { apiKey, isLoggedIn })
      return h(AtsuSearchMap, {
        'api-key': apiKey,
        'is-logged-in': isLoggedIn
      })
    }
  })
  
  console.log('AtsuSearchMap component configured with render function')

  // アプリをマウント
  try {
    console.log('Mounting AtsuSearchMap component to #atsu-search-app...')
    const vueInstance = app.mount('#atsu-search-app')
    console.log('✅ AtsuSearchMap mounted successfully!', vueInstance)
  } catch (error) {
    console.error('❌ Error mounting AtsuSearchMap:', error)
    console.error('Error details:', error)
  }
}

// DOM読み込み完了後に初期化
if (document.readyState === 'loading') {
  console.log('DOM still loading, adding event listener...')
  document.addEventListener('DOMContentLoaded', initializeVueApp)
} else {
  console.log('DOM already loaded, initializing immediately...')
  initializeVueApp()
}

console.log('=== AtsuSearch: search.js loaded ===')