<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Routing\Controller;
use Tymon\JWTAuth\Facades\JWTAuth; 

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api')->except(['login', 'register']);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
         return response()->json([
            'success' => false,
            'message' => 'Datos inválidos',
            'errors' => $validator->errors()
          ], 422);
        }

        $credentials = $request->only('email', 'password');

        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciales incorrectas'
            ], 401);
        }

     return $this->respondWithToken($token);
    }


    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|between:2,100',
            'email' => 'required|string|email|max:100|unique:users',
            'password' => 'required|string|confirmed|min:6',
            'role' => 'sometimes|string|in:admin,user,manager'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role ?? 'user',
        ]);

        $token = auth()->login($user);

        return response()->json([
            'success' => true,
            'message' => 'Usuario creado exitosamente',
            'user' => $user,
            'authorization' => [
                'token' => $token,
                'type' => 'bearer',
            ]
        ], 201);
    }

    public function logout()
    {
        auth()->logout();
        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada'
        ]);
    }

    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
    }

    public function me()
    {
        return response()->json([
            'success' => true,
            'user' => auth()->user()
        ]);
    }

    protected function respondWithToken($token)
    {
        return response()->json([
        'success' => true,
        'user' => auth()->user(),
        'authorization' => [
            'token' => $token,
            'type' => 'bearer',
            'expires_in' => 3600 
            ]
        ]);
    }
}
