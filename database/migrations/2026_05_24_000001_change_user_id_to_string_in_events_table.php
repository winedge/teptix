<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeUserIdToStringInEventsTable extends Migration
{
    public function up()
    {
        // First drop any foreign key on user_id if it exists
        try {
            Schema::table('events', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
            });
        } catch (\Exception $e) {
            // No foreign key — continue
        }

        \DB::statement('ALTER TABLE events MODIFY COLUMN user_id VARCHAR(255) NULL');
    }

    public function down()
    {
        \DB::statement('ALTER TABLE events MODIFY COLUMN user_id BIGINT UNSIGNED NULL');
    }
}
