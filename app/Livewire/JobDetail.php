<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;

class JobDetail extends Component
{
    public $jobId;
    public $job = [];
    public $company = [];
    public $relatedJobs = [];
    public $loading = true;
    public $error = '';
    public $showApplicationModal = false;
    public $hasUserApplied = false;
    public $recentSearches = [];
    public $jobs = [];

    public $applicationData = [
        'cover_letter' => ''
    ];

    private $apiUrl            = 'https://job-search-api.elastic-swartz.213-238-168-122.plesk.page/api/v1';
    private $applicationApiUrl = 'https://job-apply.elastic-swartz.213-238-168-122.plesk.page/api/v1';

    public function mount($id)
    {
        $this->jobId = $id;
        $this->loadJobDetail();
        $this->loadRelatedJobs();
        $this->checkUserApplication();

        // Eğer bir liste göstermek istersen:
        $this->jobs = $this->relatedJobs;
    }

    public function loadJobDetail()
    {
        $this->loading = true;
        $this->error   = '';

        $cacheKey = "job_detail:{$this->jobId}";

        // 1) Cache'te varsa döndür
        if ($cached = Redis::get($cacheKey)) {
            $this->job     = json_decode($cached, true) ?: [];
            $this->company = $this->job['company'] ?? [];
            $this->loading = false;
            return;
        }

        // 2) Cache yoksa API'dan çek
        try {
            $response = Http::timeout(10)
                ->get("{$this->apiUrl}/jobs/{$this->jobId}");

            if ($response->successful()) {
                $this->job     = $response->json() ?: [];
                $this->company = $this->job['company'] ?? [];

                // 3) Cache'e kaydet (15 dk)
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
        $cacheKey = "related_jobs:{$this->jobId}";

        // 1) Cache'te varsa kullan
        if ($cached = Redis::get($cacheKey)) {
            $this->relatedJobs = json_decode($cached, true) ?: [];
            return;
        }

        // 2) Yoksa API'dan çek
        try {
            $response = Http::timeout(5)
                ->get("{$this->apiUrl}/jobs/{$this->jobId}/related");

            if ($response->successful()) {
                $this->relatedJobs = $response->json() ?: [];
                // 3) Cache'e kaydet (5 dk)
                Redis::setex($cacheKey, 300, json_encode($this->relatedJobs));
            } else {
                $this->relatedJobs = [];
            }
        } catch (\Exception $e) {
            $this->relatedJobs = [];
        }
    }

    public function checkUserApplication()
    {
        if (!session('user_id')) {
            $this->hasUserApplied = false;
            return;
        }

        try {
            $response = Http::timeout(5)
                ->get("{$this->applicationApiUrl}/applications/user/" . session('user_id'));

            if ($response->successful()) {
                $apps = $response->json()['data'] ?? [];
                $this->hasUserApplied = collect($apps)
                    ->contains(fn($app) => ($app['job_posting_id'] ?? null) == $this->jobId);
            } else {
                $this->hasUserApplied = false;
            }
        } catch (\Exception $e) {
            $this->hasUserApplied = false;
        }
    }

    public function apply()
    {
        if (!session('user_id') || !session('access_token')) {
            return redirect()->route('login')
                ->with('message', 'Başvuru yapmak için giriş yapmalısınız.');
        }

        if ($this->hasUserApplied) {
            session()->flash('info', 'Bu iş ilanına zaten başvurmuşsunuz.');
            return;
        }

        $this->showApplicationModal = true;
    }

    public function submitApplication()
    {
        if (!session('user_id')) {
            session()->flash('error', 'Oturum süreniz dolmuş. Lütfen tekrar giriş yapın.');
            return redirect()->route('login');
        }

        $this->validate([
            'applicationData.cover_letter' => 'required|min:50|max:2000',
        ], [
            'applicationData.cover_letter.required' => 'Kapak mektubu gereklidir.',
            'applicationData.cover_letter.min'      => 'Kapak mektubu en az 50 karakter olmalıdır.',
            'applicationData.cover_letter.max'      => 'Kapak mektubu en fazla 2000 karakter olabilir.',
        ]);

        try {
            $payload = [
                'job_posting_id' => (int)$this->jobId,
                'user_id'        => session('user_id'),
                'cover_letter'   => $this->applicationData['cover_letter'],
                'resume_path'    => null,
            ];

            $response = Http::timeout(10)
                ->post("{$this->applicationApiUrl}/applications", $payload);

            if ($response->successful()) {
                $this->showApplicationModal = false;
                $this->reset('applicationData');
                $this->hasUserApplied = true;
                session()->flash('success', 'Başvurunuz başarıyla gönderildi! 🎉');

                // Görüntüleme ve başvuru sayısını güncelle
                $this->job['application_count'] = ($this->job['application_count'] ?? 0) + 1;
            } else {
                if ($response->status() === 409) {
                    $this->hasUserApplied = true;
                    session()->flash('error', 'Bu iş ilanına daha önce başvuru yapmışsınız.');
                } elseif ($response->status() === 422) {
                    $errs = collect($response->json('errors', []))->flatten()->implode(' ');
                    session()->flash('error', "Validation hatası: {$errs}");
                } else {
                    session()->flash('error', $response->json('message', 'Başvuru gönderilirken bir hata oluştu.'));
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

        session()->flash('success', 'İş ilanı kaydedildi!');
    }

    public function reportJob()
    {
        session()->flash('info', 'Raporlama talebiniz alındı.');
    }

    public function getFormattedSalary()
    {
        if (empty($this->job)) return null;

        $min      = $this->job['salary_min'] ?? null;
        $max      = $this->job['salary_max'] ?? null;
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
        if (empty($this->job['created_at'])) {
            return '';
        }

        try {
            return \Carbon\Carbon::parse($this->job['created_at'])->diffForHumans();
        } catch (\Exception $e) {
            return '';
        }
    }

    public function render()
    {
        return view('livewire.job-detail')->layout('components.layout', [
            'title' => ($this->job['title'] ?? 'İş İlanı Detayı')
                . ' - ' . ($this->company['name'] ?? ''),
        ]);
    }
}