{{-- resources/views/admin/jobs/index.blade.php --}}
<x-admin-layout 
    :title="'İş İlanları Yönetimi'"
    :pageTitle="'İş İlanları'"
    :breadcrumbs="[
        ['title' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['title' => 'İş İlanları', 'url' => '#']
    ]"
>
    <div class="space-y-6">
        <!-- Header Actions -->
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-xl font-semibold text-gray-900">Tüm İş İlanları</h2>
                <p class="text-gray-600">Sistemdeki tüm iş ilanlarını yönetin</p>
            </div>
            <a href="{{ route('admin.jobs.create') }}" 
               class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center transition-colors">
                <i class="fas fa-plus mr-2"></i>
                Yeni İş İlanı
            </a>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="bg-white p-6 rounded-lg shadow-sm border">
                <div class="flex items-center">
                    <div class="p-2 bg-blue-100 rounded-lg">
                        <i class="fas fa-briefcase text-blue-600"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-2xl font-bold text-gray-900">{{ count($jobs ?? []) }}</h3>
                        <p class="text-gray-600 text-sm">Toplam İlan</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow-sm border">
                <div class="flex items-center">
                    <div class="p-2 bg-green-100 rounded-lg">
                        <i class="fas fa-eye text-green-600"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-2xl font-bold text-gray-900">
                            {{ collect($jobs ?? [])->where('is_active', true)->count() }}
                        </h3>
                        <p class="text-gray-600 text-sm">Aktif İlan</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow-sm border">
                <div class="flex items-center">
                    <div class="p-2 bg-gray-100 rounded-lg">
                        <i class="fas fa-pause text-gray-600"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-2xl font-bold text-gray-900">
                            {{ collect($jobs ?? [])->where('is_active', false)->count() }}
                        </h3>
                        <p class="text-gray-600 text-sm">Pasif İlan</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow-sm border">
                <div class="flex items-center">
                    <div class="p-2 bg-yellow-100 rounded-lg">
                        <i class="fas fa-clock text-yellow-600"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-2xl font-bold text-gray-900">
                            {{ collect($jobs ?? [])->filter(function($job) {
                                return isset($job['created_at']) && 
                                       \Carbon\Carbon::parse($job['created_at'])->isToday();
                            })->count() }}
                        </h3>
                        <p class="text-gray-600 text-sm">Bugün Eklenen</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Jobs Table -->
        <div class="bg-white rounded-lg shadow-sm border">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">İş İlanları Listesi</h3>
            </div>
            
            @if(empty($jobs))
                <div class="p-12 text-center">
                    <i class="fas fa-briefcase text-gray-300 text-6xl mb-4"></i>
                    <h3 class="text-xl text-gray-500 mb-2">Henüz iş ilanı yok</h3>
                    <p class="text-gray-400 mb-6">İlk iş ilanınızı oluşturmak için aşağıdaki butona tıklayın</p>
                    <a href="{{ route('admin.jobs.create') }}" 
                       class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg inline-flex items-center">
                        <i class="fas fa-plus mr-2"></i>
                        İlk İlanı Oluştur
                    </a>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    İş İlanı
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Şirket
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Lokasyon
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Maaş
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Durum
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Tarih
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    İşlemler
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($jobs as $job)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ $job['title'] ?? 'N/A' }}
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                {{ $job['position'] ?? 'N/A' }}
                                            </div>
                                            <div class="text-xs text-gray-400">
                                                {{ ucfirst($job['work_type'] ?? 'N/A') }} • 
                                                {{ ucfirst($job['experience_level'] ?? 'N/A') }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            Şirket ID: {{ $job['company_id'] ?? 'N/A' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            {{ $job['city'] ?? 'N/A' }}, {{ $job['country'] ?? 'N/A' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            @if(isset($job['salary_min']) && isset($job['salary_max']))
                                                {{ number_format($job['salary_min']) }} - {{ number_format($job['salary_max']) }} {{ $job['currency'] ?? 'TRY' }}
                                            @elseif(isset($job['salary_min']))
                                                {{ number_format($job['salary_min']) }}+ {{ $job['currency'] ?? 'TRY' }}
                                            @else
                                                <span class="text-gray-400">Belirtilmemiş</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($job['is_active'] ?? false)
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                                <i class="fas fa-eye mr-1"></i>Aktif
                                            </span>
                                        @else
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                                <i class="fas fa-eye-slash mr-1"></i>Pasif
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        @if(isset($job['created_at']))
                                            {{ \Carbon\Carbon::parse($job['created_at'])->format('d.m.Y') }}
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex items-center justify-end space-x-2">
                                            <a href="{{ route('admin.jobs.edit', $job['id']) }}" 
                                               class="text-blue-600 hover:text-blue-900 p-1 rounded"
                                               title="Düzenle">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            
                                            <form action="{{ route('admin.jobs.destroy', $job['id']) }}" 
                                                  method="POST" 
                                                  class="inline"
                                                  onsubmit="return confirm('Bu iş ilanını silmek istediğinizden emin misiniz?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" 
                                                        class="text-red-600 hover:text-red-900 p-1 rounded"
                                                        title="Sil">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-admin-layout>