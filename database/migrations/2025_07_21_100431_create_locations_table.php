<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('地点名');
            $table->text('address')->nullable()->comment('住所');
            $table->decimal('latitude', 10, 8)->comment('緯度');
            $table->decimal('longitude', 11, 8)->comment('経度');
            $table->string('prefecture_code', 2)->nullable()->comment('都道府県コード');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade')->comment('ユーザーID（未ログインの場合はnull）');
            $table->boolean('is_favorite')->default(false)->comment('お気に入り');
            $table->timestamps();
            
            $table->index(['latitude', 'longitude']);
            $table->index('prefecture_code');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
