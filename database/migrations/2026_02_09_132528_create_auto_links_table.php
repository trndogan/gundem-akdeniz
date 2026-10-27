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
        Schema::create('auto_links', function (Blueprint $table) {
            $table->id();
            $table->string('keyword');
            $table->string('url');
            $table->boolean('is_active')->default(true);
            $table->boolean('new_tab')->default(false);
            $table->boolean('nofollow')->default(false);
            $table->integer('priority')->default(0);
            $table->timestamps();

            $table->index('is_active');
        });

        Schema::table('articles', function (Blueprint $table) {
            $table->longText('content_html')->nullable()->after('body');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auto_links');

        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn('content_html');
        });
    }
};
