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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            
            // Payment Details
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('status'); // pending, processing, completed, failed, refunded
            $table->text('description')->nullable();
            
            // Transaction Details
            $table->string('transaction_id')->unique()->nullable();
            $table->string('authorization_code')->nullable();
            $table->string('processor_reference')->nullable();
            
            // 3D Secure Details (align with model usage)
            $table->string('threeds_version')->nullable();
            $table->string('threeds_eci')->nullable();
            $table->string('threeds_cavv')->nullable();
            $table->string('threeds_xid')->nullable();
            $table->string('threeds_authentication_status')->nullable();
            $table->boolean('liability_shift')->default(false);
            $table->string('flow_type')->nullable(); // frictionless, challenge, stand-in, not_enrolled
            $table->json('enrollment_data')->nullable();
            
            // Card Details (last 4 digits, type for reference)
            $table->string('card_last_four', 4)->nullable();
            $table->string('card_type')->nullable();
            
            // Metadata
            $table->json('metadata')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
