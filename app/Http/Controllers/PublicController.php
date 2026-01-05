<?php

namespace App\Http\Controllers;

use App\Models\Materi;
use Illuminate\Http\Request;

class PublicController extends Controller
{
    public function materials(Request $request)
    {
        $query = Materi::where('is_public', true);

        if ($search = $request->input('q')) {
            $query->where('judul', 'like', "%{$search}%")->orWhere('konten', 'like', "%{$search}%");
        }

        if ($guru = $request->input('guru')) {
            $query->where('guru_id', $guru);
        }

        $materi = $query->orderByDesc('id')->paginate(8)->withQueryString();
        return view('public.materials', compact('materi'));
    }

    public function showMaterial($id)
    {
        $materi = Materi::findOrFail($id);
        if (! $materi->is_public) abort(403);
        return view('public.show', compact('materi'));
    }

    // Request to become guru
    public function showBecomeGuru()
    {
        return view('user.become_guru');
    }

    public function submitBecomeGuru(Request $request)
    {
        $user = $request->user();
        if (! $user) return redirect()->route('login');
        if ($user->role === 'guru') return redirect()->back()->with('error', 'Anda sudah guru.');
        $user->guru_status = 'requested';
        $user->save();
        return redirect()->back()->with('success', 'Permintaan menjadi guru dikirimkan. Tunggu persetujuan admin.');
    }
}
