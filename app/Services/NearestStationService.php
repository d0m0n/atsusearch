<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * 緯度経度から最寄りのWBGT観測地点を特定するサービス。
 *
 * 観測地点データは wbgt_stations テーブルが存在すればそちらを優先し、
 * テーブルがない場合は組み込みの47都道府県代表地点データにフォールバックする。
 */
class NearestStationService
{
    /**
     * 47都道府県代表WBGT観測地点（環境省 2025年度提供地点）。
     * wbgt_stations テーブルが整備されるまでのフォールバック用。
     */
    private const FALLBACK_STATIONS = [
        // 北海道・東北
        ['id' => '47412', 'name' => '札幌',   'lat' => 43.064, 'lon' => 141.347, 'prefecture_code' => '01'],
        ['id' => '47575', 'name' => '青森',   'lat' => 40.824, 'lon' => 140.740, 'prefecture_code' => '02'],
        ['id' => '47581', 'name' => '盛岡',   'lat' => 39.704, 'lon' => 141.153, 'prefecture_code' => '03'],
        ['id' => '47590', 'name' => '仙台',   'lat' => 38.268, 'lon' => 140.872, 'prefecture_code' => '04'],
        ['id' => '47582', 'name' => '秋田',   'lat' => 39.719, 'lon' => 140.102, 'prefecture_code' => '05'],
        ['id' => '47588', 'name' => '山形',   'lat' => 38.253, 'lon' => 140.339, 'prefecture_code' => '06'],
        ['id' => '47595', 'name' => '福島',   'lat' => 37.750, 'lon' => 140.468, 'prefecture_code' => '07'],
        // 関東
        ['id' => '47629', 'name' => '水戸',   'lat' => 36.342, 'lon' => 140.447, 'prefecture_code' => '08'],
        ['id' => '47615', 'name' => '宇都宮', 'lat' => 36.566, 'lon' => 139.883, 'prefecture_code' => '09'],
        ['id' => '47624', 'name' => '前橋',   'lat' => 36.391, 'lon' => 139.061, 'prefecture_code' => '10'],
        ['id' => '47626', 'name' => '熊谷',   'lat' => 36.148, 'lon' => 139.389, 'prefecture_code' => '11'],
        ['id' => '47648', 'name' => '銚子',   'lat' => 35.735, 'lon' => 140.847, 'prefecture_code' => '12'],
        ['id' => '47662', 'name' => '東京',   'lat' => 35.681, 'lon' => 139.767, 'prefecture_code' => '13'],
        ['id' => '47670', 'name' => '横浜',   'lat' => 35.444, 'lon' => 139.638, 'prefecture_code' => '14'],
        ['id' => '47604', 'name' => '新潟',   'lat' => 37.916, 'lon' => 139.036, 'prefecture_code' => '15'],
        // 中部
        ['id' => '47607', 'name' => '富山',   'lat' => 36.696, 'lon' => 137.213, 'prefecture_code' => '16'],
        ['id' => '47605', 'name' => '金沢',   'lat' => 36.595, 'lon' => 136.626, 'prefecture_code' => '17'],
        ['id' => '47616', 'name' => '福井',   'lat' => 36.065, 'lon' => 136.222, 'prefecture_code' => '18'],
        ['id' => '47638', 'name' => '甲府',   'lat' => 35.664, 'lon' => 138.569, 'prefecture_code' => '19'],
        ['id' => '47610', 'name' => '長野',   'lat' => 36.651, 'lon' => 138.181, 'prefecture_code' => '20'],
        ['id' => '47632', 'name' => '岐阜',   'lat' => 35.391, 'lon' => 136.722, 'prefecture_code' => '21'],
        ['id' => '47656', 'name' => '静岡',   'lat' => 34.976, 'lon' => 138.383, 'prefecture_code' => '22'],
        ['id' => '47636', 'name' => '名古屋', 'lat' => 35.181, 'lon' => 136.907, 'prefecture_code' => '23'],
        ['id' => '47651', 'name' => '津',     'lat' => 34.730, 'lon' => 136.508, 'prefecture_code' => '24'],
        // 関西
        ['id' => '47761', 'name' => '彦根',   'lat' => 35.276, 'lon' => 136.251, 'prefecture_code' => '25'],
        ['id' => '47759', 'name' => '京都',   'lat' => 35.012, 'lon' => 135.768, 'prefecture_code' => '26'],
        ['id' => '47772', 'name' => '大阪',   'lat' => 34.686, 'lon' => 135.520, 'prefecture_code' => '27'],
        ['id' => '47770', 'name' => '神戸',   'lat' => 34.691, 'lon' => 135.183, 'prefecture_code' => '28'],
        ['id' => '47780', 'name' => '奈良',   'lat' => 34.685, 'lon' => 135.805, 'prefecture_code' => '29'],
        ['id' => '47777', 'name' => '和歌山', 'lat' => 34.226, 'lon' => 135.167, 'prefecture_code' => '30'],
        // 中国・四国
        ['id' => '47746', 'name' => '鳥取',   'lat' => 35.504, 'lon' => 134.238, 'prefecture_code' => '31'],
        ['id' => '47741', 'name' => '松江',   'lat' => 35.472, 'lon' => 133.051, 'prefecture_code' => '32'],
        ['id' => '47768', 'name' => '岡山',   'lat' => 34.662, 'lon' => 133.935, 'prefecture_code' => '33'],
        ['id' => '47765', 'name' => '広島',   'lat' => 34.397, 'lon' => 132.460, 'prefecture_code' => '34'],
        ['id' => '47750', 'name' => '下関',   'lat' => 33.951, 'lon' => 130.925, 'prefecture_code' => '35'],
        ['id' => '47895', 'name' => '徳島',   'lat' => 34.066, 'lon' => 134.559, 'prefecture_code' => '36'],
        ['id' => '47891', 'name' => '高松',   'lat' => 34.340, 'lon' => 134.043, 'prefecture_code' => '37'],
        ['id' => '47887', 'name' => '松山',   'lat' => 33.842, 'lon' => 132.766, 'prefecture_code' => '38'],
        ['id' => '47893', 'name' => '高知',   'lat' => 33.560, 'lon' => 133.531, 'prefecture_code' => '39'],
        // 九州・沖縄
        ['id' => '47807', 'name' => '福岡',   'lat' => 33.606, 'lon' => 130.418, 'prefecture_code' => '40'],
        ['id' => '47813', 'name' => '佐賀',   'lat' => 33.263, 'lon' => 130.300, 'prefecture_code' => '41'],
        ['id' => '47817', 'name' => '長崎',   'lat' => 32.745, 'lon' => 129.874, 'prefecture_code' => '42'],
        ['id' => '47819', 'name' => '熊本',   'lat' => 32.790, 'lon' => 130.742, 'prefecture_code' => '43'],
        ['id' => '47815', 'name' => '大分',   'lat' => 33.238, 'lon' => 131.613, 'prefecture_code' => '44'],
        ['id' => '47830', 'name' => '宮崎',   'lat' => 31.911, 'lon' => 131.424, 'prefecture_code' => '45'],
        ['id' => '47827', 'name' => '鹿児島', 'lat' => 31.597, 'lon' => 130.557, 'prefecture_code' => '46'],
        ['id' => '47936', 'name' => '那覇',   'lat' => 26.213, 'lon' => 127.679, 'prefecture_code' => '47'],
    ];

