<?php

namespace Database\Seeders;

use App\Models\WbgtStation;
use Illuminate\Database\Seeder;

/**
 * 環境省 WBGT 提供地点の初期データ投入。
 *
 * 現時点では 47 都道府県代表地点を登録する。
 * 全 841 地点は wbgt_stations テーブルの整備後に別シーダーで追加予定。
 *
 * 実行:
 *   sail artisan db:seed --class=WbgtStationSeeder
 */
class WbgtStationSeeder extends Seeder
{
    /** @var array<int, array{station_code: string, name: string, prefecture_code: string, latitude: float, longitude: float}> */
    private const STATIONS = [
        // 北海道・東北
        ['station_code' => '47412', 'name' => '札幌',   'prefecture_code' => '01', 'latitude' => 43.0642, 'longitude' => 141.3469],
        ['station_code' => '47575', 'name' => '青森',   'prefecture_code' => '02', 'latitude' => 40.8236, 'longitude' => 140.7403],
        ['station_code' => '47581', 'name' => '盛岡',   'prefecture_code' => '03', 'latitude' => 39.7036, 'longitude' => 141.1528],
        ['station_code' => '47590', 'name' => '仙台',   'prefecture_code' => '04', 'latitude' => 38.2682, 'longitude' => 140.8719],
        ['station_code' => '47582', 'name' => '秋田',   'prefecture_code' => '05', 'latitude' => 39.7186, 'longitude' => 140.1022],
        ['station_code' => '47588', 'name' => '山形',   'prefecture_code' => '06', 'latitude' => 38.2528, 'longitude' => 140.3394],
        ['station_code' => '47595', 'name' => '福島',   'prefecture_code' => '07', 'latitude' => 37.7503, 'longitude' => 140.4681],
        // 関東
        ['station_code' => '47629', 'name' => '水戸',   'prefecture_code' => '08', 'latitude' => 36.3417, 'longitude' => 140.4469],
        ['station_code' => '47615', 'name' => '宇都宮', 'prefecture_code' => '09', 'latitude' => 36.5658, 'longitude' => 139.8833],
        ['station_code' => '47624', 'name' => '前橋',   'prefecture_code' => '10', 'latitude' => 36.3914, 'longitude' => 139.0606],
        ['station_code' => '47626', 'name' => '熊谷',   'prefecture_code' => '11', 'latitude' => 36.1478, 'longitude' => 139.3892],
        ['station_code' => '47648', 'name' => '銚子',   'prefecture_code' => '12', 'latitude' => 35.7353, 'longitude' => 140.8472],
        ['station_code' => '47662', 'name' => '東京',   'prefecture_code' => '13', 'latitude' => 35.6814, 'longitude' => 139.7671],
        ['station_code' => '47670', 'name' => '横浜',   'prefecture_code' => '14', 'latitude' => 35.4442, 'longitude' => 139.6381],
        ['station_code' => '47604', 'name' => '新潟',   'prefecture_code' => '15', 'latitude' => 37.9161, 'longitude' => 139.0364],
        // 中部
        ['station_code' => '47607', 'name' => '富山',   'prefecture_code' => '16', 'latitude' => 36.6961, 'longitude' => 137.2125],
        ['station_code' => '47605', 'name' => '金沢',   'prefecture_code' => '17', 'latitude' => 36.5950, 'longitude' => 136.6256],
        ['station_code' => '47616', 'name' => '福井',   'prefecture_code' => '18', 'latitude' => 36.0656, 'longitude' => 136.2219],
        ['station_code' => '47638', 'name' => '甲府',   'prefecture_code' => '19', 'latitude' => 35.6636, 'longitude' => 138.5686],
        ['station_code' => '47610', 'name' => '長野',   'prefecture_code' => '20', 'latitude' => 36.6514, 'longitude' => 138.1814],
        ['station_code' => '47632', 'name' => '岐阜',   'prefecture_code' => '21', 'latitude' => 35.3914, 'longitude' => 136.7219],
        ['station_code' => '47656', 'name' => '静岡',   'prefecture_code' => '22', 'latitude' => 34.9764, 'longitude' => 138.3831],
        ['station_code' => '47636', 'name' => '名古屋', 'prefecture_code' => '23', 'latitude' => 35.1814, 'longitude' => 136.9069],
        ['station_code' => '47651', 'name' => '津',     'prefecture_code' => '24', 'latitude' => 34.7303, 'longitude' => 136.5083],
        // 関西
        ['station_code' => '47761', 'name' => '彦根',   'prefecture_code' => '25', 'latitude' => 35.2758, 'longitude' => 136.2514],
        ['station_code' => '47759', 'name' => '京都',   'prefecture_code' => '26', 'latitude' => 35.0117, 'longitude' => 135.7681],
        ['station_code' => '47772', 'name' => '大阪',   'prefecture_code' => '27', 'latitude' => 34.6858, 'longitude' => 135.5197],
        ['station_code' => '47770', 'name' => '神戸',   'prefecture_code' => '28', 'latitude' => 34.6914, 'longitude' => 135.1833],
        ['station_code' => '47780', 'name' => '奈良',   'prefecture_code' => '29', 'latitude' => 34.6853, 'longitude' => 135.8050],
        ['station_code' => '47777', 'name' => '和歌山', 'prefecture_code' => '30', 'latitude' => 34.2256, 'longitude' => 135.1675],
        // 中国・四国
        ['station_code' => '47746', 'name' => '鳥取',   'prefecture_code' => '31', 'latitude' => 35.5039, 'longitude' => 134.2381],
        ['station_code' => '47741', 'name' => '松江',   'prefecture_code' => '32', 'latitude' => 35.4725, 'longitude' => 133.0508],
        ['station_code' => '47768', 'name' => '岡山',   'prefecture_code' => '33', 'latitude' => 34.6617, 'longitude' => 133.9350],
        ['station_code' => '47765', 'name' => '広島',   'prefecture_code' => '34', 'latitude' => 34.3972, 'longitude' => 132.4597],
        ['station_code' => '47750', 'name' => '下関',   'prefecture_code' => '35', 'latitude' => 33.9514, 'longitude' => 130.9256],
        ['station_code' => '47895', 'name' => '徳島',   'prefecture_code' => '36', 'latitude' => 34.0658, 'longitude' => 134.5592],
        ['station_code' => '47891', 'name' => '高松',   'prefecture_code' => '37', 'latitude' => 34.3403, 'longitude' => 134.0431],
        ['station_code' => '47887', 'name' => '松山',   'prefecture_code' => '38', 'latitude' => 33.8422, 'longitude' => 132.7658],
        ['station_code' => '47893', 'name' => '高知',   'prefecture_code' => '39', 'latitude' => 33.5597, 'longitude' => 133.5314],
        // 九州・沖縄
        ['station_code' => '47807', 'name' => '福岡',   'prefecture_code' => '40', 'latitude' => 33.6064, 'longitude' => 130.4183],
        ['station_code' => '47813', 'name' => '佐賀',   'prefecture_code' => '41', 'latitude' => 33.2631, 'longitude' => 130.2997],
        ['station_code' => '47817', 'name' => '長崎',   'prefecture_code' => '42', 'latitude' => 32.7453, 'longitude' => 129.8744],
        ['station_code' => '47819', 'name' => '熊本',   'prefecture_code' => '43', 'latitude' => 32.7897, 'longitude' => 130.7419],
        ['station_code' => '47815', 'name' => '大分',   'prefecture_code' => '44', 'latitude' => 33.2378, 'longitude' => 131.6131],
        ['station_code' => '47830', 'name' => '宮崎',   'prefecture_code' => '45', 'latitude' => 31.9111, 'longitude' => 131.4244],
        ['station_code' => '47827', 'name' => '鹿児島', 'prefecture_code' => '46', 'latitude' => 31.5969, 'longitude' => 130.5572],
        ['station_code' => '47936', 'name' => '那覇',   'prefecture_code' => '47', 'latitude' => 26.2128, 'longitude' => 127.6792],
    ];

    public function run(): void
    {
        $now = now();

        foreach (self::STATIONS as $station) {
            WbgtStation::updateOrCreate(
                ['station_code' => $station['station_code']],
                array_merge($station, [
                    'is_active'  => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
            );
        }

        $this->command->info('WbgtStationSeeder: ' . count(self::STATIONS) . ' 地点を登録しました');
    }
}
