<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Materi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = Auth::user();
            if (! $user || ($user->role ?? '') !== 'admin') abort(403);
            return $next($request);
        });
    }

    public function index()
    {
        $pending = User::where('guru_status', 'requested')->get();
        $users = User::orderByDesc('id')->paginate(20);
        return view('admin.index', compact('pending','users'));
    }

    public function approveGuru($id)
    {
        $user = User::findOrFail($id);
        $user->guru_status = 'approved';
        $user->role = 'guru';
        $user->save();
        return redirect()->back()->with('success', 'User disetujui sebagai guru.');
    }

    public function rejectGuru($id)
    {
        $user = User::findOrFail($id);
        $user->guru_status = 'rejected';
        $user->save();
        return redirect()->back()->with('success', 'Permintaan guru ditolak.');
    }

    public function blockUser($id)
    {
        $user = User::findOrFail($id);
        $user->is_blocked = true;
        $user->save();
        return redirect()->back()->with('success', 'User diblokir.');
    }

    public function unblockUser($id)
    {
        $user = User::findOrFail($id);
        $user->is_blocked = false;
        $user->save();
        return redirect()->back()->with('success', 'User dibuka blokirnya.');
    }

    public function takedownMateri($id)
    {
        $materi = Materi::findOrFail($id);
        $materi->delete();
        return redirect()->back()->with('success', 'Materi telah diturunkan.');
    }

    // List all users (paginated)
    public function users()
    {
        $users = User::orderByDesc('id')->paginate(20);
        return view('admin.users', compact('users'));
    }

    // Show single user detail
    public function showUser($id)
    {
        $user = User::findOrFail($id);
        return view('admin.user', compact('user'));
    }

    // List all materi for admin
    public function materiIndex()
    {
        $materi = Materi::with('guru')->orderByDesc('created_at')->paginate(20);
        return view('admin.materi', compact('materi'));
    }

    // Show specific materi detail
    public function showMateri($id)
    {
        $materi = Materi::with('soal','guru')->findOrFail($id);
        return view('admin.materi_show', compact('materi'));
    }
}
