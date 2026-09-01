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
            if (!Schema::hasColumn('venue_map_template_settings', 'show_row_name')) {
                Schema::table('venue_map_template_settings', function (Blueprint $table) {
                    $table->boolean('show_row_name')->default(false)->after('layout_style');
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
            if (Schema::hasColumn('venue_map_template_settings', 'show_row_name')) {
                Schema::table('venue_map_template_settings', function (Blueprint $table) {
                    $table->dropColumn('show_row_name');
                });
            }
        }
    }
};
