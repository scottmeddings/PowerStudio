<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // You can override these via config/env if you like.
        $email    = config('podpower.admin_email', 'admin@powertime.au');
        $name     = config('podpower.admin_name',  'Admin');
        $password = config('podpower.admin_password', 'xk5xnmj7');

        // Prefer the model constant if it exists, fallback to string.
        $role = defined(User::class.'::ROLE_ADMIN') ? User::ROLE_ADMIN : 'admin';

        // Find existing user by email
        $user = User::firstWhere('email', $email);

        if ($user) {
            // Only rehash/reset if requested or hash is outdated/missing
            $updates = [
                'name'               => $name,
                'role'               => $role,
                'remember_token'     => $user->remember_token ?: Str::random(60),
                'email_verified_at'  => $user->email_verified_at ?: now(),
            ];

            if (env('RESET_ADMIN_PASSWORD', false) || empty($user->password) || Hash::needsRehash($user->password)) {
                $updates['password'] = Hash::make($password);
            }

            $user->fill($updates)->save();
        } else {
            // Create fresh admin user
            $user = User::create([
                'name'              => $name,
                'email'             => $email,
                'password'          => Hash::make($password),
                'role'              => $role,
                'remember_token'    => Str::random(60),
                'email_verified_at' => now(),
            ]);
        }

        // If your User implements MustVerifyEmail, mark verified explicitly.
        if (method_exists($user, 'hasVerifiedEmail') && method_exists($user, 'markEmailAsVerified')) {
            if (! $user->hasVerifiedEmail()) {
                $user->markEmailAsVerified();
            }
        }
    }
}
