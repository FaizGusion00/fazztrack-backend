<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->string('qr_code_hash', 32)->nullable()->after('duration');
            $table->index('qr_code_hash', 'idx_jobs_qr_code_hash');
        });

        // Update existing jobs with QR code hashes
        DB::statement('UPDATE jobs SET qr_code_hash = MD5(CONCAT(job_id, NOW()))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropIndex('idx_jobs_qr_code_hash');
            $table->dropColumn('qr_code_hash');
        });
    }
};
