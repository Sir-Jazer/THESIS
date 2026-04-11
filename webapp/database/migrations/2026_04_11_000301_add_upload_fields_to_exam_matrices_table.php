<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_matrices', function (Blueprint $table) {
            $table->string('status', 20)->default('draft')->after('name');
            $table->timestamp('uploaded_at')->nullable()->after('status');
            $table->foreignId('uploaded_by')->nullable()->after('uploaded_at')->constrained('users')->nullOnDelete();

            $table->index(['academic_year', 'semester', 'exam_period', 'status'], 'exam_matrices_upload_scope_idx');
        });
    }

    public function down(): void
    {
        Schema::table('exam_matrices', function (Blueprint $table) {
            $table->dropIndex('exam_matrices_upload_scope_idx');
            $table->dropForeign(['uploaded_by']);
            $table->dropColumn(['status', 'uploaded_at', 'uploaded_by']);
        });
    }
};
