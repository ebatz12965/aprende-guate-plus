<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gestión de Roles</title>

    <!-- Tailwind CSS desde CDN para estilos rápidos -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans antialiased p-6">

<h1 class="text-3xl font-bold mb-6">Gestión de Roles</h1>

<div class="bg-white shadow rounded-lg p-6">
    <h2 class="text-2xl font-semibold mb-4">Listado de Roles</h2>

    @if($roles->count() > 0)
        <table class="min-w-full border border-gray-200">
            <thead class="bg-gray-100">
            <tr>
                <th class="py-2 px-4 text-left border-b">ID</th>
                <th class="py-2 px-4 text-left border-b">Nombre de Rol</th>
                <th class="py-2 px-4 text-left border-b">Creado</th>
                <th class="py-2 px-4 text-left border-b">Actualizado</th>
                <th class="py-2 px-4 text-center border-b">Acciones</th>
            </tr>
            </thead>
            <tbody>
            @foreach($roles as $role)
                <tr class="border-b hover:bg-gray-50">
                    <td class="py-2 px-4">{{ $role->id }}</td>
                    <td class="py-2 px-4">{{ $role->name }}</td>
                    <td class="py-2 px-4">{{ $role->created_at->format('d/m/Y H:i') }}</td>
                    <td class="py-2 px-4">{{ $role->updated_at->format('d/m/Y H:i') }}</td>
                    <td class="py-2 px-4"></td>
                    <td class="py-2 px-4 text-center flex justify-center gap-2">
                        <!-- Botón Editar -->
                        <a href="#"
                           class="bg-sky-600 hover:bg-sky-700 text-white font-semibold py-1 px-3 rounded">
                            Editar
                        </a>

                        <!-- Botón Eliminar -->
                        <form action="{{ route('admin.roles.destroy', $user->id) }}" method="POST" onsubmit="return confirm('¿Seguro que deseas eliminar este usuario?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="bg-red-500 hover:bg-red-600 text-white font-semibold py-1 px-3 rounded">
                                Eliminar
                            </button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @else
        <p class="text-gray-500">No hay roles registrados.</p>
    @endif
</div>

</body>
</html>
