<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dowaba_oauth_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('local_user_id')->nullable()->index();
            $table->string('dowaba_user_id', 64)->index();
            $table->string('email')->nullable();

            $table->text('access_token');
            $table->text('refresh_token')->nullable();
            $table->text('id_token')->nullable();

            $table->timestamp('expires_at')->nullable();
            $table->json('scopes')->nullable();
            $table->json('claims')->nullable();

            $table->timestamps();

            $table->unique(['local_user_id', 'dowaba_user_id'], 'dowaba_oauth_tokens_user_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dowaba_oauth_tokens');
    }
};
