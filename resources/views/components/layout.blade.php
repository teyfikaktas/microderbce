<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'MicroJob - İş İlanları' }}</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Livewire Styles -->
    @livewireStyles
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b sticky top-0 z-50">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between h-16">
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="/" class="text-2xl font-bold text-blue-600 hover:text-blue-700 transition">
                        🚀 MicroJob
                    </a>
                </div>
                
                <!-- Navigation -->
                <nav class="hidden md:flex space-x-8">
                    <a href="/" class="text-gray-600 hover:text-blue-600 transition font-medium">
                        Ana Sayfa
                    </a>
                    <a href="/jobs" class="text-gray-600 hover:text-blue-600 transition font-medium">
                        İş İlanları
                    </a>
                    <a href="/companies" class="text-gray-600 hover:text-blue-600 transition font-medium">
                        Şirketler
                    </a>
                </nav>
                
                <!-- Auth Buttons -->
                <div class="flex items-center space-x-4">
                    @if(session('user_id'))
                        <!-- Logged In User Menu -->
                        <div class="flex items-center space-x-4">
                            <!-- User Info -->
                            <div class="flex items-center space-x-2">
                                <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center">
                                    <span class="text-white font-bold text-sm">
                                        {{ substr(session('user_name', 'U'), 0, 1) }}
                                    </span>
                                </div>
                                <div class="hidden md:block">
                                    <p class="text-sm font-medium text-gray-700">{{ session('user_name') }}</p>
                                    <p class="text-xs text-gray-500">
                                        @if(session('user_role') === 'company')
                                            🏢 Şirket
                                        @else
                                            👤 Kullanıcı
                                        @endif
                                    </p>
                                </div>
                            </div>

                            <!-- User Menu Dropdown -->
                            <div class="relative" x-data="{ open: false }">
                                <button @click="open = !open" class="text-gray-600 hover:text-blue-600 transition">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                
                                <div x-show="open" @click.away="open = false" 
                                     class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50">
                                    <a href="/profile" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        👤 Profilim
                                    </a>
                                    @if(session('user_role') === 'company')
                                    <a href="/company/jobs" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        🏢 İlanlarım
                                    </a>
                                    <a href="/company/applications" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        📝 Başvurular
                                    </a>
                                    @else
                                    <a href="/applications" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        📝 Başvurularım
                                    </a>
                                    <a href="/saved-jobs" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        ❤️ Kayıtlı İlanlar
                                    </a>
                                    @endif
                                    <div class="border-t border-gray-100"></div>
                                    <form action="/logout" method="POST" class="block">
                                        @csrf
                                        <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                                            🚪 Çıkış Yap
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @else
                        <!-- Not Logged In -->
                        <div class="flex items-center space-x-3">
                            <a href="/login" 
                               class="text-gray-600 hover:text-blue-600 transition font-medium px-3 py-2 rounded-md hover:bg-blue-50">
                                🔐 Giriş Yap
                            </a>
                            <a href="/register" 
                               class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition font-medium">
                                👤 Üye Ol
                            </a>
                            <a href="/register?role=company" 
                               class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition font-medium text-sm">
                                🏢 Şirket
                            </a>
                        </div>
                    @endif
                </div>

                <!-- Mobile Menu Button -->
                <div class="md:hidden">
                    <button x-data @click="$refs.mobileMenu.classList.toggle('hidden')" 
                            class="text-gray-600 hover:text-blue-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Mobile Menu -->
            <div x-ref="mobileMenu" class="hidden md:hidden pb-4">
                <div class="space-y-2">
                    <a href="/" class="block text-gray-600 hover:text-blue-600 py-2">Ana Sayfa</a>
                    <a href="/jobs" class="block text-gray-600 hover:text-blue-600 py-2">İş İlanları</a>
                    <a href="/companies" class="block text-gray-600 hover:text-blue-600 py-2">Şirketler</a>
                    
                    @if(!session('user_id'))
                    <div class="border-t pt-2 mt-2">
                        <a href="/login" class="block text-blue-600 py-2">🔐 Giriş Yap</a>
                        <a href="/register" class="block text-blue-600 py-2">👤 Üye Ol</a>
                        <a href="/register?role=company" class="block text-green-600 py-2">🏢 Şirket Kayıt</a>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </header>

    <!-- Flash Messages -->
    @if (session()->has('success'))
        <div class="bg-green-500 text-white px-6 py-3 text-center">
            {{ session('success') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="bg-red-500 text-white px-6 py-3 text-center">
            {{ session('error') }}
        </div>
    @endif

    @if (session()->has('info'))
        <div class="bg-blue-500 text-white px-6 py-3 text-center">
            {{ session('info') }}
        </div>
    @endif

    <!-- Main Content -->
    <main>
        {{ $slot }}
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white mt-16">
        <div class="container mx-auto px-4 py-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <h3 class="text-lg font-bold mb-4">🚀 MicroJob</h3>
                    <p class="text-gray-300 text-sm">
                        Türkiye'nin en yenilikçi iş bulma platformu
                    </p>
                </div>
                <div>
                    <h4 class="font-semibold mb-3">İş Arayanlar</h4>
                    <ul class="space-y-2 text-sm text-gray-300">
                        <li><a href="/jobs" class="hover:text-white">İş İlanları</a></li>
                        <li><a href="/register" class="hover:text-white">Üye Ol</a></li>
                        <li><a href="/companies" class="hover:text-white">Şirketler</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold mb-3">Şirketler</h4>
                    <ul class="space-y-2 text-sm text-gray-300">
                        <li><a href="/register?role=company" class="hover:text-white">İlan Ver</a></li>
                        <li><a href="/login" class="hover:text-white">Şirket Girişi</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold mb-3">Destek</h4>
                    <ul class="space-y-2 text-sm text-gray-300">
                        <li><a href="#" class="hover:text-white">İletişim</a></li>
                        <li><a href="#" class="hover:text-white">SSS</a></li>
                        <li><a href="#" class="hover:text-white">Yardım</a></li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-700 mt-8 pt-8 text-center">
                <p class="text-gray-300 text-sm">&copy; 2025 MicroJob. Tüm hakları saklıdır.</p>
            </div>
        </div>
    </footer>

    <!-- Alpine.js for dropdowns -->
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    
    <!-- Livewire Scripts -->
    @livewireScripts
</body>
</html>