<nav class="bg-white border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-wrap items-center justify-between gap-4 h-16">
        <div class="flex items-center gap-8">
            <a href="{{ route('dashboard') }}" class="font-semibold text-gray-800">Garasi Kendaraan</a>
            <x-nav-link :href="route('dashboard')" :active="request()->routeIs('*.dashboard')">
                Dashboard
            </x-nav-link>
            @if (Auth::user()->isAdmin())
                <x-nav-link :href="route('admin.garages.index')" :active="request()->routeIs('admin.garages.*')">
                    Garasi
                </x-nav-link>
                @php($menunggu = \App\Models\Tenant::where('status_verifikasi', 'PENDING')->count())
                <x-nav-link :href="route('admin.tenants.index')" :active="request()->routeIs('admin.tenants.*')">
                    Verifikasi
                    @if ($menunggu > 0)
                        <span class="ms-1 px-1.5 py-0.5 rounded-full bg-yellow-400 text-xs text-gray-900">{{ $menunggu }}</span>
                    @endif
                </x-nav-link>
                <x-nav-link :href="route('admin.extensions.index')" :active="request()->routeIs('admin.extensions.*')">
                    Perpanjangan
                </x-nav-link>
                <x-nav-link :href="route('admin.reports.index')" :active="request()->routeIs('admin.reports.*')">
                    Laporan
                </x-nav-link>
            @else
                <x-nav-link :href="route('penyewa.rentals.create')" :active="request()->routeIs('penyewa.rentals.create')">
                    Sewa Baru
                </x-nav-link>
                <x-nav-link :href="route('penyewa.rentals.index')" :active="request()->routeIs('penyewa.rentals.index')">
                    Sewa Saya
                </x-nav-link>
            @endif
        </div>

        <div class="flex items-center gap-4 text-sm text-gray-600">
            <span>{{ Auth::user()->email }} ({{ Auth::user()->role->value }})</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="underline hover:text-gray-900">Keluar</button>
            </form>
        </div>
    </div>
</nav>