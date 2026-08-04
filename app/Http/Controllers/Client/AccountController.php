<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AccountController extends Controller
{
    public function edit(Request $request)
    {
        return view('client.account.edit', [
            'user' => $request->user(),
            'activityLevels' => User::ACTIVITY_LEVELS,
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'age' => ['nullable', 'integer', 'min:10', 'max:100'],
            'activity_level' => ['nullable', Rule::in(array_keys(User::ACTIVITY_LEVELS))],
            'goals' => ['nullable', 'string', 'max:1000'],
            'medical_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $user->update($data);

        return back()->with('success', 'Datos actualizados. Tu coach ya puede verlos.');
    }

    public function updatePassword(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::default()],
        ]);

        $user->update([
            'password' => Hash::make($request->input('password')),
        ]);

        return back()->with('success', 'Contraseña actualizada.');
    }
}
