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
        Schema::table('wbgt_data', function (Blueprint $table) {
            $table->enum('data_source', ['csv', 'official_site', 'sample'])->default('sample')->after('data_type');
            $table->timestamp('fetch_time')->nullable()->after('data_source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wbgt_data', function (Blueprint $table) {
            $table->dropColumn(['data_source', 'fetch_time']);
        });
    }
};
