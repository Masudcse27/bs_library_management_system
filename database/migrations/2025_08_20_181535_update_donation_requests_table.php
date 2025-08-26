<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('donation_requests', function (Blueprint $table) {
            // Drop unused columns
            $table->dropColumn(['author_name', 'location', 'contact_number']);

            // Modify status enum
            $table->enum('status', ['pending', 'collected'])->default('pending')->change();

            // Add new columns
            $table->string('bs_id')->after('status');
            $table->string('sbu')->after('bs_id');
        });
    }

    public function down(): void
    {
        Schema::table('donation_requests', function (Blueprint $table) {
            $table->string('author_name')->nullable();
            $table->string('location')->nullable();
            $table->string('contact_number');

            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->change();

            $table->dropColumn(['bs_id', 'sbu']);
        });
    }
};
