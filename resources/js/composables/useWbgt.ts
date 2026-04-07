import { ref, readonly } from 'vue'
import axios from 'axios'
import type { WbgtApiResponse, LocationWithWbgt } from '../types/index'

/**
 * WBGTデータのフェッチ・管理を担うcomposable。
 *
 * 使い方:
 *   const { wbgtData, fetchWbgt, loading, error } = useWbgt()
 */
export function useWbgt() {
  const wbgtData = ref<WbgtApiResponse | null>(null)
  const loading  = ref(false)
  const error    = ref<string | null>(null)

  /**
   * 指定locationIdのWBGTデータをAPIから取得する。
   */
  const fetchWbgt = async (
    locationId: number,
    options: { date?: string; type?: 'forecast' | 'actual' } = {}
  ): Promise<WbgtApiResponse | null> => {
    loading.value = true
    error.value   = null

    try {
      const response = await axios.get<WbgtApiResponse>(`/api/wbgt/${locationId}`, {
        params: {
          date: options.date,
          type: options.type ?? 'forecast',
        },
      })

      wbgtData.value = response.data
      return response.data
    } catch (err: unknown) {
      error.value = axios.isAxiosError(err)
        ? (err.response?.data?.message ?? err.message)
        : '予期せぬエラーが発生しました'
      return null
    } finally {
      loading.value = false
    }
  }

  /**
   * LocationWithWbgt オブジェクトに現在時刻のWBGT値を設定して返す。
   * AtsuSearchMap.vue 等でのポストプロセスに使用。
   */
  const resolveCurrentWbgt = (data: WbgtApiResponse): number | null => {
    if (!data.wbgt_data.length) return null

    const currentHour = new Date().getHours()
    const record = data.wbgt_data.find(d => d.hour <= currentHour) ?? data.wbgt_data[0]
    return record ? record.wbgt_value : null
  }

  return {
    wbgtData:           readonly(wbgtData),
    loading:            readonly(loading),
    error:              readonly(error),
    fetchWbgt,
    resolveCurrentWbgt,
  }
}
