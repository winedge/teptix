<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDeletedSoftaccountToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (
            Schema::hasColumn('users', 'deleted_at') &&
            Schema::hasColumn('users', 'deleted_softaccount')
        ) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'deleted_at')) {
                $table->softDeletes();
            }
            if (!Schema::hasColumn('users', 'deleted_softaccount')) {
            $table->timestamp('deleted_softaccount')->nullable();
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
        if (! Schema::hasColumn('users', 'deleted_softaccount')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('deleted_softaccount');
        });
    }
}
