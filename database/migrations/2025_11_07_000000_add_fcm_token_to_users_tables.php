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
        // Add fcm_token to app_user table
        Schema::table('app_user', function (Blueprint $table) {
            if (!Schema::hasColumn('app_user', 'fcm_token')) {
                $table->text('fcm_token')->nullable()->after('device_token');
            }
        });

        // Add fcm_token to guest_user table
        Schema::table('guest_user', function (Blueprint $table) {
            if (!Schema::hasColumn('guest_user', 'fcm_token')) {
                $table->text('fcm_token')->nullable()->after('phone');
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
        Schema::table('app_user', function (Blueprint $table) {
            if (Schema::hasColumn('app_user', 'fcm_token')) {
                $table->dropColumn('fcm_token');
            }
        });

        Schema::table('guest_user', function (Blueprint $table) {
            if (Schema::hasColumn('guest_user', 'fcm_token')) {
                $table->dropColumn('fcm_token');
            }
        });
    }
};
