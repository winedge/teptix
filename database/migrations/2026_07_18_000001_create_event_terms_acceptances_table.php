<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEventTermsAcceptancesTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('event_terms_acceptances')) {
            return;
        }

        Schema::create('event_terms_acceptances', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('event_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('terms_version')->default('event-create-v1');
            $table->boolean('accepted')->default(false);
            $table->timestamp('accepted_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index('event_id');
            $table->index('user_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('event_terms_acceptances');
    }
}
