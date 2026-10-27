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
        // Partiler
        Schema::create('parties', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('short_name')->nullable();
            $table->string('logo_url')->nullable();
            $table->string('primary_color')->default('#333333');
            $table->string('secondary_color')->default('#666666');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Başkanlar
        Schema::create('mayors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignId('city_id')->nullable()->constrained()->nullOnDelete();
            $table->string('district')->nullable();
            $table->foreignId('party_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title')->default('Belediye Başkanı');
            $table->string('photo_url')->nullable();
            $table->text('bio')->nullable();
            $table->longText('bio_full')->nullable();
            $table->string('municipality_name')->nullable();
            $table->string('municipality_logo_url')->nullable();
            $table->string('social_twitter')->nullable();
            $table->string('social_instagram')->nullable();
            $table->string('social_facebook')->nullable();
            $table->string('social_website')->nullable();
            $table->date('term_start')->nullable();
            $table->date('term_end')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'city_id']);
            $table->index('party_id');
        });

        // Haber-Başkan pivot
        Schema::create('article_mayor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mayor_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['article_id', 'mayor_id']);
        });

        // Articles'a is_press_release alanı
        Schema::table('articles', function (Blueprint $table) {
            $table->boolean('is_press_release')->default(false)->after('is_breaking');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn('is_press_release');
        });
        Schema::dropIfExists('article_mayor');
        Schema::dropIfExists('mayors');
        Schema::dropIfExists('parties');
    }
};
