<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Http;

class NewJobAlerts extends Component
{
    public $alerts = [];         // Ekrana gelen yeni ilanlar
    public $lastChecked;         // En son ne zamana kadar baktık

    // Job Search Service Base URL
    private $apiUrl = 'https://job-search-api.elastic-swartz.213-238-168-122.plesk.page/api/v1';

    public function mount()
    {
        // Başlangıçta şimdiki zamanı alıyoruz; bu tarihten sonra eklenen ilanlara bakacağız
        $this->lastChecked = now()->toISOString();
    }

    /**
     * 10 saniyede bir tetiklenecek
     */
    public function pollNewJobs()
    {
        try {
            // created_at > lastChecked filter ile sadece son eklenenleri al
            $response = Http::timeout(5)->get($this->apiUrl . '/jobs', [
                'created_at' => "gt.{$this->lastChecked}", 
                'is_active'  => 'eq.true',
                'order'      => 'created_at.asc', 
                'limit'      => 10
            ]);

            if ($response->successful()) {
                $jobs = $response->json()['data'] ?? [];

                foreach ($jobs as $job) {
                    // Her ilanı sadece bir kez ekle
                    if (!collect($this->alerts)->contains('id', $job['id'])) {
                        $this->alerts[] = $job;
                    }

                    // lastChecked’i en son eklenenin zamanına çek
                    $createdAt = $job['created_at'];
                    if ($createdAt > $this->lastChecked) {
                        $this->lastChecked = $createdAt;
                    }
                }
            }
        } catch (\Exception $e) {
            // Hata sessiz geç
        }
    }

    /**
     * Kullanıcı bildirime tıkladığında detay sayfasına yönlendir
     */
    public function goToJob($id)
    {
        return redirect()->route('job.detail', ['id' => $id]);
    }

    public function render()
    {
        return view('livewire.new-job-alerts');
    }
}
