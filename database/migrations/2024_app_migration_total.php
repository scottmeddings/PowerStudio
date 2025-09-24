\<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // ---- USERS ----
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->string('role', 20)->default('user')->index();
            $table->rememberToken();
            $table->timestamps();
            $table->string('provider')->nullable();
            $table->string('provider_id')->nullable();
            $table->text('avatar')->nullable();
            $table->text('cover_path')->nullable();
            $table->text('profile_photo')->nullable();
        });

        // ---- CACHE ----
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key', 191)->primary();
            $table->mediumText('value');
            $table->integer('expiration');
        });

        // ---- JOBS ----
        Schema::create('jobs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('queue', 191)->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        // ---- EPISODES ----
        Schema::create('episodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 191);
            $table->text('description')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->string('slug', 191)->unique();

            $table->text('audio_url')->nullable();
            $table->bigInteger('audio_bytes')->nullable();
            $table->text('audio_path')->nullable();
            $table->integer('duration_sec')->nullable();
            $table->text('playable_url')->nullable();

            $table->string('status', 32)->default('draft');
            $table->unsignedInteger('downloads_count')->default(0);
            $table->unsignedInteger('comments_count')->default(0);
            $table->integer('episode_number')->nullable();
            $table->string('episode_type', 50)->nullable();
            $table->boolean('explicit')->default(false);
            $table->integer('season')->nullable();
            $table->text('image_url')->nullable();
            $table->text('image_path')->nullable();
            $table->text('cover_path')->nullable();
            $table->text('chapters_url')->nullable();
            $table->longText('chapters')->nullable();
            $table->string('guid', 191)->nullable()->unique();
            $table->string('ai_status', 50)->nullable();
            $table->integer('ai_progress')->nullable();
            $table->string('ai_message', 255)->nullable();
            $table->string('podcast_title', 191)->nullable();
            $table->text('podcast_url')->nullable();
            $table->text('media_path')->nullable();
            $table->text('backup_path')->nullable();
            $table->uuid('uuid')->unique();
            $table->string('unique_key', 191)->nullable()->unique();
            $table->timestamps();
            $table->unique(['user_id','title','published_at'], 'episodes_user_title_pub_unique');
        });

        // ---- DOWNLOADS ----
        Schema::create('downloads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('episode_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->text('referrer')->nullable();
            $table->string('device', 100)->nullable();
            $table->string('location', 100)->nullable();
            $table->timestamps();
        });

        // ---- COMMENTS ----
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->morphs('commentable');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('comment');
            $table->enum('status', ['pending','approved','rejected'])->default('pending');
            $table->timestamps();
        });

        // ---- EPISODE COMMENTS ----
        Schema::create('episode_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('episode_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('comment');
            $table->timestamps();
        });

        // ---- EPISODE TRANSCRIPTS ----
        Schema::create('episode_transcripts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('episode_id')->constrained()->cascadeOnDelete();
            $table->longText('transcript');
            $table->timestamps();
        });

        // ---- PODCAST APPS ----
        Schema::create('podcast_apps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 191);
            $table->timestamps();
        });

        // ---- PODCAST DIRECTORIES ----
        Schema::create('podcast_directories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 191);
            $table->timestamps();
        });

        // ---- SITE SETTINGS ----
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('key', 191)->index();
            $table->json('value')->nullable();
            $table->timestamps();
            $table->unique(['user_id','key']);
        });

        // ---- SETTINGS ----
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('key', 191)->nullable()->index();
            $table->longText('value')->nullable();
            $table->boolean('singleton')->default(false);
            $table->timestamps();
            $table->unique(['user_id','key'], 'settings_user_key_unique');
        });

        // ---- SOCIAL CONNECTIONS ----
        Schema::create('social_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 191);
            $table->longText('access_token')->nullable();
            $table->longText('refresh_token')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('page_id', 191)->nullable();
            $table->string('channel_id', 191)->nullable();
            $table->json('settings')->nullable();
            $table->boolean('is_connected')->default(false);
            $table->timestamps();
            $table->unique(['user_id','provider']);
        });

        // ---- SOCIAL CREDENTIALS ----
        Schema::create('social_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 191);
            $table->json('credentials')->nullable();
            $table->timestamp('connected_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id','provider']);
        });

        // ---- SOCIAL ACCOUNTS ----
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 32)->index();
            $table->string('external_id', 191)->nullable();
            $table->longText('access_token')->nullable();
            $table->longText('refresh_token')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->unique(['user_id','provider']);
        });

        // ---- SOCIAL POSTS ----
        Schema::create('social_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 191)->nullable();
            $table->longText('body');
            $table->text('episode_url')->nullable();
            $table->string('visibility', 24)->default('public');
            $table->json('services');
            $table->string('status', 24)->default('queued');
            $table->json('assets')->nullable();
            $table->timestamps();
        });

        // ---- COLLABORATORS ----
        Schema::create('collaborators', function (Blueprint $table) {
            $table->id();
            $table->string('email', 191)->index();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('role', 20)->default('admin');
            $table->string('token', 64)->unique();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
        });

        // ---- PROMOS ----
        Schema::create('promos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name', 191);
            $table->string('code', 191)->unique();
            $table->decimal('discount', 5, 2)->nullable();
            $table->date('expires_at')->nullable();
            $table->timestamps();
        });

        // ---- PAYOUTS ----
        Schema::create('payouts', function (Blueprint $t) {
            $t->id();
            $t->string('provider', 50)->default('stripe');
            $t->string('external_id', 191)->nullable()->index();
            $t->date('payout_date')->nullable()->index();
            $t->decimal('amount_usd', 12, 2)->default(0);
            $t->enum('status', ['pending','in_transit','paid','failed','canceled','processing'])->default('pending');
            $t->json('meta')->nullable();
            $t->timestamps();
            $t->unique(['provider','external_id']);
        });

        // ---- STRIPE ACCOUNTS ----
        Schema::create('stripe_accounts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->index();
            $t->string('account_id', 191)->index();
            $t->string('account_type', 50)->default('standard');
            $t->string('status', 50)->default('connected');
            $t->json('capabilities')->nullable();
            $t->timestamps();
            $t->unique(['user_id','account_id']);
        });

        // ---- STRIPE TRANSACTIONS ----
        Schema::create('stripe_transactions', function (Blueprint $t) {
            $t->id();
            $t->string('txn_id', 191)->unique();
            $t->date('available_on')->index();
            $t->decimal('amount_usd', 12, 2);
            $t->string('type', 50)->nullable();
            $t->json('raw')->nullable();
            $t->timestamps();
        });

        // ---- PASSKEY CREDENTIALS ----
        Schema::create('passkey_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('credential_id', 191)->unique();
            $table->longText('public_key')->nullable();
            $table->string('label', 100)->nullable();
            $table->string('transports', 100)->nullable();
            $table->unsignedBigInteger('sign_count')->default(0);
            $table->timestamps();
        });

        // ---- WEBAUTHN CREDENTIALS ----
        Schema::create('webauthn_credentials', function (Blueprint $table) {
            $table->string('id', 191)->primary();
            $table->string('authenticatable_type', 191);
            $table->unsignedBigInteger('authenticatable_id');
            $table->string('user_id', 191);
            $table->string('alias', 191)->nullable();
            $table->unsignedBigInteger('counter')->nullable();
            $table->string('rp_id', 191);
            $table->string('origin', 191);
            $table->longText('transports')->nullable();
            $table->string('aaguid', 36)->nullable();
            $table->longText('public_key');
            $table->string('attestation_format', 50)->default('none');
            $table->longText('certificates')->nullable();
            $table->timestamp('disabled_at')->nullable();
            $table->timestamps();
        });

        // ---- AD INVENTORIES ----
        Schema::create('ad_inventories', function (Blueprint $t) {
            $t->id();
            $t->foreignId('episode_id')->constrained()->cascadeOnDelete();
            $t->unsignedInteger('pre_total')->default(0);
            $t->unsignedInteger('pre_sold')->default(0);
            $t->unsignedInteger('mid_total')->default(0);
            $t->unsignedInteger('mid_sold')->default(0);
            $t->unsignedInteger('post_total')->default(0);
            $t->unsignedInteger('post_sold')->default(0);
            $t->enum('status', ['draft','selling','paused'])->default('draft');
            $t->timestamps();
        });

        // ---- MONETIZATIONS ----
        Schema::create('monetizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('episode_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 50);
            $table->decimal('amount', 10, 2)->nullable();
            $table->string('currency', 10)->default('USD');
            $table->timestamps();
        });

        // ---- HOUSE CAMPAIGNS ----
        Schema::create('house_campaigns', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('podcast_id')->nullable()->index();
            $t->string('name', 191);
            $t->enum('status', ['draft','active','paused','completed'])->default('draft');
            $t->date('start_at')->nullable();
            $t->date('end_at')->nullable();
            $t->unsignedTinyInteger('priority')->default(5);
            $t->json('targets')->nullable();
            $t->timestamps();
        });

        // ---- HOUSE PROMOS ----
        Schema::create('house_promos', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('campaign_id')->index();
            $t->string('label', 191);
            $t->enum('slot', ['pre','mid','post'])->default('mid');
            $t->text('audio_url')->nullable();
            $t->text('cta_url')->nullable();
            $t->json('episodes')->nullable();
            $t->timestamps();
        });

        // ---- REVENUE DAILY ----
        Schema::create('revenue_daily', function (Blueprint $t) {
            $t->id();
            $t->date('day')->index();
            $t->unsignedInteger('downloads')->default(0);
            $t->decimal('impressions', 12, 2)->default(0);
            $t->decimal('ecpm', 8, 2)->default(0);
            $t->decimal('revenue_usd', 12, 2)->default(0);
            $t->timestamps();
            $t->unique(['day']);
        });

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        Schema::dropIfExists('revenue_daily');
        Schema::dropIfExists('house_promos');
        Schema::dropIfExists('house_campaigns');
        Schema::dropIfExists('monetizations');
        Schema::dropIfExists('ad_inventories');
        Schema::dropIfExists('webauthn_credentials');
        Schema::dropIfExists('passkey_credentials');
        Schema::dropIfExists('stripe_transactions');
        Schema::dropIfExists('stripe_accounts');
        Schema::dropIfExists('payouts');
        Schema::dropIfExists('promos');
        Schema::dropIfExists('collaborators');
        Schema::dropIfExists('social_posts');
        Schema::dropIfExists('social_accounts');
        Schema::dropIfExists('social_credentials');
        Schema::dropIfExists('social_connections');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('site_settings');
        Schema::dropIfExists('podcast_directories');
        Schema::dropIfExists('podcast_apps');
        Schema::dropIfExists('episode_transcripts');
        Schema::dropIfExists('episode_comments');
        Schema::dropIfExists('comments');
        Schema::dropIfExists('downloads');
        Schema::dropIfExists('episodes');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('cache');
        Schema::dropIfExists('users');

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
};
