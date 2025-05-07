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
        Schema::create('asset_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // $table->foreignUuid('asset_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('asset_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('transaction_id')->constrained()->onDelete('cascade');
            $table->foreignId('account_id'); 
            $table->string('penerima');
            $table->date('tgl');
            $table->decimal('debit');
            $table->decimal('kredit');
            $table->string('items');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_items');
    }
};
