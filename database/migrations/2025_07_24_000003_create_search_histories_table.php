<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('search_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade')->comment('ユーザーID');
            $table->string('query')->nullable()->comment('入力テキスト（住所等）');
            $table->decimal('latitude',  10, 8)->nullable()->comment('検索した緯度');
            $table->decimal('longitude', 11, 8)->nullable()->comment('検索した経度');
            $table->foreignId('station_id')->nullable()->constrained('wbgt_stations')->nullOnDelete()->comment('最寄り観測地点');
            $table->timestamp('searched_at')->useCurrent()->comment('検索日時');

            $table->index('user_id');
            $table->index('searched_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_histories');
    }
};
