<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->boolean('feed_explicit')->default(false)->after('feed_url');
            $table->text('feed_apple_summary')->nullable()->after('feed_explicit');
            $table->string('feed_episode_link', 191)->nullable()->after('feed_apple_summary');
            $table->integer('feed_episode_limit')->default(100)->after('feed_episode_link');
            $table->enum('feed_episode_artwork_tag', ['itunes','rss','none'])
                  ->default('itunes')
                  ->after('feed_episode_limit');
            $table->string('feed_ownership_email', 191)->nullable()->after('feed_episode_artwork_tag');
            $table->boolean('feed_apple_verification')->default(false)->after('feed_ownership_email');
            $table->boolean('feed_remove_from_directory')->default(false)->after('feed_apple_verification');
            $table->boolean('feed_set_new_feed_url')->default(false)->after('feed_remove_from_directory');
            $table->string('feed_redirect_url', 2048)->nullable()->after('feed_set_new_feed_url');
            $table->boolean('feed_redirect_enabled')->default(false)->after('feed_redirect_url');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'feed_explicit',
                'feed_apple_summary',
                'feed_episode_link',
                'feed_episode_limit',
                'feed_episode_artwork_tag',
                'feed_ownership_email',
                'feed_apple_verification',
                'feed_remove_from_directory',
                'feed_set_new_feed_url',
                'feed_redirect_url',
                'feed_redirect_enabled',
            ]);
        });
    }
};
