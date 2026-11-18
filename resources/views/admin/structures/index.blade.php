<x-app-layout titre="Structures">
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <h1 class="titre-page">Structures</h1>
            <a href="{{ route('admin.structures.create') }}" class="btn-primaire">+ Ajouter une structure</a>
        </div>
    </x-slot>

    <p class="mb-4 text-sm text-slate-600">Modifiez les libellés, les couleurs et l'ordre d'affichage. Glissez une ligne avec ⠿ pour changer l'ordre parmi les structures de même niveau ; l'organigramme se met à jour aussitôt.</p>

    <div class="carte p-3" x-data="{
            init() {
                this.$el.querySelectorAll('[data-freres]').forEach((liste) => new window.Sortable(liste, {
                    handle: '.poignee', animation: 150,
                    onEnd: async () => {
                        const ids = [...liste.querySelectorAll(':scope > li')].map((li) => li.dataset.id);
                        const { data } = await window.axios.post(@js(route('admin.structures.reordonner')), { ids });
                        this.$dispatch('notification', { message: data.message });
                    },
                }));
            },
        }">
        @include('admin.structures.partials.branche', ['parentId' => 0])
    </div>
</x-app-layout>
