<?php

use App\Services\WbgtCsvParser;

beforeEach(function () {
    $this->parser = new WbgtCsvParser();
});

// =========================================================
// parseForecastCsv()
// =========================================================

describe('WbgtCsvParser::parseForecastCsv()', function () {
    it('parses valid forecast CSV', function () {
        // 環境省予測値CSV形式: 1行目=時刻ヘッダー、2行目以降=地点データ
        // 予測値は10倍で格納（310 → 31.0℃）
        $csv = implode("\n", [
            ',,2025080112,2025080115,2025080118', // ヘッダー行
            '47662,202508011000,310,285,270',      // 東京（id=47662）
            '47772,202508011000,295,280,265',      // 大阪（id=47772）
        ]);

        $results = $this->parser->parseForecastCsv($csv, '47662');

        expect($results)->toHaveCount(3);
        expect($results[0]['wbgt_value'])->toBe(31.0);  // 310 / 10
        expect($results[1]['wbgt_value'])->toBe(28.5);  // 285 / 10
        expect($results[2]['wbgt_value'])->toBe(27.0);  // 270 / 10
        expect($results[0]['data_type'])->toBe('forecast');
    });

    it('returns empty array for empty CSV', function () {
        $results = $this->parser->parseForecastCsv('', '47662');
        expect($results)->toBeEmpty();
    });

    it('returns empty array when station ID not found', function () {
        $csv = implode("\n", [
            ',,2025080112',
            '47662,202508011000,310',
        ]);

        $results = $this->parser->parseForecastCsv($csv, '99999');
        expect($results)->toBeEmpty();
    });

    it('parses datetime correctly', function () {
        $csv = implode("\n", [
            ',,2025080114',
            '47662,202508011000,300',
        ]);

        $results = $this->parser->parseForecastCsv($csv, '47662');

        expect($results)->toHaveCount(1);
        expect($results[0]['datetime']->format('Y-m-d H'))->toBe('2025-08-01 14');
    });

    it('skips non-numeric WBGT values', function () {
        $csv = implode("\n", [
            ',,2025080112,2025080115',
            '47662,202508011000,310,---',  // '---' は無効
        ]);

        $results = $this->parser->parseForecastCsv($csv, '47662');
        expect($results)->toHaveCount(1); // 有効な1件のみ
    });
});

// =========================================================
// parseActualCsv()
// =========================================================

describe('WbgtCsvParser::parseActualCsv()', function () {
    it('parses valid actual CSV', function () {
        // 環境省実況値CSV形式: Date,Time,WBGT値,...
        $csv = implode("\n", [
            'Date,Time,47662',
            '2025/08/01,12:00,31.5',
            '2025/08/01,13:00,32.0',
            '2025/08/01,14:00,31.8',
        ]);

        $results = $this->parser->parseActualCsv($csv);

        expect($results)->toHaveCount(3);
        expect($results[0]['wbgt_value'])->toBe(31.5);
        expect($results[0]['data_type'])->toBe('actual');
    });

    it('returns empty array for empty CSV', function () {
        $results = $this->parser->parseActualCsv('');
        expect($results)->toBeEmpty();
    });

    it('parses datetime with slash-separated date', function () {
        $csv = implode("\n", [
            'Date,Time,Station',
            '2025/08/01,09:00,25.5',
        ]);

        $results = $this->parser->parseActualCsv($csv);

        expect($results)->toHaveCount(1);
        expect($results[0]['datetime']->format('Y-m-d H'))->toBe('2025-08-01 09');
    });

    it('skips rows with missing WBGT value', function () {
        $csv = implode("\n", [
            'Date,Time,Station',
            '2025/08/01,12:00,',         // 空
            '2025/08/01,13:00,invalid',  // 非数値
            '2025/08/01,14:00,30.0',     // 有効
        ]);

        $results = $this->parser->parseActualCsv($csv);
        expect($results)->toHaveCount(1);
    });

    it('handles CRLF line endings', function () {
        $csv = "Date,Time,Station\r\n2025/08/01,12:00,30.5\r\n";

        $results = $this->parser->parseActualCsv($csv);
        expect($results)->toHaveCount(1);
    });
});
