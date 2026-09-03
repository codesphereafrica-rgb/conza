<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('topics', function (Blueprint $table) {
            // Drop existing foreign key, make category_id nullable, then re-add constraint allowing null
            $table->dropForeign(['category_id']);
            $table->unsignedBigInteger('category_id')->nullable()->change();
            $table->foreign('category_id')->references('id')->on('categories')->nullOnDelete();

            // Add attachments JSON column
            $table->json('attachments')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('topics', function (Blueprint $table) {
            // Drop attachments column
            $table->dropColumn('attachments');

            // Make category_id not nullable again
            $table->dropForeign(['category_id']);
            $table->unsignedBigInteger('category_id')->nullable(false)->change();
            $table->foreign('category_id')->references('id')->on('categories')->cascadeOnDelete();
        });
    }
};
