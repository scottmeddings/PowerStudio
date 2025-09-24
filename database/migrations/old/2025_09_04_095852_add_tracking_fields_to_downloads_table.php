<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Check outside the closure to avoid SQLite quirks
        $needsUserAgent = ! Schema::hasColumn('downloads', 'user_agent');
        $needsIp        = ! Schema::hasColumn('downloads', 'ip_address');

        if ($needsUserAgent || $needsIp) {
            Schema::table('downloads', function (Blueprint $table) use ($needsUserAgent, $needsIp) {
                if ($needsUserAgent) {
                    // user-agents can exceed 255 chars -> use text
                    $table->text('user_agent')->nullable()->after('episode_id');
                }
                if ($needsIp) {
                    // 45 chars to support IPv6
                    $table->string('ip_address', 45)->nullable()->after('user_agent');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('downloads', function (Blueprint $table) {
            if (Schema::hasColumn('downloads', 'ip_address')) {
                $table->dropColumn('ip_address');
            }
            if (Schema::hasColumn('downloads', 'user_agent')) {
                $table->dropColumn('user_agent');
            }
        });
    }
};
