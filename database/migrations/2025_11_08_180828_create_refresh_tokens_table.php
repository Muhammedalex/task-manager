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
        Schema::create('refresh_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade')
                ->onUpdate('cascade')
                ->comment('User who owns this refresh token');
            $table->string('token', 64)->unique()->comment('Hashed refresh token');
            $table->string('device_name')->nullable()->comment('Device identifier');
            $table->string('ip_address', 45)->nullable()->comment('IP address when token was created');
            $table->text('user_agent')->nullable()->comment('User agent when token was created');
            $table->timestamp('expires_at')->comment('Token expiration date');
            $table->timestamp('last_used_at')->nullable()->comment('Last time token was used');
            $table->boolean('is_revoked')->default(false)->comment('Whether token is revoked');
            $table->timestamps();

            // Indexes for performance
            $table->index('user_id', 'idx_refresh_tokens_user_id');
            $table->index('token', 'idx_refresh_tokens_token');
            $table->index('expires_at', 'idx_refresh_tokens_expires_at');
            $table->index(['user_id', 'is_revoked'], 'idx_refresh_tokens_user_revoked');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refresh_tokens');
    }
};
