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
        Schema::create('rekeningsaldos', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('transaction_id')->constrained()->onDelete('cascade');
            $table->foreignId('kontak_id')->nullable()->constrained()->onDelete('cascade');
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
            $table->json('images')->nullable();
            $table->decimal('subtotal', 20, 2);
            $table->decimal('saldo_rekening', 20, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rekeningsaldos');
    }
};
