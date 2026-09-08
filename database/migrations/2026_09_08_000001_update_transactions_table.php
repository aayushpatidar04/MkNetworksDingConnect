<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Add new DingConnect product fields
            $table->string('sku_code', 100)->nullable()->after('operator_id');
            $table->decimal('send_value', 10, 4)->nullable()->after('sku_code');
            $table->decimal('receive_value', 10, 2)->nullable()->after('send_value');
            $table->string('send_currency', 3)->nullable()->after('receive_value');
            $table->string('receive_currency', 3)->nullable()->after('send_currency');
            $table->string('display_text')->nullable()->after('receive_currency');
            $table->string('validity_period', 50)->nullable()->after('display_text');
            $table->json('benefits')->nullable()->after('validity_period');
            // Indexes
            $table->index('sku_code');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['sku_code']);
            $table->dropColumn([
                'sku_code',
                'send_value',
                'receive_value',
                'send_currency',
                'receive_currency',
                'display_text',
                'validity_period',
                'benefits',
            ]);
        });
    }
};
