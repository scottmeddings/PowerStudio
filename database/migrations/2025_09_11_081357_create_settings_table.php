<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            // ---- (Optional) legacy key/value for backward-compat ----
            // Keep nullable so it doesn't block real column usage.
            $table->string('key')->nullable()->unique();
            $table->longText('value')->nullable();

            // ---- Singleton guard (no row is inserted here) ----
            // If you plan a single row, app logic can enforce it later.
            $table->tinyInteger('singleton')->default(1)->unique();

            // ================= CORE SITE SETTINGS =================
            // Mirrors "feed.site_url" etc. from your defaults where relevant.
            $table->string('feed_url', 2048)->default('https://podcast.powertime.au/feed.xml');

            $table->string('site_title')->nullable();
            $table->string('site_link', 2048)->default('https://powertime.au');
            $table->string('site_lang', 20)->default('en-us');

            $table->text('site_desc')->nullable();

            $table->string('site_itunes_author')->nullable();
            $table->text('site_itunes_summary')->nullable();
            $table->string('site_itunes_image', 2048)->nullable();

            $table->string('site_owner_name')->nullable();
            $table->string('site_owner_email')->nullable();

            $table->boolean('site_explicit')->default(false);
            $table->string('site_category')->nullable();
            $table->string('site_type', 20)->default('episodic'); // or 'serial'

            // =============== WEBSITE / DISPLAY EXTRAS ===============
            $table->string('podcast_subdomain', 63)->nullable();         // left part only
            $table->string('site_country', 40)->nullable();               // e.g., "Global" or "AU"
            $table->string('site_timezone', 64)->nullable();              // e.g., "Australia/Melbourne"
            $table->string('episode_download_visibility', 20)->default('hidden'); // hidden|public
            $table->boolean('site_topbar_show')->default(true);

            // ===================== FEED OPTIONS =====================
            $table->boolean('feed_explicit')->default(false);            // mirrors 'feed.explicit'
            $table->text('feed_apple_summary')->nullable();              // mirrors 'feed.apple_summary'

            // ============== ADVANCED FEED SETTINGS ==================
            $table->unsignedInteger('feed_episode_limit')->default(100); // mirrors 'feed.episode_number_limit'
            $table->string('feed_ownership_email')->nullable();          // mirrors 'feed.ownership_verification_email'

            // Episode link & artwork tag selection
            // mirrors 'feed.episode_link' => 'podbean'|'original'
            $table->string('feed_episode_link', 24)->default('podbean');
            // mirrors 'feed.episode_artwork_tag' => 'itunes'|'episode'
            $table->string('feed_episode_artwork_tag', 24)->default('itunes');

            // Directory / verification toggles
            // mirrors 'feed.apple_podcasts_verification' and 'feed.remove_from_apple_directory'
            $table->boolean('feed_remove_from_directory')->default(false);
            $table->boolean('feed_apple_verification')->default(false);

            // Redirect options
            // mirrors 'feed.set_podcast_new_feed_url' and 'feed.redirect_to_new_feed'
            $table->boolean('feed_set_new_feed_url')->default(false);
            $table->boolean('feed_redirect_enabled')->default(false);
            $table->string('feed_redirect_url', 2048)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
