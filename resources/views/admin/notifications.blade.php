<x-app-layout titre="Notifications">
    <x-slot name="header"><h1 class="titre-page">Notifications</h1></x-slot>
    <div class="carte">
        <ul class="divide-y divide-slate-100">
            @forelse ($notifications as $n)
                <li @class(['flex items-start justify-between gap-4 px-5 py-3', 'bg-dnh-50' => is_null($n->read_at)])>
                    <div>
                        <p class="text-sm text-slate-800">{{ $n->data['message'] ?? '' }}</p>
                        <p class="text-xs text-slate-500">
                            @if (! empty($n->data['auteur'])) Par {{ $n->data['auteur'] }} · @endif
                            {{ $n->created_at->translatedFormat('d F Y à H:i') }} · taux du projet : {{ $n->data['taux'] ?? '—' }} %
                        </p>
                    </div>
                    @if (! empty($n->data['projet_id']))
                        <a href="{{ route('admin.projets.edit', $n->data['projet_id']) }}" class="shrink-0 text-sm text-dnh-700 hover:underline">Voir le projet</a>
                    @endif
                </li>
            @empty
                <li class="px-5 py-6 text-sm text-slate-500">Aucune notification.</li>
            @endforelse
        </ul>
    </div>
    <div class="mt-4">{{ $notifications->links() }}</div>
</x-app-layout>
