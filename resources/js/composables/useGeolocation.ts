import { ref, readonly } from 'vue'

export interface GeolocationState {
  latitude:  number | null
  longitude: number | null
  accuracy:  number | null
  error:     string | null
  loading:   boolean
}

/**
 * GPS位置情報を取得するcomposable。
 *
 * 使い方:
 *   const { state, getCurrentPosition } = useGeolocation()
 */
export function useGeolocation() {
  const state = ref<GeolocationState>({
    latitude:  null,
    longitude: null,
    accuracy:  null,
    error:     null,
    loading:   false,
  })

  const isSupported = 'geolocation' in navigator

  /**
   * 現在地を一度だけ取得する。
   * Promise形式なので await で使用可能。
   */
  const getCurrentPosition = (): Promise<GeolocationCoordinates> => {
    return new Promise((resolve, reject) => {
      if (!isSupported) {
        const msg = 'お使いのブラウザでは位置情報がサポートされていません'
        state.value.error = msg
        reject(new Error(msg))
        return
      }

      state.value.loading = true
      state.value.error   = null

      navigator.geolocation.getCurrentPosition(
        (position) => {
          state.value.latitude  = position.coords.latitude
          state.value.longitude = position.coords.longitude
          state.value.accuracy  = position.coords.accuracy
          state.value.loading   = false
          resolve(position.coords)
        },
        (err) => {
          const messages: Record<number, string> = {
            1: '位置情報へのアクセスが拒否されました',
            2: '位置情報を取得できませんでした',
            3: '位置情報の取得がタイムアウトしました',
          }
          const msg = messages[err.code] ?? '位置情報の取得に失敗しました'
          state.value.error   = msg
          state.value.loading = false
          reject(new Error(msg))
        },
        {
          enableHighAccuracy: true,
          timeout:            10000,
          maximumAge:         30000,
        }
      )
    })
  }

  return {
    state:              readonly(state),
    isSupported,
    getCurrentPosition,
  }
}
