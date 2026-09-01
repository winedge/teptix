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
        if (Schema::hasTable('venue_map_seats')) {
            if (!Schema::hasColumn('venue_map_seats', 'is_accessible')) {
                Schema::table('venue_map_seats', function (Blueprint $table) {
                    $table->boolean('is_accessible')->default(false)->after('sort_order');
                });
            }
        }

        if (Schema::hasTable('event_venue_seats')) {
            if (!Schema::hasColumn('event_venue_seats', 'is_accessible')) {
                Schema::table('event_venue_seats', function (Blueprint $table) {
                    $table->boolean('is_accessible')->default(false)->after('status');
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
        if (Schema::hasTable('venue_map_seats')) {
            if (Schema::hasColumn('venue_map_seats', 'is_accessible')) {
                Schema::table('venue_map_seats', function (Blueprint $table) {
                    $table->dropColumn('is_accessible');
                });
            }
        }

        if (Schema::hasTable('event_venue_seats')) {
            if (Schema::hasColumn('event_venue_seats', 'is_accessible')) {
                Schema::table('event_venue_seats', function (Blueprint $table) {
                    $table->dropColumn('is_accessible');
                });
            }
        }
    }
};
