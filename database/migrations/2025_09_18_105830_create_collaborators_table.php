<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('collaborators', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('role', 20)->default('admin'); // 'admin' or 'viewer' (we default to admin for parity)
            $table->string('token', 64)->unique();       // for accepting the invite
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('collaborators');
    }
};
