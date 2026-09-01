<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketAllowUsersTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('ticket_allow_users')) {
            Schema::create('ticket_allow_users', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('ticket_id');
                $table->tinyInteger('allow_to_user')->default(1)->comment('1 = Display to user, 0 = Hide from user');
                $table->timestamps();

                $table->index('ticket_id');
                $table->index('allow_to_user');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('ticket_allow_users');
    }
}
