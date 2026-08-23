<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            // audit | development | bug_fix | deployment | security | other
            $table->string('type', 20)->index();
            $table->text('description')->nullable();
            $table->string('file_path');  // private-disk relative path
            $table->string('file_name');  // original/display name
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
