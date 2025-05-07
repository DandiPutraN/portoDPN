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
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('category_id')->constrained()->onDelete('cascade');
            $table->string('nomor_trx');
            $table->boolean('lunas')->default(0); // Menambahkan kolom lunas (default false)
            // $table->decimal('sisa_tagihan', 20, 2)->default(0); // Menambahkan kolom sisa tagihan
            $table->string('bayar_dari');
            $table->string('penerima');
            $table->string('terbilang');
            $table->date('tanggal_transaksi');
            $table->date('jatuh_tempo')->nullable();
            $table->string('termin');
            $table->decimal('subtotal', 20, 2);
            // $table->decimal('saldo_cash', 20, 2);
            $table->string('divisi'); 
            $table->enum('status', ['pending', 'paid', 'unpaid'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
