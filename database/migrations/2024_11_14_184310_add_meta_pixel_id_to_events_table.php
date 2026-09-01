<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMetaPixelIdToEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasColumn('events', 'meta_pixel_id')) {
            return;
        }

        Schema::table('events', function (Blueprint $table) {
            $table->string('meta_pixel_id')->nullable()->unique()->after('event_status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (! Schema::hasColumn('events', 'meta_pixel_id')) {
            return;
        }

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('meta_pixel_id');
        });
    }
}
