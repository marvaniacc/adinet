<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Lawyer's recurring weekly working hours. Concrete bookable slots
        // are generated from these, minus already-booked appointments.
        Schema::create('availability_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lawyer_profile_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday'); // Carbon dayOfWeek: 0=Sunday .. 6=Saturday
            $table->string('start_time', 5);        // "10:00"
            $table->string('end_time', 5);          // "12:00"
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['lawyer_profile_id', 'weekday']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('amount_toman');
            $table->string('gateway', 20)->default('zarinpal');
            $table->string('authority', 120)->nullable()->unique();
            $table->string('ref_id', 60)->nullable();
            // pending | redirected | paid | failed | cancelled
            $table->string('status', 20)->default('pending')->index();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('availability_slots');
    }
};
