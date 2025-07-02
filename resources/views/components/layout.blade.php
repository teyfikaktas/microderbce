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
    <header class="bg-white shadow-sm border-b">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between h-16">
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="/" class="text-2xl font-bold text-blue-600">MicroJob</a>
                </div>
                
                <!-- Navigation -->
                <nav class="hidden md:flex space-x-8">
                    <a href="/" class="text-gray-600 hover:text-blue-600">Ana Sayfa</a>
                    <a href="/jobs" class="text-gray-600 hover:text-blue-600">İş İlanları</a>
                    <a href="/companies" class="text-gray-600 hover:text-blue-600">Şirketler</a>
                </nav>
                
                <!-- Auth Buttons -->
                <div class="flex items-center space-x-4">
                    @if(session('user_id'))
                        <!-- Logged In User -->
                        <div class="flex items-center space-x-3">
                            <span class="text-gray-700">Merhaba, {{ session('user_name', 'Kullanıcı') }}</span>
                            <form action="/logout" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="text-red-600 hover:text-red-800">
                                    Çıkış Yap
                                </button>
                            </form>
                        </div>
                    @else
                        <!-- Not Logged In -->
                        <a href="/login" class="text-gray-600 hover:text-blue-600">Giriş Yap</a>
                        <a href="/register" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                            Üye Ol
                        </a>
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
    @if(session('user_id'))
    <!-- AI Chat Widget -->
    <livewire:a-i-chat-widget />
@else
    <!-- AI Chat for Guest Users -->
    <div class="fixed bottom-6 right-6 z-50">
        <button 
            onclick="showLoginPrompt()"
            class="bg-blue-600 hover:bg-blue-700 text-white rounded-full p-4 shadow-lg transition-all duration-200 transform hover:scale-105"
            title="AI İş Asistanı - Giriş Gerekli"
        >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-4l-4 4z"></path>
            </svg>
            <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                🔒
            </span>
        </button>
    </div>

    <script>
        function showLoginPrompt() {
            if (confirm('AI İş Asistanı için giriş yapmanız gerekiyor. Giriş sayfasına yönlendirilsin mi?')) {
                window.location.href = '/login';
            }
        }
    </script>
@endif
    <!-- Main Content -->
    <main>
        {{ $slot }}
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white mt-16">
        <div class="container mx-auto px-4 py-8">
            <div class="text-center">
                <p>&copy; 2025 MicroJob. Tüm hakları saklıdır.</p>
            </div>
        </div>
    </footer>

    <!-- Livewire Scripts -->
    @livewireScripts
</body>
</html>