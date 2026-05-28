<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 50)->unique()->index();
            $table->enum('status', ['draft','pending','processing','fiscalized','failed','refunded'])
                  ->default('pending')->index();
            $table->string('payment_type', 20)->default('CASH');
            $table->string('buyer_pin', 30)->nullable();
            $table->string('buyer_name', 200)->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('vat_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->string('kra_receipt_number', 100)->nullable()->index();
            $table->text('kra_qr_code')->nullable();
            $table->string('kra_internal_data', 500)->nullable();
            $table->text('failure_reason')->nullable();
            $table->foreignId('cashier_id')->constrained('users');
            $table->timestamp('fiscalized_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('sales'); }
};
