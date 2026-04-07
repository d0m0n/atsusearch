<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('default_latitude',  10, 8)->nullable()->after('remember_token')->comment('初期表示緯度');
            $table->decimal('default_longitude', 11, 8)->nullable()->after('default_latitude')->comment('初期表示経度');
            $table->string('default_address')->nullable()->after('default_longitude')->comment('初期表示住所');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['default_latitude', 'default_longitude', 'default_address']);
        });
    }
};
