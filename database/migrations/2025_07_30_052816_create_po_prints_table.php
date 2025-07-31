<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('modern')->create('po_prints', function (Blueprint $table) {
            $table->id();
            $table->string('po_docno', 50);
            $table->unsignedBigInteger('printed_by');
            $table->string('print_type', 20)->default('pdf'); // pdf, excel
            $table->timestamps();

            $table->foreign('printed_by')->references('id')->on('users');
            $table->index('po_docno');
        });
    }

    public function down(): void
    {
        Schema::connection('modern')->dropIfExists('po_prints');
    }
};