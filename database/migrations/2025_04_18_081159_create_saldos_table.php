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
        Schema::create('saldos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('transaction_id')->constrained()->onDelete('cascade');
            $table->foreignId('kontak_id')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->string('kas_bank');
            $table->string('nomor');
            $table->string('penerima');
            $table->string('deskripsi');
            $table->boolean('lunas')->default(false);
            $table->string('terbilang');
            $table->string('transfer_dana'); 
            $table->date('tanggal_transaksi');
            $table->string('images'); 
            $table->decimal('subtotal', 20, 2);
            $table->decimal('saldo_kas', 20, 2);
            $table->timestamp('last_updated')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saldos');
    }
};
