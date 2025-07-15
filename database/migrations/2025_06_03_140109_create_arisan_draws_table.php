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
        Schema::create('arisan_draws', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('arisan_groups')->onDelete('cascade');
            $table->integer('draw_number');
            $table->foreignId('winner_id')->constrained('users')->onDelete('cascade');
            $table->date('draw_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('arisan_draws');
    }
};
