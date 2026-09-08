<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('iso_code', 2)->unique();
            $table->string('iso_code_3', 3);
            $table->string('ding_country_id')->nullable();
            $table->string('calling_code');
            $table->string('currency', 3)->default('GBP');
            $table->string('flag_emoji')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('operators', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('ding_operator_id');
            $table->foreignId('country_id')->constrained()->cascadeOnDelete();
            $table->string('logo_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('display_order')->default(0);
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('phone')->unique();
            $table->enum('role', ['admin', 'retailer'])->default('retailer');

            // Retailer fields
            $table->string('shop_name')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('pincode')->nullable();
            $table->string('gst_number')->nullable();
            $table->string('pan_number')->nullable();
            $table->string('aadhar_number')->nullable();

            // KYC
            $table->string('kyc_id_proof_path')->nullable();
            $table->string('kyc_address_proof_path')->nullable();
            $table->enum('kyc_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('kyc_rejection_reason')->nullable();
            $table->timestamp('kyc_verified_at')->nullable();

            // Status
            $table->boolean('is_active')->default(true);
            $table->enum('commission_tier', ['bronze', 'silver', 'gold'])->default('bronze');

            // DingConnect
            $table->string('ding_customer_id')->nullable();
            $table->string('ding_secret')->nullable();

            // Audit
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();

            $table->index('role');
            $table->index('commission_tier');
        });

        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('balance', 12, 2)->default(0.00);
            $table->string('currency', 3)->default('GBP');
            $table->boolean('is_locked')->default(false);
            $table->text('lock_reason')->nullable();
            $table->timestamps();
            $table->index('user_id');
        });

        Schema::create('wallet_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->enum('type', ['credit', 'debit', 'hold', 'refund']);
            $table->decimal('amount', 12, 2);
            $table->decimal('balance_before', 12, 2);
            $table->decimal('balance_after', 12, 2);
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('description')->nullable();
            $table->timestamp('created_at');

            $table->index(['wallet_id', 'transaction_id']);
            $table->index('type');
            $table->index('created_at');
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users'); // Retailer
            $table->foreignId('admin_id')->nullable()->constrained('users'); // Admin who processed

            // Recharge details
            $table->string('mobile_number', 20);
            $table->foreignId('operator_id')->constrained();
            $table->foreignId('country_id')->constrained('countries');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('GBP');

            // Pricing breakdown
            $table->decimal('ding_cost', 10, 2);
            $table->decimal('commission_rate', 5, 4);
            $table->decimal('commission_amount', 10, 2);
            $table->decimal('retailer_charged', 10, 2);

            // DingConnect reference
            $table->string('ding_transaction_id')->nullable();
            $table->string('ding_order_reference')->nullable()->unique();
            $table->json('ding_response')->nullable();

            // Status
            $table->enum('status', ['pending', 'processing', 'success', 'failed', 'cancelled', 'refunded'])->default('pending');
            $table->text('failure_reason')->nullable();

            // Receipt
            $table->string('receipt_number')->nullable()->unique();

            // Callback
            $table->boolean('callback_received')->default(false);
            $table->timestamp('callback_received_at')->nullable();

            // Tracking
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
            $table->index('ding_transaction_id');
            $table->index('mobile_number');
            $table->index('created_at');
        });

        Schema::create('commission_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('scope', ['global', 'operator', 'retailer_tier', 'country', 'amount_band']);
            $table->foreignId('operator_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('country_id')->nullable()->constrained('countries')->nullOnDelete();
            $table->enum('retailer_tier', ['bronze', 'silver', 'gold'])->nullable();
            $table->decimal('min_amount', 10, 2)->nullable();
            $table->decimal('max_amount', 10, 2)->nullable();
            $table->enum('commission_type', ['percentage', 'flat'])->default('percentage');
            $table->decimal('commission_value', 10, 4);
            $table->integer('priority')->default(0);
            $table->date('effective_from');
            $table->date('effective_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['scope', 'is_active']);
        });

        Schema::create('wallet_topups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('GBP');
            $table->decimal('fee_amount', 10, 2)->default(0);
            $table->decimal('fee_percentage', 5, 4)->default(0);
            $table->decimal('total_charged', 12, 2);
            $table->enum('payment_method', ['upi', 'card', 'net_banking', 'bank_transfer', 'wallet']);
            $table->string('payment_gateway');
            $table->string('gateway_transaction_id')->nullable()->unique();
            $table->string('gateway_order_id')->nullable()->unique();
            $table->json('payment_response')->nullable();
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->string('receipt_number')->nullable()->unique();
            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
        });

        Schema::create('ding_callbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ding_transaction_id');
            $table->json('payload');
            $table->enum('status', ['received', 'processing', 'processed', 'failed'])->default('received');
            $table->text('error_message')->nullable();
            $table->timestamp('received_at');
            $table->timestamp('processed_at')->nullable();

            $table->index('ding_transaction_id');
        });

        Schema::create('admin_earnings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained();
            $table->foreignId('retailer_id')->constrained('users');
            $table->decimal('ding_cost', 10, 2);
            $table->decimal('retailer_charged', 10, 2);
            $table->decimal('commission_amount', 10, 2);
            $table->string('currency', 3)->default('GBP');
            $table->boolean('is_settled')->default(false);
            $table->timestamp('settled_at')->nullable();
            $table->timestamp('created_at');
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'read_at']);
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->text('description');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at');

            $table->index('user_id');
            $table->index('action');
            $table->index('created_at');
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type', 50)->default('string');
            $table->string('group', 50)->default('general');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['key', 'group']);
        });

        // Password reset tokens
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // Personal access tokens (Sanctum)
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('admin_earnings');
        Schema::dropIfExists('ding_callbacks');
        Schema::dropIfExists('wallet_topups');
        Schema::dropIfExists('commission_rules');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('wallet_ledgers');
        Schema::dropIfExists('wallets');
        Schema::dropIfExists('users');
        Schema::dropIfExists('operators');
        Schema::dropIfExists('countries');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('sessions');
    }
};
