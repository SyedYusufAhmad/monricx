<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->string('refund_status', 32)->nullable()->index()->after('status');
            $table->string('provider_refund_id')->nullable()->unique()->after('provider_payment_id');
            $table->unsignedBigInteger('refund_amount_paise')->nullable()->after('amount_paise');
            $table->timestamp('refunded_at')->nullable()->after('failed_at');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropUnique(['provider_refund_id']);
            $table->dropColumn(['refund_status', 'provider_refund_id', 'refund_amount_paise', 'refunded_at']);
        });
    }
};
