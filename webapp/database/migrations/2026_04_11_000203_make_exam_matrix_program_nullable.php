<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_matrices', function (Blueprint $table) {
            $table->dropForeign(['program_id']);
            $table->unsignedBigInteger('program_id')->nullable()->change();
            $table->foreign('program_id')->references('id')->on('programs')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('exam_matrices', function (Blueprint $table) {
            $table->dropForeign(['program_id']);
            $table->unsignedBigInteger('program_id')->nullable(false)->change();
            $table->foreign('program_id')->references('id')->on('programs')->cascadeOnDelete();
        });
    }
};
