<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('events')) {
            if (!Schema::hasColumn('events', 'is_featured')) {
                Schema::table('events', function (Blueprint $table) {
                    $table->boolean('is_featured')->default(false)->after('status');
                });
            }
        }
    }

    public function down()
    {
        if (Schema::hasTable('events')) {
            if (Schema::hasColumn('events', 'is_featured')) {
                Schema::table('events', function (Blueprint $table) {
                    $table->dropColumn('is_featured');
                });
            }
        }
    }
};
