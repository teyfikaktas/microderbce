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
                                class="w-full bg-blue-600 text-white py-3 px-6 rounded-lg hover:bg-blue-700 transition duration-200 font-medium"
                            >
                                İş Ara
                            </button>
                        </div>
                    </div>
                </div>
            </div>
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
                        class="bg-white text-gray-700 px-4 py-2 rounded-full shadow-sm hover:shadow-md transition duration-200 text-sm"
                    >
                        {{ $search }}
                    </button>
                @endforeach
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
                    @else
                        Son İş İlanları
                    @endif
                </h2>
                <span class="text-gray-600">{{ count($jobs) }} ilan bulundu</span>
            </div>

            @if(count($jobs) > 0)
                <div class="grid gap-6">
                    @foreach($jobs as $job)
                        <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition duration-200">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h3 class="text-xl font-semibold text-gray-800 mb-2">
                                        {{ $job['title'] }}
                                    </h3>
                                    <p class="text-gray-600 mb-2">{{ $job['position'] }}</p>
                                    <div class="flex items-center text-sm text-gray-500 space-x-4">
                                        <span>📍 {{ $job['city'] }}, {{ $job['country'] }}</span>
                                        <span>💼 {{ ucfirst($job['work_type']) }}</span>
                                        <span>⭐ {{ ucfirst($job['experience_level']) }}</span>
                                    </div>
                                </div>
                                <div class="text-right">
                                    @if($job['salary_min'] && $job['salary_max'])
                                        <div class="text-lg font-semibold text-green-600">
                                            {{ number_format($job['salary_min']) }} - {{ number_format($job['salary_max']) }} ₺
                                        </div>
                                    @endif
                                    <div class="text-sm text-gray-500">
                                        {{ \Carbon\Carbon::parse($job['created_at'])->diffForHumans() }}
                                    </div>
                                </div>
                            </div>

                            <p class="text-gray-700 mb-4 line-clamp-3">
                                {{ Str::limit($job['description'], 200) }}
                            </p>

                            <div class="flex justify-between items-center">
                                <div class="flex items-center space-x-4 text-sm text-gray-500">
                                    <span>👥 {{ $job['application_count'] ?? 0 }} başvuru</span>
                                    <span>👁️ {{ $job['view_count'] ?? 0 }} görüntülenme</span>
                                </div>
                                <a href="/jobs/{{ $job['id'] }}" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition duration-200 inline-block text-center">
    Detaylar
</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12">
                    <div class="text-gray-400 text-6xl mb-4">📋</div>
                    <h3 class="text-xl font-semibold text-gray-600 mb-2">Henüz iş ilanı bulunamadı</h3>
                    <p class="text-gray-500">Farklı arama kriterleri deneyebilirsiniz.</p>
                </div>
            @endif
        </div>
    </section>
</div>