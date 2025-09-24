<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            // Add podcast/site-related columns
            $table->string('site_title', 191)->nullable()->after('singleton');
            $table->text('site_desc')->nullable()->after('site_title');
            $table->string('site_category', 191)->nullable()->after('site_desc');
            $table->string('podcast_subdomain', 191)->nullable()->after('site_category');
            $table->text('site_link')->nullable()->after('podcast_subdomain');
            $table->string('site_lang', 50)->nullable()->after('site_link')->default('en-us');
            $table->string('site_country', 100)->nullable()->after('site_lang')->default('Global');
            $table->string('site_timezone', 100)->nullable()->after('site_country')->default('UTC');
            $table->string('site_type', 50)->nullable()->after('site_timezone')->default('episodic');
            $table->enum('episode_download_visibility', ['public', 'private', 'hidden'])
                  ->default('public')
                  ->after('site_type');
            $table->boolean('site_topbar_show')->default(true)->after('episode_download_visibility');
            $table->text('feed_url')->nullable()->after('site_topbar_show');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'site_title',
                'site_desc',
                'site_category',
                'podcast_subdomain',
                'site_link',
                'site_lang',
                'site_country',
                'site_timezone',
                'site_type',
                'episode_download_visibility',
                'site_topbar_show',
                'feed_url',
            ]);
        });
    }
};
