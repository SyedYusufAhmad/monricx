<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_views', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('session_hash', 64)->index();
            $table->char('ip_hash', 64)->nullable()->index();
            $table->string('path', 512);
            $table->string('route_name')->nullable()->index();
            $table->string('referrer_host')->nullable();
            $table->string('device_type', 24)->nullable()->index();
            $table->timestamp('visited_at')->index();

            $table->index(['visited_at', 'route_name']);
        });

        Schema::create('daily_metrics', function (Blueprint $table) {
            $table->date('metric_date')->primary();
            $table->unsignedBigInteger('page_views')->default(0);
            $table->unsignedBigInteger('sessions')->default(0);
            $table->unsignedBigInteger('orders_count')->default(0);
            $table->unsignedBigInteger('paid_orders_count')->default(0);
            $table->unsignedBigInteger('gross_revenue_paise')->default(0);
            $table->char('currency', 3)->default('INR');
            $table->timestamps();
        });

        Schema::create('temporary_admin_accesses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('label')->nullable();
            $table->json('permissions')->nullable();
            $table->unsignedInteger('max_uses')->default(1);
            $table->unsignedInteger('uses')->default(0);
            $table->timestamp('expires_at')->index();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('revoked_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action')->index();
            $table->nullableMorphs('auditable');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('temporary_admin_accesses');
        Schema::dropIfExists('daily_metrics');
        Schema::dropIfExists('page_views');
    }
};
