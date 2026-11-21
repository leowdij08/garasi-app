<nav class="bg-white border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-wrap items-center justify-between gap-4 h-16">
        <div class="flex items-center gap-8">
            <a href="{{ route('dashboard') }}" class="font-semibold text-gray-800">Garasi Kendaraan</a>
            <x-nav-link :href="route('dashboard')" :active="request()->routeIs('*.dashboard')">
                Dashboard
            </x-nav-link>
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