    /**
     * 指定座標から最寄りの観測地点を返す。
     *
     * @return array{id: string, name: string, distance: float, prefecture_code: string}|null
     */
    public function findNearest(float $latitude, float $longitude): ?array
    {
        $stations = $this->loadStations();

        $nearest     = null;
        $minDistance = PHP_FLOAT_MAX;

        foreach ($stations as $station) {
            $distance = $this->haversine($latitude, $longitude, $station['lat'], $station['lon']);
            if ($distance < $minDistance) {
                $minDistance = $distance;
                $nearest     = $station;
            }
        }

        if ($nearest === null) {
            return null;
        }

        return [
            'id'              => $nearest['id'],
            'name'            => $nearest['name'],
            'distance'        => round($minDistance, 2),
            'prefecture_code' => $nearest['prefecture_code'],
        ];
    }

    /**
     * 全観測地点を返す（WbgtDataService のCSVフェッチで使用）。
     *
     * @return array<int, array{id: string, name: string, lat: float, lon: float, prefecture_code: string}>
     */
    public function getAllStations(): array
    {
        return $this->loadStations();
    }

    /**
     * 地点IDで観測地点を返す。見つからない場合は null。
     *
     * @return array{id: string, name: string, lat: float, lon: float, prefecture_code: string}|null
     */
    public function findById(string $stationId): ?array
    {
        foreach ($this->loadStations() as $station) {
            if ($station['id'] === $stationId) {
                return $station;
            }
        }
        return null;
    }

    /**
     * 観測地点リストを取得。
     * wbgt_stations テーブルが存在すればDBから、なければ定数フォールバックを使用。
     *
     * @return array<int, array{id: string, name: string, lat: float, lon: float, prefecture_code: string}>
     */
    private function loadStations(): array
    {
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('wbgt_stations')) {
                $stations = \App\Models\WbgtStation::select('station_code as id', 'name', 'latitude as lat', 'longitude as lon', 'prefecture_code')
                    ->get()
                    ->map(fn ($s) => $s->toArray())
                    ->toArray();

                if (count($stations) > 0) {
                    return $stations;
                }
            }
        } catch (\Exception $e) {
            Log::warning('NearestStationService: wbgt_stations table not available, using fallback data.');
        }

        return self::FALLBACK_STATIONS;
    }

    /**
     * Haversine公式で2点間の距離をkmで返す。
     */
    private function haversine(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $R = 6371.0;

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
