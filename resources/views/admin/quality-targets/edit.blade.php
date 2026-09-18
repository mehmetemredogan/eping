<x-admin-layout header="Ağ Kalitesi Hedefini Düzenle">
    <div class="max-w-2xl border border-neutral-950 bg-white p-6">
        <h2 class="mb-6 text-sm font-semibold uppercase tracking-widest text-neutral-950">Hedefi Düzenle: {{ $target->name }}</h2>
        <form action="{{ route('admin.quality-targets.update', $target) }}" method="POST">
            @method('PUT')
            @include('admin.quality-targets._form')
        </form>
    </div>
</x-admin-layout>
