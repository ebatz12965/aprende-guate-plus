<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformModule;
use App\Support\AdminAccess;
use App\Support\Modules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class ModuleController extends Controller
{
    public function __construct(private readonly Modules $modules) {}

    public function index(): View
    {
        $rows = PlatformModule::with('editor')->get()->keyBy('key');
        $modules = collect(Modules::CATALOG)->map(fn (array $module, string $key) => $module + [
            'key' => $key,
            'state' => $this->modules->state($key),
            'row' => $rows[$key] ?? null,
        ]);

        return view('admin.modules.index', [
            'modules' => $modules,
            // El super administrador siempre tiene acceso a la vista previa; no hace falta elegirlo.
            'roles' => Role::orderBy('name')->pluck('name')->reject(fn (string $role) => $role === AdminAccess::SUPER_ROLE)
                ->sortBy(fn (string $role) => AdminAccess::sortKey($role))->values(),
        ]);
    }

    public function update(Request $request, string $module): RedirectResponse
    {
        abort_unless(isset(Modules::CATALOG[$module]), 404);
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(Modules::STATUSES))],
            'message' => ['nullable', 'string', 'max:500'],
            'until' => ['nullable', 'date', 'after:now'],
            'preview_roles' => ['array'],
            'preview_roles.*' => ['string', Rule::exists('roles', 'name'), Rule::notIn([AdminAccess::SUPER_ROLE])],
        ]);
        $until = filled($data['until'] ?? null) ? Carbon::parse($data['until']) : null;
        $this->modules->update($request->user(), $module, $data['status'], $data['message'] ?? null, $until, $data['preview_roles'] ?? []);

        $label = Modules::CATALOG[$module]['label'];

        return redirect()->route('admin.modules.index')->withFragment('modulo-'.$module)->with('success', match ($data['status']) {
            Modules::MAINTENANCE => "«{$label}» quedó en mantenimiento".($until ? ' hasta el '.$until->format('d/m/Y H:i').'; se reactivará solo.' : '. Actívalo cuando termines.'),
            Modules::PREVIEW => "«{$label}» quedó en vista previa.",
            default => "«{$label}» está activo para todas las personas.",
        });
    }
}
