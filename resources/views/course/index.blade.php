<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración de Cursos</title>
    <!-- Bootstrap CSS desde CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Administración de Cursos</h1>
        <a href="{{ route('courses.create') }}" class="btn btn-primary">
            + Crear Curso
        </a>
    </div>

    @if ($courses->isEmpty())
        <div class="alert alert-warning text-center">
            No hay cursos creados en la plataforma.
        </div>
    @else
        <div class="table-responsive shadow-sm">
            <table class="table table-striped align-middle">
                <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Título</th>
                    <th>Categoría</th>
                    <th>Precio</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($courses as $course)
                    <tr>
                        <td>{{ $course->id }}</td>
                        <td>{{ $course->title }}</td>
                        <td>{{ $course->category->name ?? 'Sin categoría' }}</td>
                        <td>Q {{ number_format($course->price, 2) }}</td>
                        <td>
                            @if($course->status === 'published')
                                <span class="badge bg-success">Publicado</span>
                            @else
                                <span class="badge bg-secondary">Borrador</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('courses.show', $course->id) }}" class="btn btn-sm btn-info">Ver</a>
                            <a href="{{ route('courses.edit', $course->id) }}" class="btn btn-sm btn-warning">Editar</a>
                            <form action="{{ route('courses.destroy', $course->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('¿Seguro que deseas eliminar este curso?')">
                                    Eliminar
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<!-- Bootstrap JS desde CDN -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
