<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Manşet kapağı için yapay zekâdan gelen ek alanlar.
 *
 * Haber başlığı kapakta çoğu zaman fazla uzun kalıyor; manşet grafiği için
 * kısa ve vurucu bir üst başlık ile 2-3 künye bilgisi ayrıca üretilir.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bot_drafts', function (Blueprint $table) {
            $table->string('cover_headline', 200)->nullable()->after('tags');
            $table->json('cover_facts')->nullable()->after('cover_headline');
        });
    }

    public function down(): void
    {
        Schema::table('bot_drafts', function (Blueprint $table) {
            $table->dropColumn(['cover_headline', 'cover_facts']);
        });
    }
};
