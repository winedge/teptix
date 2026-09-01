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
        if (Schema::hasTable('venue_map_template_settings')) {
            if (!Schema::hasColumn('venue_map_template_settings', 'seat_shape')) {
                Schema::table('venue_map_template_settings', function (Blueprint $table) {
                    $table->string('seat_shape')->default('circle')->after('show_row_name'); // 'circle', 'square', 'rectangle'
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('venue_map_template_settings')) {
            if (Schema::hasColumn('venue_map_template_settings', 'seat_shape')) {
                Schema::table('venue_map_template_settings', function (Blueprint $table) {
                    $table->dropColumn('seat_shape');
                });
            }
        }
    }
};
