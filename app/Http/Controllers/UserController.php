<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    private array $roles = ['admin','gurubk','wakakesiswaan','kepalasekolah','siswa'];

    /** GET /admin/users?q=&role=&page=&per_page= */
    public function index(Request $req)
    {
        $q = User::query()->latest('id');

        if ($req->filled('q')) {
            $kw = trim((string) $req->q);
            $q->where(function ($s) use ($kw) {
                $s->where('name', 'like', "%{$kw}%")
                  ->orWhere('email', 'like', "%{$kw}%")
                  ->orWhere('phone', 'like', "%{$kw}%");
            });
        }

        if ($req->filled('role')) {
            $q->where('role', $req->role);
        }

        $per = min(max((int) $req->input('per_page', 10), 1), 100);
        $data = $q->paginate($per);

        return response()->json([
            'data' => $data->items(),
            'meta' => [
                'current_page' => $data->currentPage(),
                'per_page'     => $data->perPage(),
                'total'        => $data->total(),
                'last_page'    => $data->lastPage(),
            ]
        ], 200);
    }

    /** POST /admin/users */
    public function store(Request $req)
    {
        $req->validate([
            'name'      => 'required|string|max:100',
            'email'     => 'required|email|max:191|unique:users,email',
            'password'  => 'required|string|min:6',
            'role'      => ['required', Rule::in($this->roles)],
            'phone'     => 'nullable|string|max:30',
            'gender'    => 'nullable|in:male,female',
            'birthdate' => 'nullable|date',
        ]);

        $u = User::create([
            'name'       => $req->name,
            'email'      => $req->email,
            'password'   => Hash::make($req->password),
            'role'       => $req->role,
            'phone'      => $req->input('phone'),
            'gender'     => $req->input('gender'),
            'birthdate'  => $req->input('birthdate'),
        ]);

        return response()->json(['message' => 'User dibuat', 'data' => $u], 201);
    }

    /** GET /admin/users/{id} */
    public function show($id)
    {
        $u = User::findOrFail($id);
        return response()->json($u, 200);
    }

    /** PATCH /admin/users/{id} */
    public function update(Request $req, $id)
    {
        $u = User::findOrFail($id);

        $req->validate([
            'name'      => 'sometimes|required|string|max:100',
            'email'     => [
                'sometimes','required','email','max:191',
                Rule::unique('users','email')->ignore($u->id)->whereNull('deleted_at'),
            ],
            'password'  => 'nullable|string|min:6',
            'role'      => ['sometimes','required', Rule::in($this->roles)],
            'phone'     => 'nullable|string|max:30',
            'gender'    => 'nullable|in:male,female',
            'birthdate' => 'nullable|date',
        ]);

        $u->fill($req->only('name','email','role','phone','gender','birthdate'));
        if ($req->filled('password')) {
            $u->password = Hash::make($req->password);
        }
        $u->save();

        return response()->json(['message' => 'User diperbarui', 'data' => $u], 200);
    }

    /** DELETE /admin/users/{id} (soft delete) */
    public function destroy($id)
    {
        $u = User::findOrFail($id);
        $u->delete();
        return response()->json(['message' => 'User dihapus'], 200);
    }

    /** POST /admin/users/{id}/restore (opsional) */
    public function restore($id)
    {
        $u = User::withTrashed()->findOrFail($id);
        if ($u->trashed()) {
            $u->restore();
        }
        return response()->json(['message' => 'User direstore', 'data' => $u], 200);
    }
}
