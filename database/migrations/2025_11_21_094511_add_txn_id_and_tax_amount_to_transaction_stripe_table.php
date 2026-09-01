<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTxnIdAndTaxAmountToTransactionStripeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (
            Schema::hasColumn('transaction_stripe', 'txn_id') &&
            Schema::hasColumn('transaction_stripe', 'tax_amount')
        ) {
            return;
        }

        Schema::table('transaction_stripe', function (Blueprint $table) {
            if (! Schema::hasColumn('transaction_stripe', 'txn_id')) {
                $table->string('txn_id')->nullable()->after('latest_charge');
            }
            if (! Schema::hasColumn('transaction_stripe', 'tax_amount')) {
                $table->decimal('tax_amount', 10, 2)->nullable()->after('txn_id');
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
        if (
            ! Schema::hasColumn('transaction_stripe', 'txn_id') &&
            ! Schema::hasColumn('transaction_stripe', 'tax_amount')
        ) {
            return;
        }

        Schema::table('transaction_stripe', function (Blueprint $table) {
            if (Schema::hasColumn('transaction_stripe', 'txn_id')) {
                $table->dropColumn('txn_id');
            }
            if (Schema::hasColumn('transaction_stripe', 'tax_amount')) {
                $table->dropColumn('tax_amount');
            }
        });
    }
}
