<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGeneralBannerFieldsToBannerTable extends Migration
{
    public function up()
    {
        Schema::table('banner', function (Blueprint $table) {
            if (! Schema::hasColumn('banner', 'banner_type')) {
                $table->string('banner_type')->default('event')->after('event_id');
            }
            if (! Schema::hasColumn('banner', 'redirect_url')) {
                $table->string('redirect_url')->nullable()->after('banner_type');
            }
            if (! Schema::hasColumn('banner', 'display_order')) {
                $table->integer('display_order')->nullable()->after('redirect_url');
            }
        });
    }

    public function down()
    {
        Schema::table('banner', function (Blueprint $table) {
            foreach (['banner_type', 'redirect_url', 'display_order'] as $column) {
                if (Schema::hasColumn('banner', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
