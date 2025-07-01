<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <div class="text-center">
                <h2 class="mt-6 text-3xl font-extrabold text-gray-900">
                    Hesabınıza giriş yapın
                </h2>
                <p class="mt-2 text-sm text-gray-600">
                    Veya
                    <a href="/register" class="font-medium text-blue-600 hover:text-blue-500">
                        yeni hesap oluşturun
                    </a>
                </p>
            </div>
        </div>
        
        <div class="mt-8 space-y-6">
            <!-- Error Message -->
            @if($error)
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    {{ $error }}
                </div>
            @endif

            <!-- Loading State -->
            @if($loading)
                <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded relative" role="alert">
                    Giriş yapılıyor...
                </div>
            @endif

            <form wire:submit.prevent="login">
                <div class="space-y-4">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">
                            E-posta adresi
                        </label>
                        <input 
                            wire:model="email"
                            id="email" 
                            name="email" 
                            type="email" 
                            autocomplete="email" 
                            required
                            class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm @error('email') border-red-500 @enderror" 
                            placeholder="ornek@email.com"
                        >
                        @error('email') 
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">
                            Şifre
                        </label>
                        <input 
                            wire:model="password"
                            id="password" 
                            name="password" 
                            type="password" 
                            autocomplete="current-password" 
                            required
                            class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm @error('password') border-red-500 @enderror" 
                            placeholder="••••••••"
                        >
                        @error('password') 
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="flex items-center justify-between mt-6">
                    <div class="flex items-center">
                        <input 
                            id="remember-me" 
                            name="remember-me" 
                            type="checkbox" 
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                        >
                        <label for="remember-me" class="ml-2 block text-sm text-gray-900">
                            Beni hatırla
                        </label>
                    </div>

                    <div class="text-sm">
                        <a href="#" class="font-medium text-blue-600 hover:text-blue-500">
                            Şifrenizi mi unuttunuz?
                        </a>
                    </div>
                </div>

                <div class="mt-6">
                    <button 
                        type="submit" 
                        wire:loading.attr="disabled"
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
                    >
                        <span wire:loading.remove>
                            🔐 Giriş Yap
                        </span>
                        <span wire:loading>
                            ⏳ Giriş yapılıyor...
                        </span>
                    </button>
                </div>

                <div class="mt-6">
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-300"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-2 bg-gray-50 text-gray-500">Veya</span>
                        </div>
                    </div>

                    <div class="mt-6 grid grid-cols-2 gap-3">
                        <a href="/register" class="w-full inline-flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                            👤 Kullanıcı Kayıt
                        </a>
                        <a href="/company-register" class="w-full inline-flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                            🏢 Şirket Kayıt
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>