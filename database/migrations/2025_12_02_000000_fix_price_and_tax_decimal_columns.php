<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FixPriceAndTaxDecimalColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Clean up invalid data first
        \DB::statement("UPDATE `tickets` SET `price` = 0 WHERE `price` IS NULL OR CAST(`price` AS CHAR) = 'NaN' OR CAST(`price` AS CHAR) = ''");
        \DB::statement("UPDATE `tax` SET `price` = 0 WHERE `price` IS NULL OR CAST(`price` AS CHAR) = 'NaN' OR CAST(`price` AS CHAR) = ''");
        \DB::statement("UPDATE `orders` SET `tax` = 0 WHERE `tax` IS NULL OR CAST(`tax` AS CHAR) = 'NaN' OR CAST(`tax` AS CHAR) = ''");
        \DB::statement("UPDATE `orders` SET `payment` = 0 WHERE `payment` IS NULL OR CAST(`payment` AS CHAR) = 'NaN' OR CAST(`payment` AS CHAR) = ''");
        \DB::statement("UPDATE `orders` SET `coupon_discount` = 0 WHERE `coupon_discount` IS NULL OR CAST(`coupon_discount` AS CHAR) = 'NaN' OR CAST(`coupon_discount` AS CHAR) = ''");
        \DB::statement("UPDATE `orders` SET `org_commission` = 0 WHERE `org_commission` IS NULL OR CAST(`org_commission` AS CHAR) = 'NaN' OR CAST(`org_commission` AS CHAR) = ''");
        \DB::statement("UPDATE `orders` SET `admin_revenue` = 0 WHERE `admin_revenue` IS NULL OR CAST(`admin_revenue` AS CHAR) = 'NaN' OR CAST(`admin_revenue` AS CHAR) = ''");
        \DB::statement("UPDATE `orders` SET `org_revenue` = 0 WHERE `org_revenue` IS NULL OR CAST(`org_revenue` AS CHAR) = 'NaN' OR CAST(`org_revenue` AS CHAR) = ''");
        \DB::statement("UPDATE `order_tax` SET `price` = 0 WHERE `price` IS NULL OR CAST(`price` AS CHAR) = 'NaN' OR CAST(`price` AS CHAR) = ''");

        // Fix tickets table - price column
        Schema::table('tickets', function (Blueprint $table) {
            $table->decimal('price', 10, 2)->default(0)->change();
        });

        // Fix tax table - price column
        Schema::table('tax', function (Blueprint $table) {
            $table->decimal('price', 10, 2)->default(0)->change();
        });

        // Fix orders table - tax, payment, coupon_discount, org_commission, admin_revenue, org_revenue columns
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('tax', 10, 2)->default(0)->nullable()->change();
            $table->decimal('payment', 10, 2)->default(0)->nullable()->change();
            $table->decimal('coupon_discount', 10, 2)->default(0)->nullable()->change();
            $table->decimal('org_commission', 10, 2)->default(0)->nullable()->change();
            $table->decimal('admin_revenue', 10, 2)->default(0)->nullable()->change();
            $table->decimal('org_revenue', 10, 2)->default(0)->nullable()->change();
        });

        // Fix order_tax table - price column
        Schema::table('order_tax', function (Blueprint $table) {
            $table->decimal('price', 10, 2)->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Revert changes if needed
        Schema::table('tickets', function (Blueprint $table) {
            $table->integer('price')->change();
        });

        Schema::table('tax', function (Blueprint $table) {
            $table->integer('price')->change();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->integer('tax')->nullable()->change();
            $table->integer('payment')->nullable()->change();
            $table->integer('coupon_discount')->nullable()->change();
            $table->integer('org_commission')->nullable()->change();
            $table->integer('admin_revenue')->nullable()->change();
            $table->integer('org_revenue')->nullable()->change();
        });

        Schema::table('order_tax', function (Blueprint $table) {
            $table->integer('price')->change();
        });
    }
}
