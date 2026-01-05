<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($data)) {
            $request->session()->regenerate();
            $user = Auth::user();
            if ($user->role === 'admin') {
                $dashboard = route('admin.index'); // ganti dengan route dashboard admin
            } elseif ($user->role === 'guru') {
                $dashboard = route('teacher.materi.index'); // dashboard guru
            } else {
                $dashboard = route('student.index'); // dashboard pelajar
            }
            return redirect()->intended($dashboard)->with('success', 'Login berhasil.');
        }

        return back()->withInput()->with('error', 'Login gagal: Email atau password salah.');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed|min:6',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'pelajar',
            'guru_status' => 'none',
        ]);

        // redirect to login so admin can approve later if requested
        return redirect()->route('login')->with('success', 'Pendaftaran berhasil. Silakan masuk.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}
