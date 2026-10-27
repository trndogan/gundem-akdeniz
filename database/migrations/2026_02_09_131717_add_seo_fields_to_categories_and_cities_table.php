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
        Schema::table('categories', function (Blueprint $table) {
            $table->string('seo_title')->nullable()->after('description');
            $table->text('seo_description')->nullable()->after('seo_title');
            $table->longText('content')->nullable()->after('seo_description');
        });

        Schema::table('cities', function (Blueprint $table) {
            $table->string('seo_title')->nullable()->after('slug');
            $table->text('seo_description')->nullable()->after('seo_title');
            $table->string('weather_seo_title')->nullable()->after('seo_description');
            $table->text('weather_seo_description')->nullable()->after('weather_seo_title');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['seo_title', 'seo_description', 'content']);
        });

        Schema::table('cities', function (Blueprint $table) {
            $table->dropColumn(['seo_title', 'seo_description', 'weather_seo_title', 'weather_seo_description']);
        });
    }
};
