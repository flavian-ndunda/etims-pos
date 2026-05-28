<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('mpesa_payments', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['stk_push', 'manual_verification']);
            $table->enum('status', ['pending','awaiting_confirmation','verified','completed','failed','rejected'])
                  ->default('pending')->index();
            $table->string('phone_number', 15);
            $table->decimal('amount', 12, 2);
            $table->string('transaction_code', 20)->nullable()->unique()->index();
            $table->string('merchant_request_id', 100)->nullable();
            $table->string('checkout_request_id', 100)->nullable()->index();
            $table->string('result_code', 10)->nullable();
            $table->text('result_desc')->nullable();
            $table->json('raw_callback')->nullable();
            $table->boolean('claimed')->default(false)->index();
            $table->foreignId('sale_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cashier_id')->constrained('users');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->index(['claimed', 'status']);
        });
    }
    public function down(): void { Schema::dropIfExists('mpesa_payments'); }
};
