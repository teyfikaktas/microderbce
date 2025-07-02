<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;  // ← Bunu ekleyin
class JobDetail extends Component
{
    public $jobId;
    public $job = null;
    public $relatedJobs = [];
    public $company = null;
    public $loading = true;
    public $error = null;
    public $showApplicationModal = false;
    public $hasUserApplied = false; // Yeni eklendi
    
    // Sadece cover letter (basitleştirilmiş)
    public $applicationData = [
        'cover_letter' => ''
    ];

    // API URLs
    private $apiUrl = 'https://job-search-api.elastic-swartz.213-238-168-122.plesk.page/api/v1';
    private $applicationApiUrl = 'https://job-apply.elastic-swartz.213-238-168-122.plesk.page/api/v1';

    public function mount($id)
    {
        $this->jobId = $id;
        $this->loadJobDetail();
        $this->loadRelatedJobs();
        $this->checkUserApplication(); // Yeni eklendi
    }

    public function loadJobDetail()
    {
        $this->loading = true;
        $this->error   = null;

        // 1) Cache anahtarını oluştur
        $cacheKey = "job_detail:{$this->jobId}";

        // 2) Eğer Redis’te varsa, doğrudan kullan
        if ($cached = Redis::get($cacheKey)) {
            $this->job     = json_decode($cached, true);
            $this->company = $this->job['company'] ?? null;
            $this->loading = false;
            return;
        }

        // 3) Cache’te yoksa API’dan çek
        try {
            $response = Http::timeout(10)->get($this->apiUrl . '/jobs/' . $this->jobId);
            if ($response->successful()) {
                $this->job     = $response->json();
                $this->company = $this->job['company'] ?? null;

                // 4) Redis’e kaydet (900 saniye = 15 dk)
                Redis::setex($cacheKey, 900, json_encode($this->job));
            } else {
                $this->error = 'İş ilanı bulunamadı.';
            }
        } catch (\Exception $e) {
            $this->error = 'Bağlantı hatası oluştu.';
        } finally {
            $this->loading = false;
        }
    }

    public function loadRelatedJobs()
    {
        try {
            $response = Http::timeout(5)->get($this->apiUrl . '/jobs/' . $this->jobId . '/related');

            if ($response->successful()) {
                $this->relatedJobs = $response->json();
            }
        } catch (\Exception $e) {
            $this->relatedJobs = [];
        }
    }

    public function checkUserApplication()
    {
        // Kullanıcı giriş yapmamışsa kontrol etme
        if (!session('user_id')) {
            $this->hasUserApplied = false;
            return;
        }

        try {
            // Job Application Service'den kullanıcının bu işe başvurup başvurmadığını kontrol et
            $response = Http::timeout(5)->get(
                $this->applicationApiUrl . '/applications/user/' . session('user_id')
            );

            if ($response->successful()) {
                $applications = $response->json()['data'] ?? [];
                
                // Bu job_id'ye başvuru var mı kontrol et
                $this->hasUserApplied = collect($applications)->contains(function ($application) {
                    return $application['job_posting_id'] == $this->jobId;
                });
            }
        } catch (\Exception $e) {
            $this->hasUserApplied = false;
        }
    }

    public function apply()
    {
        // Session kontrolü - Supabase auth
        if (!session('user_id') || !session('access_token')) {
            return redirect()->route('login')->with('message', 'Başvuru yapmak için giriş yapmalısınız.');
        }

        // Zaten başvurmuşsa
        if ($this->hasUserApplied) {
            session()->flash('info', 'Bu iş ilanına zaten başvurmuşsunuz.');
            return;
        }

        $this->showApplicationModal = true;
    }

    public function submitApplication()
    {
        // Session kontrolü
        if (!session('user_id')) {
            session()->flash('error', 'Oturum süreniz dolmuş. Lütfen tekrar giriş yapın.');
            return redirect()->route('login');
        }

        $this->validate([
            'applicationData.cover_letter' => 'required|min:50|max:2000',
        ], [
            'applicationData.cover_letter.required' => 'Kapak mektubu gereklidir.',
            'applicationData.cover_letter.min' => 'Kapak mektubu en az 50 karakter olmalıdır.',
            'applicationData.cover_letter.max' => 'Kapak mektubu en fazla 2000 karakter olabilir.',
        ]);

        try {
            $applicationData = [
                'job_posting_id' => (int)$this->jobId,
                'user_id' => session('user_id'),
                'cover_letter' => $this->applicationData['cover_letter'],
                'resume_path' => null
            ];

            // Job Application Service'e gönder
            $response = Http::timeout(10)->post(
                $this->applicationApiUrl . '/applications',
                $applicationData
            );

            if ($response->successful()) {
                $this->showApplicationModal = false;
                $this->reset('applicationData');
                $this->hasUserApplied = true; // Başvuru durumunu güncelle
                
                session()->flash('success', 'Başvurunuz başarıyla gönderildi! 🎉');
                
                // Application count'u güncelle
                if ($this->job) {
                    $this->job['application_count'] = ($this->job['application_count'] ?? 0) + 1;
                }
            } else {
                $error = $response->json();
                
                if ($response->status() === 409) {
                    $this->hasUserApplied = true; // Zaten başvurmuş
                    session()->flash('error', 'Bu iş ilanına daha önce başvuru yapmışsınız.');
                } elseif ($response->status() === 422) {
                    $errorMessages = collect($error['errors'] ?? [])->flatten()->implode(' ');
                    session()->flash('error', 'Validation hatası: ' . $errorMessages);
                } else {
                    session()->flash('error', $error['message'] ?? 'Başvuru gönderilirken bir hata oluştu.');
                }
            }

        } catch (\Exception $e) {
            session()->flash('error', 'Başvuru gönderilirken bir hata oluştu: ' . $e->getMessage());
        }
    }

    public function closeApplicationModal()
    {
        $this->showApplicationModal = false;
        $this->reset('applicationData');
    }

    public function goToJob($jobId)
    {
        return redirect()->route('job.detail', ['id' => $jobId]);
    }

    public function shareJob()
    {
        $this->dispatchBrowserEvent('job-shared');
    }

    public function saveJob()
    {
        if (!session('user_id')) {
            session()->flash('error', 'İş ilanını kaydetmek için giriş yapmalısınız.');
            return;
        }

        // TODO: Save job functionality
        session()->flash('success', 'İş ilanı kaydedildi!');
    }

    public function reportJob()
    {
        // TODO: Report job functionality
        session()->flash('info', 'Raporlama talebiniz alındı.');
    }

    public function getFormattedSalary()
    {
        if (!$this->job) return null;

        $min = $this->job['salary_min'] ?? null;
        $max = $this->job['salary_max'] ?? null;
        $currency = $this->job['currency'] ?? 'TRY';

        if ($min && $max) {
            return number_format($min) . ' - ' . number_format($max) . ' ' . $currency;
        } elseif ($min) {
            return number_format($min) . '+ ' . $currency;
        } elseif ($max) {
            return 'Max ' . number_format($max) . ' ' . $currency;
        }

        return 'Maaş belirtilmemiş';
    }

    public function getTimeAgo()
    {
        if (!$this->job || !isset($this->job['created_at'])) {
            return '';
        }

        try {
            $createdAt = \Carbon\Carbon::parse($this->job['created_at']);
            return $createdAt->diffForHumans();
        } catch (\Exception $e) {
            return '';
        }
    }

    public function render()
    {
        return view('livewire.job-detail')->layout('components.layout', [
            'title' => $this->job ? $this->job['title'] . ' - ' . ($this->company['name'] ?? '') : 'İş İlanı Detayı'
        ]);
    }
}