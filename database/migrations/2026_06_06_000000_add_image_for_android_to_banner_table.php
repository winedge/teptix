<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddImageForAndroidToBannerTable extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('banner', 'image_for_android')) {
            return;
        }

        Schema::table('banner', function (Blueprint $table) {
            $table->string('image_for_android')->nullable();
        });
    }

    public function down()
    {
        if (! Schema::hasColumn('banner', 'image_for_android')) {
            return;
        }

        Schema::table('banner', function (Blueprint $table) {
            $table->dropColumn('image_for_android');
        });
    }
};
