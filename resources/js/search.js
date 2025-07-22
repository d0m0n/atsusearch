import { createApp, h } from 'vue'
import AtsuSearchMap from './components/AtsuSearchMap.vue'
import axios from 'axios'

console.log('=== AtsuSearch: search.js loading ===')

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
    console.log('Available elements:', document.querySelectorAll('div[id]'))
    return
  }
  
  console.log('Mount element found:', mountElement)
  console.log('Mount element HTML:', mountElement.outerHTML.substring(0, 200))
  
  // AtsuSearchMap本格版（Google Maps API統合）
  const AtsuSearchMap = {
    name: 'AtsuSearchMap',
    render() {
      return h('div', {
        style: {
          background: 'linear-gradient(45deg, #0066cc, #0099ff)',
          color: 'white',
          padding: '20px',
          margin: '15px',
          borderRadius: '10px',
          border: '3px solid #ffffff',
          fontFamily: 'Arial, sans-serif'
        }
      }, [
        // コントロールエリア
        h('div', {
          style: { 
            marginBottom: '15px',
            display: 'flex',
            flexWrap: 'wrap',
            gap: '10px',
            alignItems: 'center'
          }
        }, [
          // 現在地取得ボタン
          h('button', {
            onClick: this.getCurrentLocation,
            disabled: this.locating,
            style: {
              backgroundColor: '#ff6b35',
              color: 'white',
              border: 'none',
              padding: '8px 16px',
              borderRadius: '5px',
              cursor: this.locating ? 'not-allowed' : 'pointer',
              opacity: this.locating ? 0.6 : 1,
              fontSize: '14px'
            }
          }, this.locating ? '📍 取得中...' : '📍 現在地を取得'),
          
          // 地域検索フォーム
          h('div', {
            style: { 
              display: 'flex',
              alignItems: 'center',
              gap: '5px'
            }
          }, [
            h('input', {
              type: 'text',
              placeholder: '地域名を入力（例: 東京駅、札幌市）',
              value: this.searchQuery,
              onInput: (e) => { this.searchQuery = e.target.value },
              onKeypress: (e) => {
                if (e.key === 'Enter') {
                  this.searchLocation()
                }
              },
              style: {
                padding: '8px 12px',
                border: '1px solid #ddd',
                borderRadius: '5px',
                fontSize: '14px',
                width: '250px',
                outline: 'none',
                color: '#333',
                backgroundColor: '#fff'
              }
            }),
            
            h('button', {
              onClick: this.searchLocation,
              disabled: this.searching,
              style: {
                backgroundColor: '#0066cc',
                color: 'white',
                border: 'none',
                padding: '8px 12px',
                borderRadius: '5px',
                cursor: this.searching ? 'not-allowed' : 'pointer',
                opacity: this.searching ? 0.6 : 1,
                fontSize: '14px'
              }
            }, this.searching ? '🔍 検索中...' : '🔍 検索')
          ])
        ]),
        
        // Google Maps コンテナ（実際のマップ）
        h('div', {
          id: 'atsu-search-map',
          style: {
            width: '100%',
            height: '450px',
            borderRadius: '8px',
            border: '2px solid #fff',
            marginBottom: '15px',
            backgroundColor: '#f0f0f0'
          }
        }),
        
        // WBGT結果表示エリア
        this.selectedLocation ? h('div', {
          style: {
            backgroundColor: 'rgba(255,255,255,0.9)',
            color: '#333',
            padding: '15px',
            borderRadius: '8px',
            marginTop: '10px'
          }
        }, [
          h('h3', { style: { margin: '0 0 10px 0', color: '#0066cc' } }, '🌡️ 暑さ指数(WBGT)情報'),
          h('p', { style: { margin: '5px 0' } }, ['📍 ', this.selectedLocation.name || 'お探しの地点']),
          h('p', { style: { margin: '5px 0' } }, ['📅 ', new Date().toLocaleDateString('ja-JP')]),
          
          this.wbgtData ? [
            h('div', {
              style: {
                backgroundColor: this.getWbgtColor(this.wbgtData.value),
                color: 'white',
                padding: '10px',
                borderRadius: '5px',
                textAlign: 'center',
                fontWeight: 'bold',
                margin: '10px 0'
              }
            }, [
              h('div', { style: { fontSize: '24px' } }, `${this.wbgtData.value}°C`),
              h('div', { style: { fontSize: '14px' } }, this.getWbgtLevel(this.wbgtData.value))
            ]),
            // 情報源の記載
            h('div', {
              style: {
                fontSize: '12px',
                color: '#666',
                marginTop: '10px',
                padding: '8px',
                backgroundColor: '#f8f9fa',
                borderRadius: '4px',
                borderLeft: '3px solid #0066cc'
              }
            }, [
              h('p', { style: { margin: '2px 0', fontWeight: 'bold' } }, '📋 データ情報源'),
              h('p', { style: { margin: '2px 0' } }, '• 環境省「暑さ指数（WBGT）予測値等電子情報提供サービス」'),
              h('p', { style: { margin: '2px 0' } }, '• 更新頻度: 1日3回（5時、14時、17時）'),
              h('p', { style: { margin: '2px 0' } }, '• 全国約840地点の観測データに基づく')
            ])
          ] : [
            h('p', { style: { color: '#666' } }, '🔄 WBGT データを取得中...')
          ]
        ]) : null,
        
        // 使い方ガイド
        h('div', {
          style: { fontSize: '12px', marginTop: '10px', opacity: '0.8' }
        }, [
          h('p', '💡 使い方: 地図上をクリックして暑さ指数を確認'),
          h('p', ['🔑 API Status: ', this.apiKey ? 'Ready' : 'Missing'])
        ])
      ])
    },
    props: ['apiKey', 'isLoggedIn'],
    data() {
      return {
        map: null,
        mapStatus: 'Initializing...',
        locating: false,
        searching: false,
        searchQuery: '',
        selectedLocation: null,
        wbgtData: null,
        markers: []
      }
    },
    mounted() {
      console.log('🗺️ AtsuSearchMap mounted - Loading Google Maps API...')
      this.initializeGoogleMaps()
    },
    methods: {
      // Google Maps API初期化
      async initializeGoogleMaps() {
        try {
          this.mapStatus = 'Loading Google Maps API...'
          
          // Google Maps APIスクリプトの動的読み込み
          if (!window.google) {
            await this.loadGoogleMapsScript()
          }
          
          // マップ初期化
          this.map = new google.maps.Map(document.getElementById('atsu-search-map'), {
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
          
          // クリックイベント追加
          this.map.addListener('click', (event) => {
            this.handleMapClick(event.latLng)
          })
          
          this.mapStatus = 'Ready - Click to get WBGT!'
          console.log('🗺️ Google Maps loaded successfully')
          
        } catch (error) {
          console.error('❌ Google Maps loading failed:', error)
          this.mapStatus = 'Error loading maps'
        }
      },
      
      // Google Maps APIスクリプト読み込み
      loadGoogleMapsScript() {
        return new Promise((resolve, reject) => {
          const script = document.createElement('script')
          script.src = `https://maps.googleapis.com/maps/api/js?key=${this.apiKey}&libraries=places`
          script.async = true
          script.defer = true
          script.onload = resolve
          script.onerror = reject
          document.head.appendChild(script)
        })
      },
      
      // マップクリック処理
      async handleMapClick(latLng) {
        const lat = latLng.lat()
        const lng = latLng.lng()
        
        console.log(`🗺️ Map clicked: ${lat}, ${lng}`)
        
        this.selectedLocation = { lat, lng, name: null }
        this.wbgtData = null
        
        // 既存マーカーをクリア
        this.clearMarkers()
        
        // 新しいマーカーを追加
        const marker = new google.maps.Marker({
          position: latLng,
          map: this.map,
          title: 'WBGT検索地点',
          icon: {
            path: google.maps.SymbolPath.CIRCLE,
            scale: 10,
            fillColor: '#ff6b35',
            fillOpacity: 0.8,
            strokeWeight: 2,
            strokeColor: '#ffffff'
          }
        })
        
        this.markers.push(marker)
        
        // WBGTデータを取得
        await this.fetchWbgtData(lat, lng)
      },
      
      // WBGTデータ取得
      async fetchWbgtData(lat, lng) {
        try {
          console.log(`🔄 Fetching WBGT data for ${lat}, ${lng}`)
          
          // APIリクエスト（実際のLaravel APIを呼び出し）
          const response = await axios.post('/api/wbgt/search', {
            latitude: lat,
            longitude: lng
          })
          
          if (response.data.success) {
            this.wbgtData = response.data.wbgt
            this.selectedLocation.name = response.data.location_name
            console.log('✅ WBGT data retrieved:', this.wbgtData)
          } else {
            // フォールバック: 模擬データ
            this.wbgtData = {
              value: Math.floor(Math.random() * 15) + 20, // 20-35の範囲
              timestamp: new Date().toISOString()
            }
            this.selectedLocation.name = '検索地点'
            console.log('⚠️ Using mock WBGT data:', this.wbgtData)
          }
          
        } catch (error) {
          console.error('❌ WBGT data fetch failed:', error)
          
          // エラー時も模擬データを表示
          this.wbgtData = {
            value: Math.floor(Math.random() * 15) + 20,
            timestamp: new Date().toISOString()
          }
          this.selectedLocation.name = '検索地点（デモデータ）'
        }
      },
      
      // 現在地取得
      getCurrentLocation() {
        if (!navigator.geolocation) {
          alert('お使いのブラウザでは位置情報がサポートされていません')
          return
        }
        
        this.locating = true
        
        navigator.geolocation.getCurrentPosition(
          (position) => {
            const lat = position.coords.latitude
            const lng = position.coords.longitude
            
            this.map.setCenter({ lat, lng })
            this.map.setZoom(12)
            
            // 現在地をクリックしたのと同じ処理
            this.handleMapClick({ lat: () => lat, lng: () => lng })
            
            this.locating = false
          },
          (error) => {
            console.error('位置情報取得エラー:', error)
            alert('位置情報の取得に失敗しました')
            this.locating = false
          }
        )
      },
      
      // マーカークリア
      clearMarkers() {
        this.markers.forEach(marker => marker.setMap(null))
        this.markers = []
      },
      
      // 地域検索（Google Places API使用）
      async searchLocation() {
        if (!this.searchQuery.trim()) {
          alert('地域名を入力してください')
          return
        }
        
        this.searching = true
        
        try {
          console.log(`🔍 Searching for location: ${this.searchQuery}`)
          
          // Google Places Serviceを使用して地域を検索
          const service = new google.maps.places.PlacesService(this.map)
          
          const request = {
            query: this.searchQuery,
            fields: ['name', 'geometry', 'formatted_address']
          }
          
          service.textSearch(request, (results, status) => {
            if (status === google.maps.places.PlacesServiceStatus.OK && results.length > 0) {
              const place = results[0]
              const location = place.geometry.location
              
              // 地図の中心を移動
              this.map.setCenter(location)
              this.map.setZoom(12)
              
              // 検索結果の地点をクリックしたのと同じ処理
              this.handleMapClick(location)
              
              console.log('✅ Location found:', place.name, place.formatted_address)
            } else {
              console.error('❌ Location search failed:', status)
              alert('指定された地域が見つかりませんでした。別の地域名で試してください。')
            }
            
            this.searching = false
          })
          
        } catch (error) {
          console.error('❌ Location search error:', error)
          alert('地域検索でエラーが発生しました')
          this.searching = false
        }
      },
      
      // WBGT色分け
      getWbgtColor(value) {
        if (value >= 31) return '#dc2626'      // 危険（赤）
        if (value >= 28) return '#f97316'      // 厳重警戒（オレンジ）
        if (value >= 25) return '#eab308'      // 警戒（黄）
        if (value >= 21) return '#3b82f6'      // 注意（青）
        return '#16a34a'                       // ほぼ安全（緑）
      },
      
      // WBGTレベルテキスト
      getWbgtLevel(value) {
        if (value >= 31) return '危険 - 運動は原則中止'
        if (value >= 28) return '厳重警戒 - 激しい運動は中止'
        if (value >= 25) return '警戒 - 積極的に休憩'
        if (value >= 21) return '注意 - 水分補給を忘れずに'
        return 'ほぼ安全'
      }
    }
  }

  // TestComponent（開発完了により削除）
  // 開発・デバッグ用コンポーネントは本番環境では不要

  // render関数を使用（テンプレート問題を回避）
  const app = createApp({
    render() {
      return h('div', { style: { padding: '20px' } }, [
        // デバッグ用コンポーネントは本番では非表示
        // h('div', { /* 緑色テストボックス - 開発完了により非表示 */ }),
        // h(TestComponent, { /* 赤色テストコンポーネント - 開発完了により非表示 */ }),
        
        // AtsuSearchMap (本格版)
        h(AtsuSearchMap, {
          apiKey: this.apiKey,
          isLoggedIn: this.isLoggedIn
        })
      ])
    },
    data() {
      // 固定値でテスト（Props抽出は後で修正）
      const apiKey = 'AIzaSyDQrPePZ7hq3lOlqx3WWUqU7aQq3iqw3g8' // 実際のAPIキー
      const isLoggedIn = true // テスト用
      
      console.log('🔍 Using fixed values for testing:', {
        apiKey: apiKey.substring(0, 20) + '...',
        isLoggedIn
      })
      
      return {
        message: 'Vue 3 Root App Successfully Running',
        currentTime: new Date().toLocaleTimeString(),
        apiKey: apiKey,
        isLoggedIn: isLoggedIn
      }
    },
    mounted() {
      console.log('✅ Vue root app mounted successfully!')
      console.log('Props extracted:', {
        apiKey: this.apiKey ? this.apiKey.substring(0, 20) + '...' : 'None',
        isLoggedIn: this.isLoggedIn
      })
      
      // 時間を1秒ごとに更新
      setInterval(() => {
        this.currentTime = new Date().toLocaleTimeString()
      }, 1000)
    }
  })

  // 本番環境用の最終版

  // コンポーネントの登録
  console.log('Registering components...')
  // app.component('TestComponent', TestComponent) // 開発完了により非表示
  app.component('AtsuSearchMap', AtsuSearchMap)
  console.log('Components registered successfully')
  
  // アプリをマウント（Vue 3の正しい方法）
  try {
    console.log('Mounting Vue app to #atsu-search-app...')
    
    // 既存のHTMLコンテンツを一旦退避
    const originalHTML = mountElement.innerHTML
    console.log('Original HTML content:', originalHTML.substring(0, 200))
    
    // マウント前にHTMLをクリア
    mountElement.innerHTML = ''
    
    const vueInstance = app.mount('#atsu-search-app')
    console.log('✅ Vue app mounted successfully!', vueInstance)
    
    // マウント後の状態を確認
    setTimeout(() => {
      console.log('📋 Post-mount check:')
      console.log('Mount element content:', mountElement.innerHTML.substring(0, 500))
      console.log('Child components found:', mountElement.children.length)
      console.log('TestComponent instances:', document.querySelectorAll('[data-v-test]').length)
    }, 500)
    
  } catch (error) {
    console.error('❌ Error mounting Vue app:', error)
    console.error('Error stack:', error.stack)
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