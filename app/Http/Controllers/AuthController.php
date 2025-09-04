<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
      // Register function
      public function register(Request $request)
      {
          $validator = Validator::make($request->all(), [
              'name' => 'required|string|max:255',
              'email' => 'required|string|email|max:255|unique:users',
              'password' => 'required|string|min:8|confirmed',
              'role' =>'required|in:siswa,gurubk,wakakesiswaan,kepalasekolah,alumni,admin',
       
          ]);
  
          if ($validator->fails()) {
              return response()->json($validator->errors(), 400);
          }
  
          $user = User::create([
              'name' => $request->name,
              'email' => $request->email,
              'password' => Hash::make($request->password),
              'role'=> $request->role,
          ]);
  
          return response()->json([
              'message' => 'User registered successfully!',
              'user' => $user
          ], 201);
      }
  
      // Login function
      public function login(Request $request)
      {
              // Validasi input
    $credentials = $request->validate([
        'email' => 'required|email',
        'password' => 'required'
    ]);

    if (!Auth::attempt($request->only('email', 'password'))) {
        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    $user = Auth::user();
    if (!method_exists($user, 'createToken')) {
        return response()->json(['message' => 'Sanctum not configured properly'], 500);
    }
    $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
        'message' => 'Login successful',
        'access_token' => $token,
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role
        ]
    ]);
   
      }
      public function logout(Request $request)
{
    $user = $request->user();
    if ($user) {
        $user->currentAccessToken()?->delete();
    }
    return response()->json(['message' => 'Logged out']);
}

}
