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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->enum('gender', ['Male', 'Female']);
            $table->string('phone')->nullable();
            $table->date('birthday')->nullable();
            $table->unsignedBigInteger('total_price');
            $table->text('note')->nullable();
            $table->foreignId('payment_method_id')->nullable()->constained('payment_methods')->nullOnDelete();
            $table->unsignedBigInteger('paid_amount')->nullable();
            $table->unsignedBigInteger('change_amount')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
