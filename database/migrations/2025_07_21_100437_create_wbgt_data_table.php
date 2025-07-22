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
        Schema::create('wbgt_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained()->onDelete('cascade')->comment('地点ID');
            $table->date('date')->comment('対象日');
            $table->tinyInteger('hour')->unsigned()->comment('時間（0-23）');
            $table->decimal('wbgt_value', 4, 1)->nullable()->comment('暴さ指数');
            $table->enum('data_type', ['actual', 'forecast'])->comment('データ種別');
            $table->timestamps();
            
            $table->unique(['location_id', 'date', 'hour', 'data_type']);
            $table->index(['date', 'hour']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wbgt_data');
    }
};
