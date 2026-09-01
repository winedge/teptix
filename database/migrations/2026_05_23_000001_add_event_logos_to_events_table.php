<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEventLogosToEventsTable extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('events', 'event_logos')) {
            return;
        }

        Schema::table('events', function (Blueprint $table) {
            $table->text('event_logos')->nullable()->after('event_logo');
        });
    }

    public function down()
    {
        if (! Schema::hasColumn('events', 'event_logos')) {
            return;
        }

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('event_logos');
        });
    }
}
