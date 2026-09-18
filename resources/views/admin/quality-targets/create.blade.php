<x-admin-layout header="Yeni Ağ Kalitesi Hedefi">
    <div class="max-w-2xl border border-neutral-950 bg-white p-6">
        <h2 class="mb-6 text-sm font-semibold uppercase tracking-widest text-neutral-950">Yeni Web Hedefi Ekle</h2>
        <form action="{{ route('admin.quality-targets.store') }}" method="POST">
            @include('admin.quality-targets._form')
        </form>
    </div>
</x-admin-layout>
