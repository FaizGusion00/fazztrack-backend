<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sections', function (Blueprint $table) {
            $table->unsignedInteger('section_id')->autoIncrement();
            $table->string('name', 50)->unique();
        });

        // Insert Section Records
        DB::table('sections')->insert([
            ['name' => 'clients'],
            ['name' => 'orders'],
            ['name' => 'payments'],
            ['name' => 'jobs'],
            ['name' => 'qr_code'],
            ['name' => 'due_dates'],
            ['name' => 'job_finished'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sections');
    }
};
