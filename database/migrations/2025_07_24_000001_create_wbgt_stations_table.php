<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wbgt_stations', function (Blueprint $table) {
            $table->id();
            $table->string('station_code', 10)->unique()->comment('環境省地点番号 (例: 47662)');
            $table->string('name', 100)->comment('地点名');
            $table->string('prefecture_code', 2)->comment('都道府県コード');
            $table->decimal('latitude', 10, 8)->comment('緯度');
            $table->decimal('longitude', 11, 8)->comment('経度');
            $table->boolean('is_active')->default(true)->comment('有効フラグ（運用期間外は false）');
            $table->timestamps();

            $table->index(['latitude', 'longitude']);
            $table->index('prefecture_code');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wbgt_stations');
    }
};
