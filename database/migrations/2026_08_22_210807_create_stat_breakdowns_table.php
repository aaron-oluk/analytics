<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stat_breakdowns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('dimension', 24);
            $table->string('value', 512);
            $table->unsignedInteger('visitors')->default(0);
            $table->unsignedInteger('pageviews')->default(0);
            $table->timestamps();

            $table->unique(['site_id', 'date', 'dimension', 'value'], 'stat_breakdowns_unique');
            $table->index(['site_id', 'date', 'dimension']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stat_breakdowns');
    }
};
