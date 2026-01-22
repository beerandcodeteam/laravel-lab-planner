<?php

use App\Models\DiagnosisPillar;
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
        Schema::create('diagnosis_pillars', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });


        DiagnosisPillar::insert([
            ['name' => 'Técnico'],
            ['name' => 'Estratégico'],
            ['name' => 'Comportamental'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('diagnosis_pillars');
    }
};
