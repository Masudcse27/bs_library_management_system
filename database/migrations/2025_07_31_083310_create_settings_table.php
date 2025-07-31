<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->integer('max_borrow_duration')->default(30); // Maximum borrow duration in days
            $table->integer('max_borrow_limit')->default(3); // Maximum number of books a user can borrow
            $table->integer('max_extension_count')->default(2); // Maximum number of times a borrow can be extended
            $table->integer('max_booking_duration')->default(7); // Maximum booking duration in days
            $table->integer('max_booking_limit')->default(3); // Maximum number of books a user can book
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
