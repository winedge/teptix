<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ManagerPermission;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class ManagerController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!Auth::user()->hasRole('Organizer') && !Auth::user()->hasRole('admin')) {
                abort(403, 'Unauthorized access.');
            }
            return $next($request);
        });
    }

    public function index()
    {
        $managers = User::role('Manager')
            ->where('org_id', Auth::user()->id)
            ->orderBy('id', 'DESC')
            ->get();

        foreach ($managers as $manager) {
            $manager->permissions_list = ManagerPermission::where('user_id', $manager->id)
                ->pluck('permission')
                ->toArray();
        }

        return view('admin.manager.index', compact('managers'));
    }

    public function create()
    {
        return view('admin.manager.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email|unique:users',
            'phone' => 'required',
            'password' => 'required|min:6',
        ]);

        $data = $request->all();
        $data['org_id'] = Auth::user()->id;
        $data['password'] = Hash::make($request->password);
        $data['language'] = Setting::first()->language ?? 'English';
        $data['status'] = 1;
        $data['is_verify'] = 1;
        $data['image'] = 'defaultuser.png';

        $user = User::create($data);
        $user->assignRole('Manager');

        // Save permissions
        if ($request->has('permissions')) {
            foreach ($request->permissions as $perm) {
                ManagerPermission::create([
                    'user_id' => $user->id,
                    'permission' => $perm,
                ]);
            }
        }
        // Log manager creation activity
        \App\Models\AdminActivityLog::record(
            \App\Models\AdminActivityLog::MANAGER_CREATED,
            $user,
            __('Manager Created'),
            __('Manager :name (:email) was created by :actor.', [
                'name' => $user->first_name . ' ' . $user->last_name,
                'email' => $user->email,
                'actor' => Auth::user()->first_name . ' ' . Auth::user()->last_name,
            ]),
            ['permissions' => $request->permissions],
            $request
        );

        return redirect('managers')->withStatus(__('Manager has been added successfully.'));
    }

    public function edit($id)
    {
        $manager = User::role('Manager')
            ->where('org_id', Auth::user()->id)
            ->findOrFail($id);

        $assignedPermissions = ManagerPermission::where('user_id', $manager->id)
            ->pluck('permission')
            ->toArray();

        return view('admin.manager.edit', compact('manager', 'assignedPermissions'));
    }

    public function update(Request $request, $id)
    {
        $manager = User::role('Manager')
            ->where('org_id', Auth::user()->id)
            ->findOrFail($id);

        $request->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email|unique:users,email,' . $manager->id,
            'phone' => 'required',
            'password' => 'nullable|min:6',
        ]);

        $data = $request->only(['first_name', 'last_name', 'email', 'phone', 'status']);
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $manager->update($data);

        // Sync permissions
        ManagerPermission::where('user_id', $manager->id)->delete();
        if ($request->has('permissions')) {
            foreach ($request->permissions as $perm) {
                ManagerPermission::create([
                    'user_id' => $manager->id,
                    'permission' => $perm,
                ]);
            }
        }

        return redirect('managers')->withStatus(__('Manager has been updated successfully.'));
    }

    public function status($id)
    {
        $manager = User::role('Manager')
            ->where('org_id', Auth::user()->id)
            ->findOrFail($id);

        $manager->status = $manager->status == 1 ? 0 : 1;
        $manager->save();

        return redirect('managers')->withStatus(__('Manager status changed successfully.'));
    }

    public function destroy($id)
    {
        $manager = User::role('Manager')
            ->where('org_id', Auth::user()->id)
            ->findOrFail($id);

        $manager->delete();

        return redirect('managers')->withStatus(__('Manager deleted successfully.'));
    }
}
