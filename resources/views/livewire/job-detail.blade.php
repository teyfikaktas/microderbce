<div>
    <!-- Hero Section -->
    <section class="bg-gradient-to-r from-blue-600 to-blue-800 text-white py-16">
        <div class="container mx-auto px-4">
            <div class="text-center mb-8">
                <h1 class="text-4xl md:text-5xl font-bold mb-4">
                    Hayalinizdeki İşi Bulun
                </h1>
                <p class="text-xl text-blue-100">
                    Binlerce iş ilanı arasından size uygun olanı keşfedin
                </p>
            </div>

            <!-- Search Form -->
            <div class="max-w-4xl mx-auto">
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Position Search -->
                        <div class="relative">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Pozisyon
                            </label>
                            <input 
                                type="text" 
                                wire:model="searchPosition"
                                placeholder="Web Developer, Frontend..."
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-gray-900"
                            >
                        </div>

                        <!-- City Search -->
                        <div class="relative">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Şehir
                            </label>
                            <input 
                                type="text" 
                                wire:model="searchCity"
                                placeholder="İstanbul, Ankara..."
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-gray-900"
                            >
                        </div>

                        <!-- Search Button -->
                        <div class="flex items-end">
                            <button 
                                wire:click="search"
                                wire:loading.attr="disabled"
                                wire:loading.class="opacity-50"
                                class="w-full bg-blue-600 text-white py-3 px-6 rounded-lg hover:bg-blue-700 transition duration-200 font-medium disabled:opacity-50"
                            >
                                <span wire:loading.remove>İş Ara</span>
                                <span wire:loading>Aranıyor...</span>
                            </button>
                        </div>
                    </div>

                    <!-- Clear Search Button -->
                    @if(!empty($searchPosition) || !empty($searchCity))
                    <div class="mt-4 text-center">
                        <button 
                            wire:click="clearSearch"
                            class="text-blue-600 hover:text-blue-800 text-sm font-medium"
                        >
                            Aramayı Temizle
                        </button>
                    </div>
                    @endif
                </div>
            </div>

            <!-- User Info -->
            @if(isset($userInfo))
            <div class="max-w-4xl mx-auto mt-4 text-center">
                <div class="bg-blue-700 bg-opacity-50 rounded-lg p-4 text-blue-100">
                    @if($userInfo['is_authenticated'])
                        <p>Hoş geldin, <strong>{{ $userInfo['user_name'] }}</strong>! 👋</p>
                        <p class="text-sm">Şehir: {{ $userInfo['user_city'] }}</p>
                    @else
                        <p>Misafir kullanıcı olarak görüntülüyorsunuz</p>
                        <a href="/login" class="text-blue-200 hover:text-white underline">Giriş yapın</a>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </section>

    <!-- Recent Searches -->
    @if(count($recentSearches) > 0)
    <section class="py-8 bg-gray-100">
        <div class="container mx-auto px-4">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Son Aramalarım</h2>
            <div class="flex flex-wrap gap-2">
                @foreach($recentSearches as $search)
                    <button 
                        wire:click="selectRecentSearch('{{ $search }}')"
                        class="bg-white text-gray-700 px-4 py-2 rounded-full shadow-sm hover:shadow-md transition duration-200 text-sm hover:bg-blue-50"
                    >
                        {{ $search }}
                    </button>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    <!-- Loading State -->
    <div wire:loading class="fixed inset-0 bg-black bg-opacity-25 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 shadow-lg">
            <div class="flex items-center space-x-3">
                <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
                <span>Yükleniyor...</span>
            </div>
        </div>
    </div>

    <!-- Error Message -->
    @if($error)
    <section class="py-4">
        <div class="container mx-auto px-4">
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg">
                <strong>Hata:</strong> {{ $error }}
            </div>
        </div>
    </section>
    @endif

    <!-- Job Listings -->
    <section class="py-12">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center mb-8">
                <h2 class="text-2xl font-bold text-gray-800">
                    @if(!empty($searchPosition) || !empty($searchCity))
                        Arama Sonuçları
                        @if(!empty($searchPosition) && !empty($searchCity))
                            - {{ $searchPosition }} ({{ $searchCity }})
                        @elseif(!empty($searchPosition))
                            - {{ $searchPosition }}
                        @elseif(!empty($searchCity))
                            - {{ $searchCity }}
                        @endif
                    @else
                        Son İş İlanları
                    @endif
                </h2>
                <span class="text-gray-600">{{ count($jobs) }} ilan bulundu</span>
            </div>

            @if(count($jobs) > 0)
                <div class="grid gap-6">
                    @foreach($jobs as $job)
                        <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition duration-200 border border-gray-200">
                            <div class="flex justify-between items-start mb-4">
                                <div class="flex-1">
                                    <h3 class="text-xl font-semibold text-gray-800 mb-2">
                                        {{ $job['position'] ?? 'Pozisyon Belirtilmemiş' }}
                                    </h3>
                                    
                                    @if(isset($job['company_name']))
                                    <p class="text-lg text-blue-600 font-medium mb-2">
                                        {{ $job['company_name'] }}
                                    </p>
                                    @endif

                                    <div class="flex flex-wrap items-center text-sm text-gray-500 space-x-4 mb-2">
                                        <span class="flex items-center">
                                            📍 {{ $job['city'] ?? 'Şehir belirtilmemiş' }}
                                        </span>
                                        
                                        @if(isset($job['work_type']))
                                        <span class="flex items-center">
                                            💼 {{ ucfirst($job['work_type']) }}
                                        </span>
                                        @endif
                                        
                                        @if(isset($job['experience_level']))
                                        <span class="flex items-center">
                                            ⭐ {{ ucfirst($job['experience_level']) }}
                                        </span>
                                        @endif
                                    </div>

                                    @if(isset($job['requirements']) && !empty($job['requirements']))
                                    <div class="flex flex-wrap gap-1 mt-2">
                                        @php
                                            $requirements = is_string($job['requirements']) ? 
                                                explode(',', $job['requirements']) : 
                                                (is_array($job['requirements']) ? $job['requirements'] : []);
                                        @endphp
                                        @foreach(array_slice($requirements, 0, 3) as $req)
                                            <span class="bg-gray-100 text-gray-600 px-2 py-1 rounded text-xs">
                                                {{ trim($req) }}
                                            </span>
                                        @endforeach
                                        @if(count($requirements) > 3)
                                            <span class="bg-gray-100 text-gray-600 px-2 py-1 rounded text-xs">
                                                +{{ count($requirements) - 3 }} daha
                                            </span>
                                        @endif
                                    </div>
                                    @endif
                                </div>

                                <div class="text-right ml-4">
                                    @if(isset($job['salary_min']) && isset($job['salary_max']) && $job['salary_min'] && $job['salary_max'])
                                        <div class="text-lg font-semibold text-green-600 mb-1">
                                            {{ number_format($job['salary_min']) }} - {{ number_format($job['salary_max']) }} ₺
                                        </div>
                                    @elseif(isset($job['salary_min']) && $job['salary_min'])
                                        <div class="text-lg font-semibold text-green-600 mb-1">
                                            {{ number_format($job['salary_min']) }}+ ₺
                                        </div>
                                    @endif
                                    
                                    <div class="text-sm text-gray-500">
                                        {{ isset($job['created_at']) ? \Carbon\Carbon::parse($job['created_at'])->diffForHumans() : 'Tarih belirtilmemiş' }}
                                    </div>
                                </div>
                            </div>

                            @if(isset($job['description']))
                            <p class="text-gray-700 mb-4 line-clamp-3">
                                {{ Str::limit($job['description'], 250) }}
                            </p>
                            @endif

                            <div class="flex justify-between items-center pt-4 border-t border-gray-100">
                                <div class="flex items-center space-x-4 text-sm text-gray-500">
                                    <span class="flex items-center">
                                        👥 {{ $job['application_count'] ?? 0 }} başvuru
                                    </span>
                                    <span class="flex items-center">
                                        👁️ {{ $job['view_count'] ?? 0 }} görüntülenme
                                    </span>
                                    @if(isset($job['is_remote']) && $job['is_remote'])
                                    <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs font-medium">
                                        🏠 Remote
                                    </span>
                                    @endif
                                </div>
                                
                                <div class="flex space-x-2">
                                    <a href="/jobs/{{ $job['id'] }}" 
                                       class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition duration-200 text-sm font-medium">
                                        Detaylar
                                    </a>
                                    @if(isset($userInfo) && $userInfo['is_authenticated'])
                                    <button class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition duration-200 text-sm font-medium">
                                        Başvur
                                    </button>
                                    @else
                                    <a href="/login" 
                                       class="bg-gray-600 text-white px-6 py-2 rounded-lg hover:bg-gray-700 transition duration-200 text-sm font-medium">
                                        Başvur
                                    </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Load More Button -->
                @if(count($jobs) >= 10)
                <div class="text-center mt-8">
                    <button class="bg-blue-600 text-white px-8 py-3 rounded-lg hover:bg-blue-700 transition duration-200 font-medium">
                        Daha Fazla İlan Göster
                    </button>
                </div>
                @endif

            @else
                <div class="text-center py-12">
                    <div class="text-gray-400 text-6xl mb-4">📋</div>
                    <h3 class="text-xl font-semibold text-gray-600 mb-2">
                        @if(!empty($searchPosition) || !empty($searchCity))
                            Arama kriterlerinize uygun iş ilanı bulunamadı
                        @else
                            Henüz iş ilanı bulunamadı
                        @endif
                    </h3>
                    <p class="text-gray-500 mb-4">
                        @if(!empty($searchPosition) || !empty($searchCity))
                            Farklı arama kriterleri deneyebilir veya filtreleri gevşetebilirsiniz.
                        @else
                            İş ilanları yükleniyor veya henüz ilan eklenmemiş.
                        @endif
                    </p>
                    @if(!empty($searchPosition) || !empty($searchCity))
                    <button 
                        wire:click="clearSearch"
                        class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition duration-200"
                    >
                        Tüm İlanları Göster
                    </button>
                    @endif
                </div>
            @endif
        </div>
    </section>
</div>

@push('styles')
<style>
    .line-clamp-3 {
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>
@endpush