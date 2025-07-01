<div>
    @if ($loading)
        <!-- Loading State -->
        <div class="min-h-screen flex items-center justify-center">
            <div class="animate-spin rounded-full h-32 w-32 border-b-2 border-blue-600"></div>
        </div>
    @elseif ($error)
        <!-- Error State -->
        <div class="min-h-screen flex items-center justify-center">
            <div class="text-center">
                <div class="text-red-500 text-6xl mb-4">❌</div>
                <h1 class="text-2xl font-bold text-gray-800 mb-4">{{ $error }}</h1>
                <a href="/" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700">
                    Ana Sayfaya Dön
                </a>
            </div>
        </div>
    @elseif ($job)
        <!-- Job Detail Content -->
        <div class="bg-gray-50 min-h-screen py-8">
            <div class="container mx-auto px-4">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    
                    <!-- Main Content (Left 2/3) -->
                    <div class="lg:col-span-2">
                        <!-- Job Header -->
                        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                            <div class="flex justify-between items-start mb-4">
                                <div class="flex-1">
                                    <h1 class="text-3xl font-bold text-gray-800 mb-2">{{ $job['title'] }}</h1>
                                    @if($company)
                                        <div class="flex items-center mb-3">
                                            @if($company['logo'])
                                                <img src="{{ $company['logo'] }}" alt="{{ $company['name'] }}" 
                                                     class="w-12 h-12 rounded-lg mr-3">
                                            @else
                                                <div class="w-12 h-12 bg-blue-600 rounded-lg flex items-center justify-center mr-3">
                                                    <span class="text-white font-bold text-lg">
                                                        {{ substr($company['name'], 0, 1) }}
                                                    </span>
                                                </div>
                                            @endif
                                            <div>
                                                <h3 class="text-xl font-semibold text-gray-800">{{ $company['name'] }}</h3>
                                                <p class="text-gray-600">{{ $company['industry'] ?? '' }}</p>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                                
                                <!-- Action Buttons -->
                                <div class="flex space-x-2 ml-4">
                                    <button wire:click="saveJob" 
                                            class="p-2 border border-gray-300 rounded-lg hover:bg-gray-50" 
                                            title="Kaydet">
                                        ❤️
                                    </button>
                                    <button wire:click="shareJob" 
                                            class="p-2 border border-gray-300 rounded-lg hover:bg-gray-50" 
                                            title="Paylaş">
                                        📤
                                    </button>
                                    <button wire:click="reportJob" 
                                            class="p-2 border border-gray-300 rounded-lg hover:bg-gray-50" 
                                            title="Rapor Et">
                                        🚨
                                    </button>
                                </div>
                            </div>

                            <!-- Job Meta Info -->
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                                <div class="text-center p-3 bg-gray-50 rounded-lg">
                                    <div class="text-2xl mb-1">📍</div>
                                    <div class="text-sm text-gray-600">Lokasyon</div>
                                    <div class="font-semibold">{{ $job['city'] }}, {{ $job['country'] }}</div>
                                </div>
                                <div class="text-center p-3 bg-gray-50 rounded-lg">
                                    <div class="text-2xl mb-1">💼</div>
                                    <div class="text-sm text-gray-600">Çalışma Şekli</div>
                                    <div class="font-semibold">{{ ucfirst($job['work_type']) }}</div>
                                </div>
                                <div class="text-center p-3 bg-gray-50 rounded-lg">
                                    <div class="text-2xl mb-1">⭐</div>
                                    <div class="text-sm text-gray-600">Deneyim</div>
                                    <div class="font-semibold">{{ ucfirst($job['experience_level']) }}</div>
                                </div>
                                <div class="text-center p-3 bg-gray-50 rounded-lg">
                                    <div class="text-2xl mb-1">💰</div>
                                    <div class="text-sm text-gray-600">Maaş</div>
                                    <div class="font-semibold text-green-600">{{ $this->getFormattedSalary() }}</div>
                                </div>
                            </div>

                            <!-- Apply Button -->
                            <div class="text-center">
                                <button wire:click="apply" 
                                        class="bg-blue-600 text-white px-8 py-3 rounded-lg hover:bg-blue-700 transition duration-200 text-lg font-semibold">
                                    🚀 Hemen Başvur
                                </button>
                                <div class="mt-2 text-sm text-gray-500">
                                    {{ $job['application_count'] ?? 0 }} kişi başvurdu • 
                                    {{ $this->getTimeAgo() }}
                                </div>
                            </div>
                        </div>

                        <!-- Job Description -->
                        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                            <h2 class="text-2xl font-bold text-gray-800 mb-4">İş Tanımı</h2>
                            <div class="prose max-w-none">
                                <p class="text-gray-700 leading-relaxed whitespace-pre-line">{{ $job['description'] }}</p>
                            </div>
                        </div>

                        <!-- Requirements -->
                        @if($job['requirements'])
                        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                            <h2 class="text-2xl font-bold text-gray-800 mb-4">Aranan Özellikler</h2>
                            <div class="prose max-w-none">
                                <p class="text-gray-700 leading-relaxed whitespace-pre-line">{{ $job['requirements'] }}</p>
                            </div>
                        </div>
                        @endif

                        <!-- Company Info -->
                        @if($company)
                        <div class="bg-white rounded-lg shadow-md p-6">
                            <h2 class="text-2xl font-bold text-gray-800 mb-4">Şirket Hakkında</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <h3 class="text-lg font-semibold mb-2">{{ $company['name'] }}</h3>
                                    <p class="text-gray-700 mb-4">{{ $company['description'] ?? '' }}</p>
                                    
                                    @if($company['website'])
                                    <a href="{{ $company['website'] }}" target="_blank" 
                                       class="text-blue-600 hover:text-blue-800 font-medium">
                                        🌐 Şirket Web Sitesi
                                    </a>
                                    @endif
                                </div>
                                <div class="space-y-3">
                                    @if($company['employee_count'])
                                    <div class="flex items-center">
                                        <span class="text-gray-600 w-24">Çalışan:</span>
                                        <span class="font-medium">{{ $company['employee_count'] }}</span>
                                    </div>
                                    @endif
                                    @if($company['industry'])
                                    <div class="flex items-center">
                                        <span class="text-gray-600 w-24">Sektör:</span>
                                        <span class="font-medium">{{ $company['industry'] }}</span>
                                    </div>
                                    @endif
                                    <div class="flex items-center">
                                        <span class="text-gray-600 w-24">Lokasyon:</span>
                                        <span class="font-medium">{{ $company['city'] }}, {{ $company['country'] }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>

                    <!-- Sidebar (Right 1/3) -->
                    <div class="lg:col-span-1">
                        <!-- Quick Apply Card -->
                        <div class="bg-white rounded-lg shadow-md p-6 mb-6 sticky top-4">
                            <h3 class="text-lg font-bold text-gray-800 mb-4">Hızlı Başvuru</h3>
                            <button wire:click="apply" 
                                    class="w-full bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 transition duration-200 font-semibold mb-4">
                                🚀 Başvur
                            </button>
                            
                            <div class="border-t pt-4">
                                <div class="flex justify-between text-sm text-gray-600 mb-2">
                                    <span>Başvuru Sayısı:</span>
                                    <span class="font-medium">{{ $job['application_count'] ?? 0 }}</span>
                                </div>
                                <div class="flex justify-between text-sm text-gray-600">
                                    <span>Yayın Tarihi:</span>
                                    <span class="font-medium">{{ $this->getTimeAgo() }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Related Jobs -->
                        @if(count($relatedJobs) > 0)
                        <div class="bg-white rounded-lg shadow-md p-6">
                            <h3 class="text-lg font-bold text-gray-800 mb-4">İlgili İş İlanları</h3>
                            <div class="space-y-4">
                                @foreach($relatedJobs as $relatedJob)
                                <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition cursor-pointer"
                                     wire:click="goToJob({{ $relatedJob['id'] }})">
                                    <h4 class="font-semibold text-gray-800 mb-1">{{ $relatedJob['title'] }}</h4>
                                    @if(isset($relatedJob['company']))
                                    <p class="text-sm text-gray-600 mb-2">{{ $relatedJob['company']['name'] }}</p>
                                    @endif
                                    <div class="flex justify-between text-sm text-gray-500">
                                        <span>📍 {{ $relatedJob['city'] }}</span>
                                        <span>💼 {{ ucfirst($relatedJob['work_type']) }}</span>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Application Modal -->
        @if($showApplicationModal)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" 
             wire:click="closeApplicationModal">
            <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4" wire:click.stop>
                <h3 class="text-xl font-bold text-gray-800 mb-4">Başvuru Formu</h3>
                
                <form wire:submit.prevent="submitApplication">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Ad Soyad</label>
                            <input type="text" wire:model="applicationData.name" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            @error('applicationData.name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">E-posta</label>
                            <input type="email" wire:model="applicationData.email" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            @error('applicationData.email') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Telefon</label>
                            <input type="tel" wire:model="applicationData.phone" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            @error('applicationData.phone') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Kapak Mektubu</label>
                            <textarea wire:model="applicationData.cover_letter" rows="4"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                      placeholder="Neden bu pozisyon için uygun olduğunuzu açıklayın..."></textarea>
                            @error('applicationData.cover_letter') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" wire:click="closeApplicationModal" 
                                class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                            İptal
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            Başvuruyu Gönder
                        </button>
                    </div>
                </form>
            </div>
        </div>
        @endif
    @endif

    <!-- Flash Messages -->
    @if (session()->has('success'))
        <div class="fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50">
            {{ session('success') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50">
            {{ session('error') }}
        </div>
    @endif

    <script>
        // Handle job sharing
        window.addEventListener('job-shared', () => {
            navigator.clipboard.writeText(window.location.href);
            alert('İş ilanı bağlantısı kopyalandı!');
        });

        // Auto-hide flash messages
        setTimeout(() => {
            document.querySelectorAll('.fixed.top-4.right-4').forEach(el => el.remove());
        }, 5000);
    </script>
</div>