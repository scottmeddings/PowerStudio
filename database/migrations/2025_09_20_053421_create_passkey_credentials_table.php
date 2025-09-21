<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('passkey_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // base64url credential ID returned by navigator.credentials.create/get
            $table->string('credential_id', 255)->unique();

            // COSE public key (base64 or JWK or raw CBOR — we’ll use base64 here)
            $table->text('public_key')->nullable();

            // Optional helpers
            $table->string('label', 100)->nullable(); // “MacBook Pro”, etc.
            $table->string('transports', 100)->nullable(); // “internal,usb,nfc,ble”
            $table->unsignedBigInteger('sign_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('passkey_credentials');
    }
};
