<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('venue_map_templates')) {
            return;
        }

        Schema::create('venue_map_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('venue_id')->index();
            $table->string('name')->nullable();
            $table->string('version', 50)->default('1.0');
            $table->unsignedInteger('expected_seat_count')->default(0);
            $table->string('background_image')->default('');
            $table->unsignedInteger('background_width')->nullable();
            $table->unsignedInteger('background_height')->nullable();
            $table->string('status', 30)->default('draft')->index();
            $table->timestamp('published_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venue_map_templates');
    }
};
