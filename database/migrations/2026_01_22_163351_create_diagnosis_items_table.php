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
        Schema::create('diagnosis_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('diagnosis_id')->constrained();
            $table->foreignId('diagnosis_item_type_id')->constrained();
            $table->foreignId('diagnosis_pillar_id')->constrained();
            $table->text('description');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('diagnosis_items');
    }
};
