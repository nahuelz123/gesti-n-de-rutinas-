<?php

namespace App\Http\Controllers;

use App\Models\Gym;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class GymJoinController extends Controller
{
    public function show(string $inviteCode)
    {
        $gym = Gym::query()->where('invite_code', $inviteCode)->where('active', true)->firstOrFail();

        return view('gym-join.show', [
            'gym' => $gym,
            'inviteCode' => $inviteCode,
        ]);
    }

    public function register(Request $request, string $inviteCode)
    {
        $gym = Gym::query()->where('invite_code', $inviteCode)->where('active', true)->firstOrFail();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        // Por seguridad, el alta por QR SIEMPRE entra como cliente.
        // Si en realidad es un profe, el admin del gimnasio le cambia el
        // rol después desde el panel (Usuarios → editar → Rol: Coach).
        $user = User::create([
            'gym_id' => $gym->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'client',
        ]);

        Auth::login($user);

        return redirect('/app');
    }
}
