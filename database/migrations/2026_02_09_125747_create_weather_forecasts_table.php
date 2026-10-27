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
        Schema::create('weather_forecasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->constrained()->cascadeOnDelete();
            $table->date('forecast_date');
            $table->string('description')->nullable();
            $table->string('icon')->nullable();
            $table->decimal('temp_min', 5, 1)->nullable();
            $table->decimal('temp_max', 5, 1)->nullable();
            $table->decimal('temp_current', 5, 1)->nullable();
            $table->integer('humidity')->nullable();
            $table->decimal('wind_speed', 5, 1)->nullable();
            $table->string('wind_direction')->nullable();
            $table->timestamps();

            $table->unique(['city_id', 'forecast_date']);
            $table->index('forecast_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weather_forecasts');
    }
};
