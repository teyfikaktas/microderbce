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
        Schema::create('job_postings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description');
            $table->text('requirements')->nullable();
            $table->string('position');
            $table->string('city');
            $table->string('country')->default('Turkey');
            $table->enum('work_type', ['fulltime', 'parttime', 'contract', 'internship'])->default('fulltime');
            $table->enum('experience_level', ['junior', 'mid', 'senior', 'lead'])->default('junior');
            $table->decimal('salary_min', 10, 2)->nullable();
            $table->decimal('salary_max', 10, 2)->nullable();
            $table->string('currency', 10)->default('TRY');
            $table->boolean('is_active')->default(true);
            $table->integer('application_count')->default(0);
            $table->integer('view_count')->default(0);
            $table->timestamps();
            
            // Indexes for search performance
            $table->index(['city', 'country']);
            $table->index('position');
            $table->index('is_active');
            $table->index('created_at');
            $table->index(['city', 'position', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_postings');
    }
};