<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;

class JobDetail extends Component
{
    public $jobId;
    public $job = [];                 // null değil, boş dizi
    public $relatedJobs = [];         // mutlaka dizi
    public $company = [];             // boş dizi
    public $loading = true;
    public $error = '';               // null yerine string
    public $showApplicationModal = false;
    public $hasUserApplied = false;

    public $applicationData = [
        'cover_letter' => ''
    ];

    private $apiUrl             = 'https://job-search-api.elastic-swartz.213-238-168-122.plesk.page/api/v1';
    private $applicationApiUrl  = 'https://job-apply.elastic-swartz.213-238-168-122.plesk.page/api/v1';

    public function mount($id)
    {
        $this->jobId = $id;
        $this->loadJobDetail();
        $this->loadRelatedJobs();
        $this->checkUserApplication();
    }

    public function loadJobDetail()
    {
        $this->loading = true;
        $this->error   = '';

        $cacheKey = "job_detail:{$this->jobId}";

        if ($cached = Redis::get($cacheKey)) {
            $this->job     = json_decode($cached, true) ?: [];
            $this->company = $this->job['company'] ?? [];
            $this->loading = false;
            return;
        }

        try {
            $response = Http::timeout(10)
                ->get("{$this->apiUrl}/jobs/{$this->jobId}");

            if ($response->successful()) {
                $this->job     = $response->json() ?: [];
                $this->company = $this->job['company'] ?? [];
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

        if ($cached = Redis::get($cacheKey)) {
            $this->relatedJobs = json_decode($cached, true) ?: [];
            return;
        }

        try {
            $response = Http::timeout(5)
                ->get("{$this->apiUrl}/jobs/{$this->jobId}/related");

            if ($response->successful()) {
                $this->relatedJobs = $response->json() ?: [];
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
        ]);

        try {
            $payload = [
                'job_posting_id' => (int) $this->jobId,
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

                $this->job['application_count'] = ($this->job['application_count'] ?? 0) + 1;
            } else {
                if ($response->status() === 409) {
                    $this->hasUserApplied = true;
                    session()->flash('error', 'Bu iş ilanına daha önce başvuru yapmışsınız.');
                } elseif ($response->status() === 422) {
                    $errs = collect($response->json('errors', []))
                        ->flatten()->implode(' ');
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

    public function render()
    {
        return view('livewire.job-detail')
            ->layout('components.layout', [
                'title' => ($this->job['title'] ?? 'İş İlanı Detayı')
                    . ' - ' . ($this->company['name'] ?? ''),
            ]);
    }
}
