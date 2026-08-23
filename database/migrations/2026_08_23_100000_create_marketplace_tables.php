<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('specialties', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('lawyer_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('city_id')->nullable()->constrained()->nullOnDelete();
            $table->string('display_name');
            $table->string('slug')->unique();
            $table->string('profile_photo')->nullable();
            $table->string('bar_association')->nullable();
            $table->string('license_number')->nullable();
            $table->text('bio')->nullable();
            $table->unsignedSmallInteger('years_of_experience')->default(0);
            $table->string('phone', 20)->nullable();
            // draft | pending_review | verified | suspended | rejected
            $table->string('status', 20)->default('draft')->index();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('submitted_for_review_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('lawyer_specialties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lawyer_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('specialty_id')->constrained()->cascadeOnDelete();
            $table->unique(['lawyer_profile_id', 'specialty_id']);
        });

        Schema::create('lawyer_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lawyer_profile_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            // phone | online | in_person
            $table->string('consultation_type', 20);
            $table->unsignedSmallInteger('duration_minutes');
            $table->unsignedBigInteger('price_amount_minor')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lawyer_services');
        Schema::dropIfExists('lawyer_specialties');
        Schema::dropIfExists('lawyer_profiles');
        Schema::dropIfExists('specialties');
        Schema::dropIfExists('cities');
    }
};
