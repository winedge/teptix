<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDonationsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('donations')) {
            return;
        }

        Schema::create('donations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('app_user_id')->nullable();
            $table->unsignedBigInteger('guest_user_id')->nullable();
            $table->unsignedBigInteger('event_id');
            $table->decimal('amount', 10, 2);
            $table->string('transaction_id')->nullable()->comment('Stripe txn_ balance transaction ID');
            $table->string('payment_intent_id')->nullable()->comment('Stripe PaymentIntent ID');
            $table->string('status')->default('pending');
            $table->timestamps();
            $table->softDeletes();

            $table->index('event_id');
            $table->index('app_user_id');
            $table->index('guest_user_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('donations');
    }
}
