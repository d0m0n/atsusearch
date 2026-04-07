<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * 環境省WBGT CSV形式の解析サービス。
 *
 * 【予測値CSV】 yohou_all.csv / yohou_{地点番号}.csv
 *   - 1行目: 空,空,予測対象時刻(YYYYMMDDHH)...
 *   - 2行目〜: 地点番号,作成時刻,予測値(×10)...
 *   - 予測値は10で割って使用（例: 310 → 31.0℃）
 *
 * 【実況値CSV】 wbgt_{地点番号}_{YYYYMM}.csv
 *   - 1行目: Date,Time,地点番号...
 *   - 2行目〜: 日付,時刻,WBGT値...
 *   - 月単位ファイル
 *
 * 文字コード: ASCII（半角英数記号のみ）／区切り: カンマ／改行: LF
 */
class WbgtCsvParser
{
    /**
     * 予測値CSVを解析する。
     *
     * @param  string $csvContent CSV本文
     * @param  string $stationId  対象地点番号
     * @return array<int, array{datetime: Carbon, wbgt_value: float, data_type: string}>
     */
    public function parseForecastCsv(string $csvContent, string $stationId): array
    {
        $lines = $this->splitLines($csvContent);

        if (count($lines) < 2) {
            Log::warning("WbgtCsvParser: forecast CSV has too few lines for station {$stationId}");
            return [];
        }

        // 1行目: 時刻ヘッダー（1列目・2列目は空, 3列目以降が YYYYMMDDHH）
        $headerCells = str_getcsv($lines[0]);
        $datetimeHeaders = array_slice($headerCells, 2);

        $results = [];

        foreach (array_slice($lines, 1) as $lineNo => $line) {
            if (trim($line) === '') continue;

            $cells = str_getcsv($line);
            $rowStationId = $cells[0] ?? '';

            // 対象地点のみ処理
            if ($rowStationId !== $stationId) continue;

            $forecastValues = array_slice($cells, 2);

            foreach ($datetimeHeaders as $i => $dtStr) {
                $raw = $forecastValues[$i] ?? null;
                if ($raw === null || !is_numeric($raw)) continue;

                // 予測値は10倍で格納されているため10で割る
                $wbgt = (float)$raw / 10.0;

                $dt = $this->parseForecastDatetime($dtStr);
                if ($dt === null) continue;

                $results[] = [
                    'datetime'   => $dt,
                    'wbgt_value' => $wbgt,
                    'data_type'  => 'forecast',
                ];
            }
        }

        return $results;
    }

    /**
     * 実況値CSVを解析する。
     *
     * @param  string $csvContent CSV本文
     * @return array<int, array{datetime: Carbon, wbgt_value: float, data_type: string}>
     */
    public function parseActualCsv(string $csvContent): array
    {
        $lines = $this->splitLines($csvContent);

        if (count($lines) < 2) {
            return [];
        }

        // 1行目はヘッダー（Date,Time,地点番号,...）
        $results = [];

        foreach (array_slice($lines, 1) as $line) {
            if (trim($line) === '') continue;

            $cells = str_getcsv($line);
            $dateStr = $cells[0] ?? '';
            $timeStr = $cells[1] ?? '';
            $raw     = $cells[2] ?? null;

            if (!$dateStr || !$timeStr || $raw === null || !is_numeric($raw)) continue;

            $dt = $this->parseActualDatetime($dateStr, $timeStr);
            if ($dt === null) continue;

            $results[] = [
                'datetime'   => $dt,
                'wbgt_value' => (float)$raw,
                'data_type'  => 'actual',
            ];
        }

        return $results;
    }

    /**
     * 予測値CSVのヘッダー日時文字列 (YYYYMMDDHH) をパース。
     */
    private function parseForecastDatetime(string $dtStr): ?Carbon
    {
        $dtStr = trim($dtStr);
        if (strlen($dtStr) !== 10) return null;

        try {
            return Carbon::createFromFormat('YmdH', $dtStr, 'Asia/Tokyo');
        } catch (\Exception $e) {
            Log::debug("WbgtCsvParser: failed to parse forecast datetime '{$dtStr}': " . $e->getMessage());
            return null;
        }
    }

    /**
     * 実況値CSVの日付・時刻文字列をパース。
     * 環境省の形式: Date="YYYY/MM/DD" Time="HH:MM"
     */
    private function parseActualDatetime(string $dateStr, string $timeStr): ?Carbon
    {
        try {
            $normalized = str_replace('/', '-', trim($dateStr));
            $hour       = (int)explode(':', trim($timeStr))[0];

            return Carbon::createFromFormat('Y-m-d H', $normalized . ' ' . sprintf('%02d', $hour), 'Asia/Tokyo');
        } catch (\Exception $e) {
            Log::debug("WbgtCsvParser: failed to parse actual datetime '{$dateStr} {$timeStr}': " . $e->getMessage());
            return null;
        }
    }

    /**
     * LF / CRLF どちらでも動作する行分割。
     *
     * @return string[]
     */
    private function splitLines(string $content): array
    {
        return array_filter(
            explode("\n", str_replace("\r\n", "\n", $content)),
            fn (string $line) => trim($line) !== ''
        );
    }
}
