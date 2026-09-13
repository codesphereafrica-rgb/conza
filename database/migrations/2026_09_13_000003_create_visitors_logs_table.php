<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitors_logs', function (Blueprint $table) {
            $table->id();
            $table->string('ip', 45)->unique();
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->string('isp')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('device_model')->nullable();
            $table->string('platform')->nullable();
            $table->text('page_visitee')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_connected')->default(false);
            $table->unsignedInteger('temps_passe')->default(0);
            $table->unsignedInteger('nombre_tentatives_login')->default(0);
            $table->timestamp('last_seen')->nullable()->index();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitors_logs');
    }
};
