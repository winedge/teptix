<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDonationIdToTransactionStripeTable extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('transaction_stripe', 'donation_id')) {
            return;
        }

        Schema::table('transaction_stripe', function (Blueprint $table) {
            $table->string('donation_id')->nullable()->after('order_id')->comment('donation.id prefixed with don_ e.g. don_1');
        });
    }

    public function down()
    {
        if (! Schema::hasColumn('transaction_stripe', 'donation_id')) {
            return;
        }

        Schema::table('transaction_stripe', function (Blueprint $table) {
            $table->dropColumn('donation_id');
        });
    }
}
