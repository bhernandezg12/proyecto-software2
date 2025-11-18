<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function index(Request $request)
    {
        $query = User::query();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
        }

        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        if ($request->has('active')) {
            $query->where('active', $request->active);
        }

        $users = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Usuarios obtenidos exitosamente',
            'data' => $users
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|between:2,100',
            'email' => 'required|string|email|max:100|unique:users',
            'password' => 'required|string|confirmed|min:6',
            'role' => 'sometimes|string|in:admin,user,manager',
            'phone' => 'sometimes|string|max:20',
            'department' => 'sometimes|string|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error en validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role ?? 'user',
            'phone' => $request->phone ?? null,
            'department' => $request->department ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Usuario creado exitosamente',
            'data' => $user
        ], 201);
    }

    public function show($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|between:2,100',
            'email' => 'sometimes|string|email|max:100|unique:users,email,' . $id,
            'password' => 'sometimes|string|confirmed|min:6',
            'role' => 'sometimes|string|in:admin,user,manager',
            'active' => 'sometimes|boolean',
            'phone' => 'sometimes|string|max:20',
            'department' => 'sometimes|string|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error en validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $user->update($request->only([
            'name', 'email', 'role', 'active', 'phone', 'department'
        ]));

        if ($request->has('password')) {
            $user->update(['password' => Hash::make($request->password)]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Usuario actualizado exitosamente',
            'data' => $user
        ]);
    }

    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'No puedes eliminar tu propio usuario'
            ], 403);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Usuario eliminado exitosamente'
        ]);
    }

    public function changeRole(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'role' => 'required|string|in:admin,user,manager'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user->update(['role' => $request->role]);

        return response()->json([
            'success' => true,
            'message' => 'Rol actualizado exitosamente',
            'data' => $user
        ]);
    }

    public function toggleActive($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        $user->update(['active' => !$user->active]);

        return response()->json([
            'success' => true,
            'message' => 'Estado del usuario actualizado',
            'data' => $user
        ]);
    }
}
