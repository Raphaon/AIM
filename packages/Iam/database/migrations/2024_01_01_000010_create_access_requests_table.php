<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('access_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('pending');
            $table->json('roles')->nullable();
            $table->json('permissions')->nullable();
            $table->text('justification')->nullable();
            $table->timestamp('requested_expires_at')->nullable();
            $table->timestamp('decision_at')->nullable();
            $table->text('decision_note')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('requested_expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_requests');
    }
};
