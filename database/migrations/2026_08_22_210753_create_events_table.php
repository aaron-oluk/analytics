<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->char('visitor_hash', 32);
            $table->char('session_id', 32);
            $table->string('pathname', 2048);
            $table->string('referrer_domain')->nullable();
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->char('country_code', 2)->nullable();
            $table->string('device_type', 16)->nullable();
            $table->string('browser', 32)->nullable();
            $table->string('os', 32)->nullable();
            $table->boolean('is_new_visitor')->default(false);
            $table->boolean('is_new_session')->default(false);
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->timestamp('occurred_at')->useCurrent();

            $table->index(['site_id', 'occurred_at']);
            $table->index(['site_id', 'session_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
