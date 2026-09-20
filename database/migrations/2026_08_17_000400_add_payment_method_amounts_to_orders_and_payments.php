<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_method', 24)->default('razorpay')->after('payment_status')->index();
            $table->unsignedBigInteger('cod_fee_paise')->default(0)->after('shipping_paise');
            $table->unsignedBigInteger('online_payable_paise')->default(0)->after('cod_fee_paise');
            $table->unsignedBigInteger('cod_due_paise')->default(0)->after('online_payable_paise');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->string('purpose', 24)->default('order_total')->after('provider');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('purpose');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['payment_method']);
            $table->dropColumn([
                'payment_method',
                'cod_fee_paise',
                'online_payable_paise',
                'cod_due_paise',
            ]);
        });
    }
};
