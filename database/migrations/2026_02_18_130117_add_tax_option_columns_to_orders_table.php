<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTaxOptionColumnsToOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'tax_option')) {
                $table->string('tax_option')->default('with_tax')->after('tax');
            }
            if (!Schema::hasColumn('orders', 'tax_custom_amount')) {
                $table->decimal('tax_custom_amount', 10, 2)->default(0)->after('tax_option');
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
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'tax_option')) {
                $table->dropColumn('tax_option');
            }
            if (Schema::hasColumn('orders', 'tax_custom_amount')) {
                $table->dropColumn('tax_custom_amount');
            }
        });
    }
}
