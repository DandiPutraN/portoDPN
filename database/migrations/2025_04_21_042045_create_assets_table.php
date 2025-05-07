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
        Schema::create('assets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nama_aset');
            $table->string('nomor');
            $table->foreignId('accounts_id');
            $table->date('tgl_pembelian');
            $table->date('tgl_pelepasan');
            $table->decimal('harga_beli', 20, 2);
            $table->decimal('harga_jual', 20, 2);
            $table->enum('status', ['tersedia','terdaftar','pemeliharaan','dilepas'])->default('tersedia');
            $table->string('lokasi');
            $table->string('keterangan');
            $table->string('metode_penyusutan');
            $table->boolean('is_penyusutan')->default(false);
            $table->string('akumulasi_penyusutan');
            $table->string('akun_penyusutan');
            $table->integer('masa_manfaat');
            $table->decimal('presentase_penyusuan', 10, 1);
            $table->decimal('nilai_residu', 20, 2);
            $table->decimal('nilai_buku', 20, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
