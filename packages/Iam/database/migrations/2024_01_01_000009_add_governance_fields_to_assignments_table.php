<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('role_user', function (Blueprint $table): void {
            $table->foreignId('assigned_by')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $table->timestamp('expires_at')->nullable()->after('assigned_by');
            $table->string('assignment_note')->nullable()->after('expires_at');
            $table->index('expires_at');
        });

        Schema::table('permission_user', function (Blueprint $table): void {
            $table->foreignId('assigned_by')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $table->timestamp('expires_at')->nullable()->after('assigned_by');
            $table->string('assignment_note')->nullable()->after('expires_at');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('role_user', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('assigned_by');
            $table->dropColumn(['expires_at', 'assignment_note']);
        });

        Schema::table('permission_user', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('assigned_by');
            $table->dropColumn(['expires_at', 'assignment_note']);
        });
    }
};
