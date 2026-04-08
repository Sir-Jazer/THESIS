<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subject_prerequisites', function (Blueprint $table) {
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('prerequisite_id')->constrained('subjects')->cascadeOnDelete();
            $table->primary(['subject_id', 'prerequisite_id']);
        });

        Schema::create('subject_corequisites', function (Blueprint $table) {
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('corequisite_id')->constrained('subjects')->cascadeOnDelete();
            $table->primary(['subject_id', 'corequisite_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_corequisites');
        Schema::dropIfExists('subject_prerequisites');
    }
};
