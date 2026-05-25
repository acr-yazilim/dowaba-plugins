<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->dateTime('scheduled_at');
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'completed'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('reminded_at')->nullable(); // 1 gün öncesi cron işaretler
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['scheduled_at', 'status']);
            $table->index('reminded_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
