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
        Schema::create('rekeningsaldoitems', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('rekeningsaldo_id'); // Foreign key untuk UUID
            $table->unsignedBigInteger('account_id');
            $table->json('images')->nullable();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->decimal('biaya', 20, 2)->nullable();
            $table->string('keterangan');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rekeningsaldo_items');
    }
};
