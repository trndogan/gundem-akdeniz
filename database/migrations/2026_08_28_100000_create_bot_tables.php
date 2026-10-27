<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Haber Botu modülü — resmî kaynaklardan toplama, Gemini ile özgünleştirme,
 * görsel üretimi ve onay kuyruğu için gereken tablolar.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Resmî kaynak kataloğu
        Schema::create('bot_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('institution_type', 40)->default('diger');
            $table->string('site_url')->nullable();
            $table->string('list_url', 500);
            $table->string('type', 10)->default('html');       // html | rss
            $table->foreignId('city_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();

            // Boş bırakılırsa çalışma anında otomatik tespit devreye girer
            $table->string('link_selector', 255)->nullable();
            $table->string('content_selector', 255)->nullable();
            $table->string('image_selector', 255)->nullable();
            $table->string('title_selector', 255)->nullable();

            $table->boolean('is_active')->default(true);
            $table->boolean('auto_publish')->default(false);   // onay beklemeden yayınla
            $table->integer('priority')->default(0);           // yüksek olan önce taranır

            $table->timestamp('last_crawled_at')->nullable();
            $table->timestamp('last_success_at')->nullable();
            $table->unsignedInteger('consecutive_errors')->default(0);
            $table->text('last_error')->nullable();
            $table->unsignedInteger('fetched_total')->default(0);
            $table->unsignedInteger('avg_words')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique('list_url');
            $table->index(['is_active', 'last_crawled_at']);
            $table->index(['institution_type', 'is_active']);
        });

        // Ham haber havuzu — kaynaktan çekilmiş, henüz AI'dan geçmemiş
        Schema::create('bot_pool', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_id')->constrained('bot_sources')->cascadeOnDelete();
            $table->string('url', 700);
            $table->char('content_hash', 32)->unique();
            $table->string('title', 500);
            $table->longText('content');
            $table->string('image_url', 1000)->nullable();
            $table->unsignedInteger('word_count')->default(0);
            $table->timestamp('source_published_at')->nullable();
            $table->string('status', 20)->default('new');       // new | processing | used | rejected | failed
            $table->string('reject_reason')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('source_id');
        });

        // AI'dan geçmiş taslaklar — onay kuyruğu
        Schema::create('bot_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pool_id')->nullable()->constrained('bot_pool')->nullOnDelete();
            $table->foreignId('source_id')->nullable()->constrained('bot_sources')->nullOnDelete();

            $table->string('title', 500);
            $table->string('slug', 300)->nullable();
            $table->text('excerpt')->nullable();
            $table->longText('body');
            $table->string('tags', 500)->nullable();
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();

            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained()->nullOnDelete();

            $table->string('image_path', 500)->nullable();
            $table->string('image_mode', 20)->nullable();       // processed | ai | placeholder | none
            $table->string('original_url', 700)->nullable();

            $table->string('status', 20)->default('draft');     // draft | approved | published | rejected | failed
            $table->string('ai_model', 60)->nullable();
            $table->text('error')->nullable();

            $table->foreignId('article_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        // Gemini API anahtar havuzu — günlük kota rotasyonu
        Schema::create('bot_gemini_keys', function (Blueprint $table) {
            $table->id();
            $table->string('label')->nullable();
            $table->text('api_key');
            $table->string('model', 60)->nullable();            // boşsa genel ayardaki model
            $table->unsignedInteger('daily_limit')->default(200);
            $table->unsignedInteger('today_usage')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_exhausted')->default(false);
            $table->date('last_reset_date')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'is_exhausted']);
        });

        // Yasaklı / zorunlu kelime filtreleri
        Schema::create('bot_keyword_filters', function (Blueprint $table) {
            $table->id();
            $table->string('keyword');
            $table->string('mode', 10)->default('block');       // block | require
            $table->string('scope', 10)->default('both');       // title | content | both
            $table->unsignedInteger('hit_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'mode']);
        });

        // Anahtar/değer ayarları
        Schema::create('bot_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->longText('value')->nullable();
            $table->timestamps();
        });

        // Bot günlüğü
        Schema::create('bot_logs', function (Blueprint $table) {
            $table->id();
            $table->string('level', 10)->default('info');       // info | warning | error
            $table->string('channel', 20)->default('system');   // crawl | ai | image | publish | system
            $table->string('message', 500);
            $table->json('context')->nullable();
            $table->foreignId('source_id')->nullable()->constrained('bot_sources')->nullOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->index(['level', 'created_at']);
            $table->index(['channel', 'created_at']);
        });

        // Cron çalışma özetleri
        Schema::create('bot_runs', function (Blueprint $table) {
            $table->id();
            $table->string('command', 30);                      // crawl | process | publish | cleanup
            $table->string('status', 15)->default('running');   // running | success | failed
            $table->json('stats')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['command', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_runs');
        Schema::dropIfExists('bot_logs');
        Schema::dropIfExists('bot_settings');
        Schema::dropIfExists('bot_keyword_filters');
        Schema::dropIfExists('bot_gemini_keys');
        Schema::dropIfExists('bot_drafts');
        Schema::dropIfExists('bot_pool');
        Schema::dropIfExists('bot_sources');
    }
};
