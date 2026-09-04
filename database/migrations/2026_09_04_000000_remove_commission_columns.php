<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Remove commission columns from users table
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['commission_tier']);
            $table->dropColumn('commission_tier');
        });

        // Remove commission columns from transactions table
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['ding_cost', 'commission_rate', 'commission_amount', 'retailer_charged']);
        });

        // Remove commission columns from admin_earnings table
        Schema::table('admin_earnings', function (Blueprint $table) {
            $table->dropColumn(['ding_cost', 'retailer_charged', 'commission_amount']);
        });

        // Drop commission_rules table
        Schema::dropIfExists('commission_rules');
    }

    public function down(): void
    {
        // Add back commission columns to users
        Schema::table('users', function (Blueprint $table) {
            $table->enum('commission_tier', ['bronze', 'silver', 'gold'])->default('bronze')->after('role');
            $table->index('commission_tier');
        });

        // Add back commission columns to transactions
        Schema::table('transactions', function (Blueprint $table) {
            $table->decimal('ding_cost', 10, 2)->after('amount');
            $table->decimal('commission_rate', 5, 4)->after('ding_cost');
            $table->decimal('commission_amount', 10, 2)->after('commission_rate');
            $table->decimal('retailer_charged', 10, 2)->after('commission_amount');
        });

        // Add back commission columns to admin_earnings
        Schema::table('admin_earnings', function (Blueprint $table) {
            $table->decimal('ding_cost', 10, 2)->after('transaction_id');
            $table->decimal('retailer_charged', 10, 2)->after('ding_cost');
            $table->decimal('commission_amount', 10, 2)->after('retailer_charged');
        });

        // Recreate commission_rules table
        Schema::create('commission_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('applies_to', ['all', 'tier']);
            $table->string('tier')->nullable();
            $table->json('operators')->nullable();
            $table->json('countries')->nullable();
            $table->decimal('min_amount', 10, 2)->nullable();
            $table->decimal('max_amount', 10, 2)->nullable();
            $table->enum('commission_type', ['percentage', 'flat'])->default('percentage');
            $table->decimal('commission_value', 10, 4);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
        });
    }
};
