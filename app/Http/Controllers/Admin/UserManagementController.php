<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    public function index(Request $request)
    {
        $users = User::query()
            ->orderBy('name')
            ->paginate(20);

        return view('admin.users.index', [
            'users' => $users,
            'roles' => User::allowedRoles(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'  => ['required','string','max:120'],
            'email' => ['required','email','max:255','unique:users,email'],
            'role'  => ['required', Rule::in(User::allowedRoles())],
        ]);

        // Temp random password; user will get reset link
        $password = str()->password(16);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'role'     => $data['role'],
            'password' => Hash::make($password),
        ]);

        event(new Registered($user));

        // Send a password reset link so they can set their own password
        Password::sendResetLink(['email' => $user->email]);

        return back()->with('ok', 'User created and invite sent.');
    }

    public function updateRole(Request $request, User $user)
    {
        $data = $request->validate([
            'role' => ['required', Rule::in(User::allowedRoles())],
        ]);

        // Safety: prevent removing last admin
        if ($user->isAdmin() && $data['role'] !== User::ROLE_ADMIN) {
            $otherAdmins = User::where('role', User::ROLE_ADMIN)
                ->whereKeyNot($user->getKey())
                ->count();
            if ($otherAdmins === 0) {
                return back()->withErrors(['role' => 'You cannot demote the last admin.']);
            }
        }

        // Safety: prevent self-demote via UI unless another admin exists
        if ($request->user()->is($user) && $data['role'] !== User::ROLE_ADMIN) {
            $otherAdmins = User::where('role', User::ROLE_ADMIN)
                ->whereKeyNot($user->getKey())
                ->count();
            if ($otherAdmins === 0) {
                return back()->withErrors(['role' => 'You cannot remove your own admin role if you are the only admin.']);
            }
        }

        $user->update(['role' => $data['role']]);

        return back()->with('ok', 'Role updated.');
    }

    public function destroy(Request $request, User $user)
    {
        // Safety: cannot delete self
        if ($request->user()->is($user)) {
            return back()->withErrors(['user' => 'You cannot delete your own account from this screen.']);
        }

        // Safety: cannot delete the last admin
        if ($user->isAdmin()) {
            $otherAdmins = User::where('role', User::ROLE_ADMIN)
                ->whereKeyNot($user->getKey())
                ->count();
            if ($otherAdmins === 0) {
                return back()->withErrors(['user' => 'You cannot delete the last admin.']);
            }
        }

        $user->delete();

        return back()->with('ok', 'User deleted.');
    }
}
