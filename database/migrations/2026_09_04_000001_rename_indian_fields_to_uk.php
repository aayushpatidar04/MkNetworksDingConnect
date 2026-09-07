<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Rename Indian tax fields to UK fields in users table
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('state', 'county');
            $table->renameColumn('pincode', 'postcode');
            $table->renameColumn('gst_number', 'vat_number');
            $table->renameColumn('pan_number', 'company_reg_number');
            $table->renameColumn('aadhar_number', 'utr_number');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('county', 'state');
            $table->renameColumn('postcode', 'pincode');
            $table->renameColumn('vat_number', 'gst_number');
            $table->renameColumn('company_reg_number', 'pan_number');
            $table->renameColumn('utr_number', 'aadhar_number');
        });
    }
};
