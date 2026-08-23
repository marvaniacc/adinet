<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultation_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lawyer_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('lawyer_services')->nullOnDelete();
            $table->string('subject');
            $table->text('description');
            $table->date('preferred_date')->nullable();
            $table->string('preferred_time', 20)->nullable();
            // pending | accepted | rejected | cancelled | completed | expired
            $table->string('status', 20)->default('pending')->index();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->index(['lawyer_profile_id', 'status']);
            $table->index(['client_id', 'status']);
        });

        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultation_request_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('lawyer_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('lawyer_services')->nullOnDelete();
            $table->dateTime('scheduled_at');
            $table->unsignedSmallInteger('duration_minutes');
            $table->string('consultation_type', 20);
            // scheduled | completed | cancelled | no_show
            $table->string('status', 20)->default('scheduled')->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['lawyer_profile_id', 'scheduled_at']);
            $table->index(['client_id', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('consultation_requests');
    }
};
