<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reactions', function (Blueprint $table) {
            $table->dropForeign(['post_id']);
            $table->dropUnique(['user_id', 'post_id', 'type']);
            $table->foreignId('post_id')->nullable()->change();
            $table->foreign('post_id')->references('id')->on('posts')->cascadeOnDelete();
            $table->foreignId('topic_id')->nullable()->after('post_id')->constrained()->cascadeOnDelete();
            $table->unique(['user_id', 'topic_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::table('reactions', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'topic_id', 'type']);
            $table->dropForeign(['topic_id']);
            $table->dropColumn('topic_id');
            $table->dropForeign(['post_id']);
            $table->foreignId('post_id')->nullable(false)->change();
            $table->unique(['user_id', 'post_id', 'type']);
        });
    }
};