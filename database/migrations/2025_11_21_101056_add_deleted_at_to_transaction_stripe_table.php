<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDeletedAtToTransactionStripeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasColumn('transaction_stripe', 'deleted_at')) {
            return;
        }

        Schema::table('transaction_stripe', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (! Schema::hasColumn('transaction_stripe', 'deleted_at')) {
            return;
        }

        Schema::table('transaction_stripe', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
}
