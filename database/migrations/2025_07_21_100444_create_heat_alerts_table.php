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
        Schema::create('heat_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('prefecture_code', 2)->comment('都道府県コード');
            $table->enum('alert_type', ['normal', 'warning', 'special_warning'])->comment('アラート種別');
            $table->date('target_date')->comment('対象日');
            $table->timestamp('issued_at')->comment('発表日時');
            $table->boolean('is_active')->default(true)->comment('有効フラグ');
            $table->timestamps();
            
            $table->index(['prefecture_code', 'target_date']);
            $table->index('issued_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('heat_alerts');
    }
};
