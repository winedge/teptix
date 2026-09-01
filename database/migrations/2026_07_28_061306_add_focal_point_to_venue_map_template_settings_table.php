<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFocalPointToVenueMapTemplateSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('venue_map_template_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('venue_map_template_settings', 'focal_x')) {
                $table->float('focal_x')->default(50)->after('seat_shape');
            }
            if (!Schema::hasColumn('venue_map_template_settings', 'focal_y')) {
                $table->float('focal_y')->default(10)->after('focal_x');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('venue_map_template_settings', function (Blueprint $table) {
            if (Schema::hasColumn('venue_map_template_settings', 'focal_x')) {
                $table->dropColumn(['focal_x', 'focal_y']);
            }
        });
    }
}
