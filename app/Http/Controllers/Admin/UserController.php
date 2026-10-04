<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveUserRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('roles')->latest()->paginate(25);

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        $roles = Role::orderBy('name')->get();

        return view('admin.users.create', compact('roles'));
    }

    public function store(SaveUserRequest $request)
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);
        $user->syncRoles([$validated['role']]);

        return redirect()->route('admin.users.index')->with('success', 'Usuario creado correctamente.');
    }

    public function show(User $user)
    {
        return redirect()->route('admin.users.edit', $user);
    }

    public function edit(User $user)
    {
        $roles = Role::orderBy('name')->get();

        return view('admin.users.edit', compact('user', 'roles'));
    }

    public function update(SaveUserRequest $request, User $user)
    {
        $validated = $request->validated();

        if ($user->is(auth()->user()) && $validated['role'] !== 'Admin') {
            return back()->withInput()->withErrors(['role' => 'No puedes retirar el rol de administrador de tu propia cuenta.']);
        }

        $user->fill(['name' => $validated['name'], 'email' => $validated['email']]);
        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }
        $user->save();
        $user->syncRoles([$validated['role']]);

        return redirect()->route('admin.users.index')->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(User $user)
    {
        if ($user->is(auth()->user())) {
            return back()->with('error', 'No puedes eliminar tu propia cuenta de administrador.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'Usuario eliminado correctamente.');
    }
}
