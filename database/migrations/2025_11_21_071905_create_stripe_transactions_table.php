<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStripeTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('transaction_stripe')) {
            return;
        }

        Schema::create('transaction_stripe', function (Blueprint $table) {
            $table->id();
            $table->string('payment_id')->unique();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->decimal('amount', 10, 2);
            $table->text('client_secret')->nullable();
            $table->string('currency', 10);
            $table->string('latest_charge')->nullable();
            $table->json('payment_method_types')->nullable();
            $table->string('status')->nullable();
            $table->json('full_response')->nullable();
            $table->timestamps();

            $table->index('order_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transaction_stripe');
    }
}
