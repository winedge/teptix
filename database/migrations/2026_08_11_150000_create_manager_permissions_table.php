<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

class CreateManagerPermissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('manager_permissions');
        
        Schema::create('manager_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('permission');
            $table->timestamps();
        });

        // Ensure the Manager role exists
        try {
            Role::firstOrCreate(['name' => 'Manager']);
        } catch (\Exception $e) {
            // Role table might not exist or connection issues during setup
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('manager_permissions');
    }
}
