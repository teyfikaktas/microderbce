<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <div class="text-center">
                <h2 class="mt-6 text-3xl font-extrabold text-gray-900">
                    Yeni hesap oluşturun
                </h2>
                <p class="mt-2 text-sm text-gray-600">
                    Zaten hesabınız var mı?
                    <a href="/login" class="font-medium text-blue-600 hover:text-blue-500">
                        Giriş yapın
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
                    Hesap oluşturuluyor...
                </div>
            @endif

            <form wire:submit.prevent="register">
                <!-- Role Selection -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-3">
                        Hesap Türü
                    </label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="flex items-center p-3 border rounded-lg cursor-pointer {{ $role === 'user' ? 'border-blue-500 bg-blue-50' : 'border-gray-300' }}">
                            <input 
                                wire:model="role" 
                                type="radio" 
                                value="user" 
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                            >
                            <div class="ml-3">
                                <div class="text-sm font-medium text-gray-900">👤 İş Arayan</div>
                                <div class="text-xs text-gray-500">İş aramak için</div>
                            </div>
                        </label>
                        
                        <label class="flex items-center p-3 border rounded-lg cursor-pointer {{ $role === 'company' ? 'border-blue-500 bg-blue-50' : 'border-gray-300' }}">
                            <input 
                                wire:model="role" 
                                type="radio" 
                                value="company" 
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                            >
                            <div class="ml-3">
                                <div class="text-sm font-medium text-gray-900">🏢 Şirket</div>
                                <div class="text-xs text-gray-500">İlan vermek için</div>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="space-y-4">
                    <!-- Name -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">
                            {{ $role === 'company' ? 'Yetkili Adı Soyadı' : 'Ad Soyad' }}
                        </label>
                        <input 
                            wire:model="name"
                            id="name" 
                            name="name" 
                            type="text" 
                            autocomplete="name" 
                            required
                            class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm @error('name') border-red-500 @enderror" 
                            placeholder="Adınız ve soyadınız"
                        >
                        @error('name') 
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Company Name (if role is company) -->
                    @if($role === 'company')
                    <div>
                        <label for="company_name" class="block text-sm font-medium text-gray-700">
                            Şirket Adı
                        </label>
                        <input 
                            wire:model="company_name"
                            id="company_name" 
                            name="company_name" 
                            type="text" 
                            required
                            class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm @error('company_name') border-red-500 @enderror" 
                            placeholder="Şirket adınız"
                        >
                        @error('company_name') 
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    @endif

                    <!-- Email -->
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

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">
                            Şifre
                        </label>
                        <input 
                            wire:model="password"
                            id="password" 
                            name="password" 
                            type="password" 
                            autocomplete="new-password" 
                            required
                            class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm @error('password') border-red-500 @enderror" 
                            placeholder="En az 6 karakter"
                        >
                        @error('password') 
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Password Confirmation -->
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700">
                            Şifre Tekrarı
                        </label>
                        <input 
                            wire:model="password_confirmation"
                            id="password_confirmation" 
                            name="password_confirmation" 
                            type="password" 
                            autocomplete="new-password" 
                            required
                            class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm @error('password_confirmation') border-red-500 @enderror" 
                            placeholder="Şifrenizi tekrar giriniz"
                        >
                        @error('password_confirmation') 
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Terms and Conditions -->
                <div class="flex items-center mt-6">
                    <input 
                        id="accept-terms" 
                        name="accept-terms" 
                        type="checkbox" 
                        required
                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                    >
                    <label for="accept-terms" class="ml-2 block text-sm text-gray-900">
                        <a href="#" class="text-blue-600 hover:text-blue-500">Kullanım Şartları</a> ve 
                        <a href="#" class="text-blue-600 hover:text-blue-500">Gizlilik Politikası</a>'nı kabul ediyorum
                    </label>
                </div>

                <!-- Submit Button -->
                <div class="mt-6">
                    <button 
                        type="submit" 
                        wire:loading.attr="disabled"
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
                    >
                        <span wire:loading.remove>
                            @if($role === 'company')
                                🏢 Şirket Hesabı Oluştur
                            @else
                                👤 Hesap Oluştur
                            @endif
                        </span>
                        <span wire:loading>
                            ⏳ Hesap oluşturuluyor...
                        </span>
                    </button>
                </div>

                <!-- Login Link -->
                <div class="mt-6 text-center">
                    <p class="text-sm text-gray-600">
                        Zaten hesabınız var mı?
                        <a href="/login" class="font-medium text-blue-600 hover:text-blue-500">
                            Giriş yapın
                        </a>
                    </p>
                </div>
            </form>
        </div>
    </div>
</div>