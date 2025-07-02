{{-- resources/views/components/admin-layout.blade.php --}}
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'MicroJob Admin - Yönetim Paneli' }}</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Livewire Styles -->
    @livewireStyles
</head>
<body class="bg-gray-50">
<div class="flex h-screen">
    {{-- Sidebar --}}
    <nav class="bg-gray-100 border-r border-gray-200" style="width:240px;">
        <div class="p-4">
            <h4 class="text-xl font-semibold text-gray-800">{{ config('app.name') }} Admin</h4>
        </div>
        <ul class="flex flex-col px-2 space-y-1">
            <li>
                <a class="flex items-center px-3 py-2 rounded-md text-sm font-medium transition-colors {{ request()->routeIs('admin.dashboard') ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}"
                   href="{{ route('admin.dashboard') }}">
                    Dashboard
                </a>
            </li>
            <li>
                <a class="flex items-center px-3 py-2 rounded-md text-sm font-medium transition-colors {{ request()->routeIs('admin.companies.*') ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}"
                   href="{{ route('admin.companies.index') }}">
                    Companies
                </a>
            </li>
            <li>
                <a class="flex items-center px-3 py-2 rounded-md text-sm font-medium transition-colors {{ request()->routeIs('admin.jobs.*') ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}"
                   href="{{ route('admin.jobs.index') }}">
                    Job Postings
                </a>
            </li>
            <li>
                <a class="flex items-center px-3 py-2 rounded-md text-sm font-medium transition-colors {{ request()->routeIs('admin.users.*') ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}"
                   href="{{ route('admin.users.index') }}">
                    Users
                </a>
            </li>
        </ul>
    </nav>

    {{-- Main Content --}}
    <div class="flex-1 flex flex-col">
        {{-- Topbar --}}
        <header class="bg-white border-b border-gray-200 px-4 py-3">
            <div class="flex justify-between items-center">
                <h1 class="text-xl font-semibold text-gray-800">{{ $pageTitle ?? '' }}</h1>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-600">Admin, {{ auth()->user()->name ?? 'Guest' }}</span>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button class="bg-red-50 text-red-600 hover:bg-red-100 hover:text-red-700 px-3 py-1 rounded-md text-sm font-medium border border-red-200 transition-colors">
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </header>

        {{-- Main Content Area --}}
        <main class="flex-1 p-6 overflow-auto bg-gray-50">
            {{-- Flash Messages --}}
            @if(session('success'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md mb-4">
                    {{ session('success') }}
                </div>
            @endif
            
            @if(session('error'))
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-md mb-4">
                    {{ session('error') }}
                </div>
            @endif

            {{ $slot }}
        </main>
    </div>
</div>

    <!-- Livewire Scripts -->
    @livewireScripts
</body>
</html>