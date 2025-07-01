<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Http;

class JobDetail extends Component
{
    public $jobId;
    public $job = null;
    public $relatedJobs = [];
    public $company = null;
    public $loading = true;
    public $error = null;
    public $showApplicationModal = false;
    // Application form
    public $applicationData = [
        'cover_letter' => '',
        'resume_file' => null,
        'name' => '',
        'email' => '',
        'phone' => ''
    ];

    // Job Search API URL
    private $apiUrl = 'https://job-search-api.elastic-swartz.213-238-168-122.plesk.page/api/v1';

    public function mount($id)
    {
        $this->jobId = $id;
        $this->loadJobDetail();
        $this->loadRelatedJobs();
    }

    public function loadJobDetail()
    {
        $this->loading = true;
        $this->error = null;

        try {
            // Job Search API'den job detayını al
            $response = Http::timeout(10)->get($this->apiUrl . '/jobs/' . $this->jobId);

            if ($response->successful()) {
                $this->job = $response->json();
                $this->company = $this->job['company'] ?? null;
                
                // Increment view count (API handles this automatically)
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
            // Job Search API'den related jobs al
            $response = Http::timeout(5)->get($this->apiUrl . '/jobs/' . $this->jobId . '/related');

            if ($response->successful()) {
                $this->relatedJobs = $response->json();
            }
        } catch (\Exception $e) {
            // Silent fail - related jobs are not critical
            $this->relatedJobs = [];
        }
    }

    public function apply()
    {
        // Check if user is logged in
        if (!auth()->check()) {
            // Redirect to login page
            return redirect()->route('login')->with('message', 'Başvuru yapmak için giriş yapmalısınız.');
        }

        $this->showApplicationModal = true;
    }

    public function submitApplication()
    {
        $this->validate([
            'applicationData.name' => 'required|min:2',
            'applicationData.email' => 'required|email',
            'applicationData.phone' => 'required|min:10',
            'applicationData.cover_letter' => 'required|min:50',
        ], [
            'applicationData.name.required' => 'Ad soyad gereklidir.',
            'applicationData.email.required' => 'E-posta gereklidir.',
            'applicationData.email.email' => 'Geçerli bir e-posta adresi giriniz.',
            'applicationData.phone.required' => 'Telefon numarası gereklidir.',
            'applicationData.cover_letter.required' => 'Kapak mektubu gereklidir.',
            'applicationData.cover_letter.min' => 'Kapak mektubu en az 50 karakter olmalıdır.',
        ]);

        try {
            // Job Posting Service'e application gönder (şimdilik mock)
            $applicationData = array_merge($this->applicationData, [
                'job_id' => $this->jobId,
                'user_id' => auth()->id(),
                'applied_at' => now()->toISOString()
            ]);

            // TODO: Job Posting Service API call
            // $response = Http::post('job-posting-api.../applications', $applicationData);

            $this->showApplicationModal = false;
            $this->reset('applicationData');
            
            session()->flash('success', 'Başvurunuz başarıyla gönderildi!');
            
            // Update application count
            if ($this->job) {
                $this->job['application_count'] = ($this->job['application_count'] ?? 0) + 1;
            }

        } catch (\Exception $e) {
            session()->flash('error', 'Başvuru gönderilirken bir hata oluştu.');
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
        // Copy job URL to clipboard (handled by frontend JS)
        $this->dispatchBrowserEvent('job-shared');
    }

    public function saveJob()
    {
        if (!auth()->check()) {
            session()->flash('error', 'İş ilanını kaydetmek için giriş yapmalısınız.');
            return;
        }

        // TODO: Save job to user's saved jobs
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