<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('year_level')->unsigned();
            $table->string('section_code');
            $table->foreignId('proctor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['program_id', 'year_level', 'section_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sections');
    }
};
