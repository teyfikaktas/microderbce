{{-- resources/views/admin/jobs/create.blade.php --}}
<x-admin-layout 
    :title="'Yeni İş İlanı Oluştur'"
    :pageTitle="'İş İlanı Oluştur'"
    :breadcrumbs="[
        ['title' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['title' => 'İş İlanları', 'url' => route('admin.jobs.index')],
        ['title' => 'Yeni İlan', 'url' => '#']
    ]"
>
    <div class="max-w-4xl mx-auto">
        <!-- Form Card -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                    <i class="fas fa-plus-circle text-blue-600 mr-2"></i>
                    Yeni İş İlanı Bilgileri
                </h2>
                <p class="text-gray-600 text-sm mt-1">Tüm alanları eksiksiz doldurun</p>
            </div>

            <form action="{{ route('admin.jobs.store') }}" method="POST" class="p-6 space-y-6">
                @csrf
                
                <!-- Company Selection -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-building text-gray-400 mr-1"></i>
                            Şirket Seç *
                        </label>
                        <select name="company_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Şirket Seçin...</option>
                            {{-- Bu kısım controller'dan gelecek companies listesi --}}
                            @foreach($companies ?? [] as $company)
                                <option value="{{ $company['id'] }}">{{ $company['name'] }}</option>
                            @endforeach
                        </select>
                        @error('company_id')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-briefcase text-gray-400 mr-1"></i>
                            İş Pozisyonu *
                        </label>
                        <input type="text" name="title" value="{{ old('title') }}" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="Örn: Frontend Developer">
                        @error('title')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-user-tie text-gray-400 mr-1"></i>
                        Pozisyon *
                    </label>
                    <input type="text" name="position" value="{{ old('position') }}" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Örn: Senior Developer">
                    @error('position')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Job Details -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-clock text-gray-400 mr-1"></i>
                            Çalışma Türü *
                        </label>
                        <select name="work_type" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Seçin...</option>
                            <option value="fulltime" {{ old('work_type') == 'fulltime' ? 'selected' : '' }}>Tam Zamanlı</option>
                            <option value="parttime" {{ old('work_type') == 'parttime' ? 'selected' : '' }}>Yarı Zamanlı</option>
                            <option value="contract" {{ old('work_type') == 'contract' ? 'selected' : '' }}>Sözleşmeli</option>
                            <option value="internship" {{ old('work_type') == 'internship' ? 'selected' : '' }}>Staj</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-map-marker-alt text-gray-400 mr-1"></i>
                            Şehir *
                        </label>
                        <input type="text" name="city" value="{{ old('city') }}" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="İstanbul">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-globe text-gray-400 mr-1"></i>
                            Ülke *
                        </label>
                        <input type="text" name="country" value="{{ old('country', 'Turkey') }}" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="Turkey">
                    </div>
                </div>

                <!-- Salary -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-lira-sign text-gray-400 mr-1"></i>
                            Minimum Maaş
                        </label>
                        <input type="number" name="salary_min" value="{{ old('salary_min') }}" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="15000">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-lira-sign text-gray-400 mr-1"></i>
                            Maksimum Maaş
                        </label>
                        <input type="number" name="salary_max" value="{{ old('salary_max') }}" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="25000">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-money-bill text-gray-400 mr-1"></i>
                            Para Birimi *
                        </label>
                        <select name="currency" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="TRY" {{ old('currency', 'TRY') == 'TRY' ? 'selected' : '' }}>TRY (₺)</option>
                            <option value="USD" {{ old('currency') == 'USD' ? 'selected' : '' }}>USD ($)</option>
                            <option value="EUR" {{ old('currency') == 'EUR' ? 'selected' : '' }}>EUR (€)</option>
                        </select>
                    </div>
                </div>

                <!-- Experience -->
                <div class="grid grid-cols-1 md:grid-cols-1 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-user-tie text-gray-400 mr-1"></i>
                            Deneyim Seviyesi *
                        </label>
                        <select name="experience_level" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Seçin...</option>
                            <option value="junior" {{ old('experience_level') == 'junior' ? 'selected' : '' }}>Junior (0-2 yıl)</option>
                            <option value="mid" {{ old('experience_level') == 'mid' ? 'selected' : '' }}>Mid Level (2-5 yıl)</option>
                            <option value="senior" {{ old('experience_level') == 'senior' ? 'selected' : '' }}>Senior (5+ yıl)</option>
                            <option value="lead" {{ old('experience_level') == 'lead' ? 'selected' : '' }}>Lead/Manager</option>
                        </select>
                    </div>
                </div>

                <!-- Job Description -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-align-left text-gray-400 mr-1"></i>
                        İş Tanımı *
                    </label>
                    <textarea name="description" rows="6" required 
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                              placeholder="İş pozisyonunun detaylı açıklamasını yazın...">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Requirements -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-list-check text-gray-400 mr-1"></i>
                        Gereksinimler *
                    </label>
                    <textarea name="requirements" rows="5" required 
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                              placeholder="• Minimum 3 yıl React deneyimi&#10;• JavaScript, HTML, CSS konularında uzman&#10;• Git kullanımı...">{{ old('requirements') }}</textarea>
                    @error('requirements')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Benefits -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-gift text-gray-400 mr-1"></i>
                        Sağlanan İmkanlar
                    </label>
                    <textarea name="benefits" rows="4" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                              placeholder="• Sağlık sigortası&#10;• Esnek çalışma saatleri&#10;• Eğitim bütçesi...">{{ old('benefits') }}</textarea>
                </div>

                <!-- Application Settings -->
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">İlan Ayarları</h3>
                    
                    <div class="mt-4">
                        <label class="flex items-center">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700">
                                <i class="fas fa-eye text-green-500 mr-1"></i>
                                İlan aktif olsun
                            </span>
                        </label>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                    <a href="{{ route('admin.jobs.index') }}" 
                       class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                        <i class="fas fa-times mr-2"></i>İptal
                    </a>
                    <button type="submit"
                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-save mr-2"></i>İş İlanı Oluştur
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>