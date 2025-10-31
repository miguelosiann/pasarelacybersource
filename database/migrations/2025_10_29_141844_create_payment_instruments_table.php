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
        Schema::create('payment_instruments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // CyberSource IDs
            $table->string('instrument_identifier_id')->unique();
            $table->string('payment_instrument_id')->unique()->nullable();
            $table->string('type')->default('card'); // card, bank_account, etc.
            
            // Card Details
            $table->string('card_type')->nullable();
            $table->string('card_last_four', 4)->nullable();
            $table->string('expiration_month')->nullable();
            $table->string('expiration_year')->nullable();
            $table->string('cardholder_name')->nullable();
            
            // Status
            $table->string('state')->default('active'); // active, expired, deactivated
            $table->boolean('is_default')->default(false);
            
            // Metadata
            $table->json('metadata')->nullable();
            $table->timestamp('expires_at')->nullable();
            
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('instrument_identifier_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_instruments');
    }
};
