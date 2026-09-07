<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('operators', function (Blueprint $table) {
            $table->string('provider_code')->nullable()->after('ding_operator_id');
            $table->string('region_codes')->nullable()->after('provider_code');
            $table->string('payment_types')->nullable()->after('region_codes');
            $table->string('validation_regex')->nullable()->after('payment_types');
            $table->string('customer_care_number')->nullable()->after('validation_regex');
            $table->boolean('is_premium')->default(false)->after('customer_care_number');
            $table->dropColumn('ding_operator_id');
            $table->index('provider_code');
        });
    }

    public function down(): void
    {
        Schema::table('operators', function (Blueprint $table) {
            $table->dropIndex(['provider_code']);
            $table->dropColumn([
                'provider_code',
                'region_codes',
                'payment_types',
                'validation_regex',
                'customer_care_number',
                'is_premium',
            ]);
            $table->string('ding_operator_id');
        });
    }
};
