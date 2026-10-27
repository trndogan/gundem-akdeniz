<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Yapay zekâ editör katmanı.
 *
 * Havuzdaki ham haberler işlenmeden önce puanlanır: haber değeri düşük olanlar
 * elenir, yüksek olanlar öne alınır, çok yüksek olanlar manşet veya öne çıkan
 * olarak işaretlenir. Böylece Gemini kotası değersiz duyurulara harcanmaz.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bot_pool', function (Blueprint $table) {
            $table->unsignedTinyInteger('score')->nullable()->after('word_count');
            $table->string('curation_reason', 300)->nullable()->after('score');
            $table->boolean('suggest_headline')->default(false)->after('curation_reason');
            $table->boolean('suggest_featured')->default(false)->after('suggest_headline');
            $table->timestamp('curated_at')->nullable()->after('suggest_featured');

            $table->index(['status', 'score']);
        });

        Schema::table('bot_drafts', function (Blueprint $table) {
            $table->unsignedTinyInteger('score')->nullable()->after('cover_facts');
            $table->boolean('is_headline')->default(false)->after('score');
            $table->boolean('is_featured')->default(false)->after('is_headline');
        });
    }

    public function down(): void
    {
        Schema::table('bot_pool', function (Blueprint $table) {
            $table->dropIndex(['status', 'score']);
            $table->dropColumn(['score', 'curation_reason', 'suggest_headline', 'suggest_featured', 'curated_at']);
        });

        Schema::table('bot_drafts', function (Blueprint $table) {
            $table->dropColumn(['score', 'is_headline', 'is_featured']);
        });
    }
};
