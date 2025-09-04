<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /** GET /profile : ambil profil user login */
    public function me()
    {
        /** @var User $u */
        $u = Auth::user();
        return response()->json($u, 200);
    }

    /** PATCH /profile : update data dasar */
    public function update(Request $req)
    {
        $req->validate([
            'name'      => 'required|string|max:100',
            'phone'     => 'nullable|string|max:30',
            'gender'    => ['nullable', Rule::in(['male','female'])],
            'birthdate' => 'nullable|date',
        ]);

        /** @var User $u */
        $u = Auth::user();

        // Hindari warning "Undefined method update": gunakan fill()+save()
        $u->fill($req->only('name','phone','gender','birthdate'));
        $u->save();

        // Tidak perlu refresh(); instance sudah berisi nilai terbaru
        return response()->json(['message' => 'Profil diperbarui', 'data' => $u], 200);
    }

    /** PATCH /profile/password : ganti password */
    public function changePassword(Request $req)
    {
        $req->validate([
            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:6|confirmed', // butuh new_password_confirmation
        ]);

        /** @var User $u */
        $u = Auth::user();

        if (!Hash::check($req->current_password, $u->password)) {
            return response()->json(['message' => 'Password sekarang tidak cocok'], 422);
        }

        $u->password = Hash::make($req->new_password);
        $u->save();

        return response()->json(['message' => 'Password diperbarui'], 200);
    }

    /** POST /profile/avatar : upload avatar (multipart) */
    public function uploadAvatar(Request $req)
    {
        $req->validate([
            'avatar' => 'required|image|max:2048', // 2MB
        ]);

        /** @var User $u */
        $u = Auth::user();

        // P1009 hilang karena kita import Illuminate\Support\Facades\Storage
        if ($u->avatar_path && Storage::disk('public')->exists($u->avatar_path)) {
            Storage::disk('public')->delete($u->avatar_path);
        }

        $path = $req->file('avatar')->store('avatars', 'public');
        $u->avatar_path = $path;
        $u->save();

        return response()->json([
            'message'    => 'Avatar diperbarui',
            'avatar_url' => $u->avatar_url, // accessor di model
        ], 200);
    }
}
