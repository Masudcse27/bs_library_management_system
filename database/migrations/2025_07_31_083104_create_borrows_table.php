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
        Schema::create('borrows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');
            $table->foreignId('book_id')
                ->constrained('books')
                ->onDelete('cascade');
            $table->date('borrowed_at')->nullable();
            $table->date('due_date')->nullable();
            $table->date('returned_at')->nullable();
            $table->enum('status', ['borrowed', 'returned'])->default('borrowed');
            $table->integer('extension_count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('borrows');
    }
};
