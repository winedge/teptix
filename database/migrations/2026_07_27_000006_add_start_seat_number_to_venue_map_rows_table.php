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
        if (Schema::hasTable('venue_map_rows')) {
            if (!Schema::hasColumn('venue_map_rows', 'start_seat_number')) {
                Schema::table('venue_map_rows', function (Blueprint $table) {
                    $table->unsignedInteger('start_seat_number')->default(1)->after('seat_count');
                });
            }
        }

        if (Schema::hasTable('event_venue_rows')) {
            if (!Schema::hasColumn('event_venue_rows', 'start_seat_number')) {
                Schema::table('event_venue_rows', function (Blueprint $table) {
                    $table->unsignedInteger('start_seat_number')->default(1)->after('seat_count');
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
        if (Schema::hasTable('venue_map_rows')) {
            if (Schema::hasColumn('venue_map_rows', 'start_seat_number')) {
                Schema::table('venue_map_rows', function (Blueprint $table) {
                    $table->dropColumn('start_seat_number');
                });
            }
        }

        if (Schema::hasTable('event_venue_rows')) {
            if (Schema::hasColumn('event_venue_rows', 'start_seat_number')) {
                Schema::table('event_venue_rows', function (Blueprint $table) {
                    $table->dropColumn('start_seat_number');
                });
            }
        }
    }
};
