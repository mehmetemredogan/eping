@csrf

<div class="space-y-6">
    <div>
        <label for="name" class="block text-xs uppercase tracking-widest text-neutral-600">Servis / Hedef Adı</label>
        <input type="text" name="name" id="name" value="{{ old('name', $target->name ?? '') }}" required placeholder="Google Search, Cloudflare CDN, Netflix vb."
            class="mt-1 block w-full border border-neutral-300 bg-white px-3 py-2 text-sm focus:border-neutral-950 focus:outline-none @error('name') border-red-500 @enderror">
        @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="url" class="block text-xs uppercase tracking-widest text-neutral-600">Tam Web URL'si (HTTPS)</label>
        <input type="url" name="url" id="url" value="{{ old('url', $target->url ?? '') }}" required placeholder="https://www.google.com"
            class="mt-1 block w-full border border-neutral-300 bg-white px-3 py-2 text-sm mono focus:border-neutral-950 focus:outline-none @error('url') border-red-500 @enderror">
        <p class="mt-1 text-[11px] text-neutral-400">DNS, TCP, TLS ve TTFB bu adrese yapılan HTTP probe istekleriyle test edilir.</p>
        @error('url') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label for="category" class="block text-xs uppercase tracking-widest text-neutral-600">Kategori</label>
            <select name="category" id="category" class="mt-1 block w-full border border-neutral-300 bg-white px-3 py-2 text-sm focus:border-neutral-950 focus:outline-none">
                @foreach($categories as $key => $label)
                    <option value="{{ $key }}" @selected(old('category', $target->category ?? 'web') == $key)>{{ $label }} ({{ $key }})</option>
                @endforeach
            </select>
            @error('category') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="sort_order" class="block text-xs uppercase tracking-widest text-neutral-600">Sıralama (Küçük önce)</label>
            <input type="number" name="sort_order" id="sort_order" value="{{ old('sort_order', $target->sort_order ?? 0) }}" min="0"
                class="mt-1 block w-full border border-neutral-300 bg-white px-3 py-2 text-sm focus:border-neutral-950 focus:outline-none">
            @error('sort_order') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $target->is_active ?? true))
                class="border-neutral-300 text-neutral-950 focus:ring-0">
            <span class="text-xs uppercase tracking-widest text-neutral-700">Aktif (Testlerde taransın)</span>
        </label>
    </div>

    <div class="flex items-center justify-between pt-4 border-t border-neutral-200">
        <a href="{{ route('admin.quality-targets.index') }}" class="text-xs uppercase tracking-widest text-neutral-500 hover:text-neutral-950">← İptal</a>
        <button type="submit" class="border border-neutral-950 bg-neutral-950 px-6 py-2.5 text-xs font-medium uppercase tracking-widest text-white transition-colors hover:bg-white hover:text-neutral-950">
            Kaydet
        </button>
    </div>
</div>
