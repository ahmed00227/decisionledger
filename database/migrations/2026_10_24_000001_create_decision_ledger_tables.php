<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('decisions', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('statement');
            $table->text('rationale');
            $table->string('category');
            $table->string('status')->default('active');
            $table->text('evaluation_limitations');
            $table->timestamps();
        });

        Schema::create('criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('decision_id')->constrained()->cascadeOnDelete();
            $table->string('rule_key');
            $table->text('summary');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('check_runs', function (Blueprint $table) {
            $table->id();
            $table->longText('php_code');
            $table->text('context_notes')->nullable();
            $table->string('decision_scope'); // single | all_compatible
            $table->foreignId('decision_id')->nullable()->constrained()->nullOnDelete();
            $table->text('summary')->nullable();
            $table->timestamps();
        });

        Schema::create('findings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('check_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('decision_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('criterion_id')->nullable()->constrained('criteria')->nullOnDelete();
            $table->string('checker_status'); // confirmed | needs_review | insufficient_evidence
            $table->text('explanation');
            $table->unsignedInteger('evidence_start_line')->nullable();
            $table->unsignedInteger('evidence_end_line')->nullable();
            $table->text('evidence_snippet')->nullable();
            $table->boolean('unsupported_rule')->default(false);
            $table->string('workflow_status')->nullable(); // reviewed | resolved
            $table->text('verification_note')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('findings');
        Schema::dropIfExists('check_runs');
        Schema::dropIfExists('criteria');
        Schema::dropIfExists('decisions');
    }
};
