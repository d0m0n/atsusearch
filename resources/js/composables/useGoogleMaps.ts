import { ref, readonly } from 'vue'

/**
 * Google Maps JavaScript API の初期化を管理するcomposable。
 *
 * 使い方:
 *   const { map, loaded, error, initMap } = useGoogleMaps(apiKey)
 *
 * - スクリプトの二重読み込みを防止する
 * - window.google が既にある場合は即時初期化する
 */
export function useGoogleMaps(apiKey: string) {
  const map    = ref<google.maps.Map | null>(null)
  const loaded = ref(false)
  const error  = ref<string | null>(null)

  /** Google Maps API スクリプトを動的にロードする */
  const loadScript = (): Promise<void> => {
    if (window.google?.maps) {
      return Promise.resolve()
    }

    // 既にscriptタグが挿入されていればロード完了を待つ
    const existing = document.querySelector<HTMLScriptElement>(
      'script[src*="maps.googleapis.com/maps/api/js"]'
    )
    if (existing) {
      return new Promise((resolve) => {
        existing.addEventListener('load', () => resolve())
      })
    }

    return new Promise((resolve, reject) => {
      const script = document.createElement('script')
      script.src   = `https://maps.googleapis.com/maps/api/js?key=${apiKey}&libraries=places`
      script.async = true
      script.defer = true
      script.onload  = () => resolve()
      script.onerror = () => reject(new Error('Google Maps APIの読み込みに失敗しました'))
      document.head.appendChild(script)
    })
  }

  /**
   * 指定のDOM要素IDにマップを初期化する。
   *
   * @param elementId  マップを描画するHTMLのid属性値
   * @param options    google.maps.MapOptions (省略時はデフォルト設定)
   */
  const initMap = async (
    elementId: string,
    options: Partial<google.maps.MapOptions> = {}
  ): Promise<google.maps.Map | null> => {
    error.value = null

    try {
      await loadScript()

      // 少し待機してAPIの内部初期化を確実にする
      await new Promise(resolve => setTimeout(resolve, 50))

      const mapElement = document.getElementById(elementId)
      if (!mapElement) {
        throw new Error(`マップ要素 #${elementId} が見つかりません`)
      }

      const defaultOptions: google.maps.MapOptions = {
        center: { lat: 35.6812, lng: 139.7671 }, // 東京駅
        zoom:   10,
        styles: [
          { featureType: 'poi', elementType: 'labels', stylers: [{ visibility: 'off' }] },
        ],
      }

      map.value = new window.google.maps.Map(mapElement, { ...defaultOptions, ...options })
      loaded.value = true

      return map.value
    } catch (err: unknown) {
      error.value = err instanceof Error ? err.message : 'マップの初期化に失敗しました'
      return null
    }
  }

  return {
    map:     readonly(map),
    loaded:  readonly(loaded),
    error:   readonly(error),
    initMap,
  }
}
