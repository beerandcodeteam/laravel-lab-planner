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
        Schema::table('diagnosis_items', function (Blueprint $table) {
            $table->dateTime('agent_selected_at')->nullable();
            $table->dateTime('user_selected_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('diagnosis_items', function (Blueprint $table) {
            $table->dropColumn(['agent_selected_at', 'user_selected_at']);
        });
    }
};
