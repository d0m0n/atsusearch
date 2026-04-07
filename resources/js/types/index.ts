/**
 * AtsuSearch — 共通TypeScript型定義
 * バックエンドのAPIレスポンスと対応させること。
 */

// ==============================
// WBGT 危険度レベル
// ==============================

export type WbgtLevel = 'danger' | 'severe_warning' | 'warning' | 'caution' | 'safe'

export interface WbgtLevelInfo {
  level:   WbgtLevel
  label:   string
  advice:  string
  color:   string       // 背景色（DADS準拠）
  cssClass: string      // Tailwindクラス
}

/** WBGT値からレベル情報を返す */
export const getWbgtLevelInfo = (wbgt: number | null): WbgtLevelInfo => {
  if (wbgt === null || wbgt === undefined) {
    return { level: 'safe', label: 'データなし', advice: '', color: '#D9D9DB', cssClass: 'bg-gray-300' }
  }
  if (wbgt >= 31) return { level: 'danger',         label: '危険',     advice: '運動は原則中止。外出をなるべく避ける',           color: '#D32F2F', cssClass: 'wbgt-danger' }
  if (wbgt >= 28) return { level: 'severe_warning', label: '厳重警戒', advice: '激しい運動は中止。10〜20分おきに休憩',           color: '#F57C00', cssClass: 'wbgt-severe-warning' }
  if (wbgt >= 25) return { level: 'warning',        label: '警戒',     advice: '積極的に休憩。水分・塩分補給',                   color: '#FBC02D', cssClass: 'wbgt-warning' }
  if (wbgt >= 21) return { level: 'caution',        label: '注意',     advice: '水分補給を忘れずに',                             color: '#0288D1', cssClass: 'wbgt-caution' }
  return             { level: 'safe',           label: 'ほぼ安全', advice: '適宜水分補給',                                   color: '#388E3C', cssClass: 'wbgt-safe' }
}

// ==============================
// WBGT 観測データ
// ==============================

export interface WbgtDataRecord {
  id:          number
  location_id: number
  date:        string      // "YYYY-MM-DD"
  hour:        number      // 0〜23
  wbgt_value:  number
  data_type:   'forecast' | 'actual'
  data_source?: 'csv' | 'sample' | 'official_site'
  fetch_time?:  string     // ISO 8601
}

// ==============================
// 観測地点（WbgtStation）
// ==============================

export interface WbgtStation {
  id:              string
  name:            string
  distance:        number   // km
  prefecture_code: string
}

// ==============================
// 地点（Location）
// ==============================

export interface Location {
  id:          number
  name:        string
  address:     string
  latitude:    number
  longitude:   number
  is_favorite: boolean
}

/** マップで表示する地点（WBGTデータ付き） */
export interface LocationWithWbgt extends Location {
  current_wbgt:    number | null
  wbgt_station?:   WbgtStation
  temperature_data?: TemperatureData
  nearest_station?:  AmeDasStation
}

// ==============================
// 気温データ
// ==============================

export interface TemperatureData {
  temperature: number
  observation_time?: string
}

export interface AmeDasStation {
  name:      string
  distance?: number
}

// ==============================
// 熱中症警戒アラート
// ==============================

export type AlertType = 'warning' | 'special_warning'

export interface HeatAlert {
  id:              number
  prefecture_code: string
  prefecture_name: string
  alert_type:      AlertType
  target_date:     string   // "YYYY-MM-DD"
  issued_at:       string   // ISO 8601
  is_active:       boolean
}

// ==============================
// APIレスポンス共通形式
// ==============================

export interface ApiResponse<T> {
  data:    T
  message?: string
}

export interface PaginatedResponse<T> {
  data:          T[]
  current_page:  number
  last_page:     number
  per_page:      number
  total:         number
}

/** GET /api/wbgt/{id} レスポンス */
export interface WbgtApiResponse {
  location:     Location
  date:         string
  type:         'forecast' | 'actual'
  wbgt_data:    WbgtDataRecord[]
  wbgt_station: WbgtStation | null
}

/** GET /api/alerts レスポンス */
export interface AlertsApiResponse {
  data: HeatAlert[]
}

/** POST /api/locations レスポンス */
export interface LocationCreateResponse {
  location: Location
  message:  string
}
