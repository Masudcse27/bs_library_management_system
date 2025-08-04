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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');
            $table->foreignId('book_id')
                ->constrained('books')
                ->onDelete('cascade');
            $table->foreignId('borrow_id')
                ->constrained('borrows')
                ->onDelete('cascade');
            $table->date('booking_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->enum('status', ['in_progress','available','collected', 'expired'])->default('in_progress');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
