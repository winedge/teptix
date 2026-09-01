<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AssignTaxPermissionsToOrganizer extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $permissions = DB::table('permissions')
            ->whereIn('name', ['tax_access', 'tax_create', 'tax_edit', 'tax_delete'])
            ->get();

        $roles = DB::table('roles')
            ->whereIn('name', ['Organizer', 'Manager'])
            ->get();

        foreach ($roles as $role) {
            foreach ($permissions as $permission) {
                $exists = DB::table('role_has_permissions')
                    ->where('permission_id', $permission->id)
                    ->where('role_id', $role->id)
                    ->exists();

                if (!$exists) {
                    DB::table('role_has_permissions')->insert([
                        'permission_id' => $permission->id,
                        'role_id'        => $role->id,
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $permissions = DB::table('permissions')
            ->whereIn('name', ['tax_access', 'tax_create', 'tax_edit', 'tax_delete'])
            ->pluck('id');

        $roles = DB::table('roles')
            ->whereIn('name', ['Organizer', 'Manager'])
            ->pluck('id');

        DB::table('role_has_permissions')
            ->whereIn('permission_id', $permissions)
            ->whereIn('role_id', $roles)
            ->delete();
    }
}
