<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('redemption_type', 50)->nullable()->after('benefits'); // Immediate, ReadReceipt, Manual
            $table->string('product_type', 50)->nullable()->after('redemption_type'); // DirectTopUp, Bundle, Voucher, etc.
            $table->text('receipt_text')->nullable()->after('product_type'); // PIN/code for ReadReceipt
            $table->index('redemption_type');
            $table->index('product_type');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['redemption_type']);
            $table->dropIndex(['product_type']);
            $table->dropColumn([
                'redemption_type',
                'product_type',
                'receipt_text'
            ]);
        });
    }
};
