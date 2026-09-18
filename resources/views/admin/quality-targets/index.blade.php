<x-admin-layout header="Ağ Kalitesi Hedefleri">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" class="flex flex-wrap gap-2">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Hedef veya URL ara..."
                class="border border-neutral-300 bg-white px-3 py-2 text-sm focus:border-neutral-950 focus:outline-none">
            <select name="category" class="border border-neutral-300 bg-white px-3 py-2 text-sm focus:border-neutral-950 focus:outline-none">
                <option value="">Tüm Kategoriler</option>
                @foreach($categories as $key => $label)
                    <option value="{{ $key }}" @selected(request('category') == $key)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="border border-neutral-300 bg-white px-4 py-2 text-sm hover:bg-neutral-50">Filtrele</button>
        </form>
        <a href="{{ route('admin.quality-targets.create') }}" class="border border-neutral-950 bg-neutral-950 px-4 py-2 text-center text-sm font-medium text-white transition-colors hover:bg-white hover:text-neutral-950">
            + Yeni Web Hedefi Ekle
        </a>
    </div>

    <div class="overflow-x-auto border border-neutral-950 bg-white">
        <table class="w-full min-w-[720px] text-left text-sm">
            <thead class="border-b border-neutral-950 text-[10px] uppercase tracking-widest text-neutral-400">
                <tr>
                    <th class="px-4 py-3 font-medium">Hedef Adı</th>
                    <th class="px-4 py-3 font-medium">URL & Domain</th>
                    <th class="px-4 py-3 font-medium">Kategori</th>
                    <th class="px-4 py-3 font-medium">Sıra</th>
                    <th class="px-4 py-3 font-medium">Durum</th>
                    <th class="px-4 py-3 text-right font-medium">İşlem</th>
                </tr>
            </thead>
            <tbody>
                @forelse($targets as $target)
                    <tr class="border-b border-neutral-100 hover:bg-neutral-50">
                        <td class="px-4 py-3 font-medium text-neutral-950">{{ $target->name }}</td>
                        <td class="px-4 py-3 mono text-xs text-neutral-600">
                            <div>{{ $target->url }}</div>
                            <div class="text-neutral-400 text-[11px]">{{ $target->domain }}</div>
                        </td>
                        <td class="px-4 py-3 text-neutral-500">
                            <span class="inline-block border border-neutral-200 bg-neutral-100 px-2 py-0.5 text-xs text-neutral-700">
                                {{ $categories[$target->category] ?? $target->category }}
                            </span>
                        </td>
                        <td class="px-4 py-3 mono text-xs text-neutral-600">{{ $target->sort_order }}</td>
                        <td class="px-4 py-3">
                            @if($target->is_active)
                                <span class="inline-flex items-center gap-1 text-xs font-medium text-emerald-700">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Aktif
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 text-xs font-medium text-neutral-400">
                                    <span class="h-1.5 w-1.5 rounded-full bg-neutral-300"></span> Pasif
                                </span>
                            @endif
                        </td>
                        <td class="space-x-3 px-4 py-3 text-right text-xs">
                            <a href="{{ route('admin.quality-targets.edit', $target) }}" class="text-neutral-950 underline-offset-2 hover:underline">Düzenle</a>
                            <form action="{{ route('admin.quality-targets.destroy', $target) }}" method="POST" class="inline" onsubmit="return confirm('Bu hedefi silmek istediğinizden emin misiniz?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline">Sil</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center text-neutral-400">
                            Henüz ağ kalitesi hedefi eklenmemiş. Yukarıdaki düğmeden ilk hedefi ekleyebilirsiniz.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $targets->links() }}</div>
</x-admin-layout>
