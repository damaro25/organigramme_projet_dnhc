<?php

namespace App\Http\Controllers\Admin;

use App\Enums\GroupeStructure;
use App\Enums\TypeLien;
use App\Enums\TypeStructure;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StructureRequest;
use App\Models\Structure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StructureController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Structure::class);

        $structures = Structure::query()->withCount(['agents' => fn ($q) => $q->actifs()])->ordonne()->get();

        // Structures groupées par parent pour un affichage en arbre (glisser-déposer entre structures sœurs).
        return view('admin.structures.index', ['parParent' => $structures->groupBy(fn ($s) => $s->parent_id ?? 0)]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Structure::class);

        return view('admin.structures.form', [
            'structure' => new Structure(['parent_id' => $request->integer('parent_id') ?: null, 'type' => TypeStructure::Section, 'type_lien' => TypeLien::Hierarchique]),
        ] + $this->listes());
    }

    public function store(StructureRequest $request): RedirectResponse
    {
        $structure = Structure::create($request->validated());

        return redirect()->route('admin.structures.index')->with('succes', "La structure « {$structure->nom} » a été ajoutée.");
    }

    public function edit(Structure $structure): View
    {
        $this->authorize('update', $structure);

        return view('admin.structures.form', ['structure' => $structure] + $this->listes($structure));
    }

    public function update(StructureRequest $request, Structure $structure): RedirectResponse
    {
        $structure->update($request->validated());

        return redirect()->route('admin.structures.index')->with('succes', 'La structure a été modifiée.');
    }

    /** Réordonne les structures sœurs (glisser-déposer). */
    public function reordonner(Request $request): JsonResponse
    {
        $this->authorize('create', Structure::class);
        $ids = $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['integer', 'exists:structures,id']])['ids'];

        DB::transaction(function () use ($ids) {
            foreach (array_values($ids) as $i => $id) {
                Structure::whereKey($id)->first()?->update(['ordre' => $i + 1]);
            }
        });

        return response()->json(['message' => 'Ordre enregistré.']);
    }

    private function listes(?Structure $structure = null): array
    {
        $exclus = $structure ? $structure->descendantIds() : [];

        return [
            'parents' => Structure::query()->ordonne()->get()->reject(fn ($s) => in_array($s->id, $exclus, true)),
            'types' => TypeStructure::cases(),
            'groupes' => GroupeStructure::cases(),
            'liens' => TypeLien::cases(),
        ];
    }
}
