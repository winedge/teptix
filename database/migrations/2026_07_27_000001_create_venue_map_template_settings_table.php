<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('venue_map_template_settings')) {
            Schema::create('venue_map_template_settings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('venue_map_template_id');
                $table->string('numbering_mode')->default('section'); // 'section' or 'continuous'
                $table->string('counting_direction')->default('left_to_right'); // 'left_to_right' or 'right_to_left'
                $table->string('layout_style')->default('curved'); // 'curved' or 'straight'
                $table->boolean('show_row_name')->default(false);
                $table->string('seat_shape')->default('circle'); // 'circle', 'square', 'rectangle'
                $table->timestamps();

                $table->foreign('venue_map_template_id')
                    ->references('id')
                    ->on('venue_map_templates')
                    ->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('venue_map_template_settings');
    }
};
