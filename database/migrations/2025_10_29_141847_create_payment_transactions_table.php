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
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->onDelete('cascade');
            
            // Transaction Type
            $table->string('type'); // authorization, capture, refund, void
            $table->string('status'); // pending, completed, failed
            
            // Transaction Details
            $table->string('transaction_id')->unique()->nullable();
            $table->string('reference_id')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            
            // Request/Response
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->text('error_message')->nullable();
            
            // Metadata
            $table->json('metadata')->nullable();
            $table->timestamp('processed_at')->nullable();
            
            $table->timestamps();
            
            $table->index('payment_id');
            $table->index('type');
            $table->index('status');
            $table->index('transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
