<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\JournalService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;

/** Journal des modifications : qui a modifié quelle photo, quel projet ou quelle étape, et quand. */
class JournalController extends Controller
{
    public function __invoke(Request $request, JournalService $journal): View
    {
        $filtres = $request->validate([
            'type' => ['nullable', Rule::in(array_keys(JournalService::TYPES))],
            'utilisateur' => ['nullable', 'integer'],
            'du' => ['nullable', 'date'],
            'au' => ['nullable', 'date', 'after_or_equal:du'],
        ]);

        $activites = Activity::query()
            ->with(['causer', 'subject'])
            ->when($filtres['type'] ?? null, fn ($q, $t) => $q->where('log_name', $t))
            ->when($filtres['utilisateur'] ?? null, fn ($q, $u) => $q->where('causer_type', User::class)->where('causer_id', $u))
            ->when($filtres['du'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($filtres['au'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.journal', [
            'activites' => $activites,
            'journal' => $journal,
            'filtres' => $filtres,
            'types' => JournalService::TYPES,
            'utilisateurs' => User::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
