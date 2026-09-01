<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateEventTermsAcceptancesColumns extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('event_terms_acceptances')) {
            return;
        }

        if (!Schema::hasColumn('event_terms_acceptances', 'accepted')) {
            Schema::table('event_terms_acceptances', function (Blueprint $table) {
                $table->boolean('accepted')->default(false)->after('terms_version');
            });
        }

        try {
            DB::statement('ALTER TABLE event_terms_acceptances MODIFY event_id BIGINT UNSIGNED NULL');
        } catch (Throwable $th) {
            // Keep migration idempotent across database engines/environments.
        }
    }

    public function down()
    {
        if (!Schema::hasTable('event_terms_acceptances')) {
            return;
        }

        if (Schema::hasColumn('event_terms_acceptances', 'accepted')) {
            Schema::table('event_terms_acceptances', function (Blueprint $table) {
                $table->dropColumn('accepted');
            });
        }
    }
}
