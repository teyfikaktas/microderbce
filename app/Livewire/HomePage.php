<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Http;

class HomePage extends Component
{
    public $searchPosition = '';
    public $searchCity = '';
    public $jobs = [];
    public $recentSearches = [];
    public $loading = false;
    public $error = null;

    // Job Search API URL
    private $apiUrl = 'https://job-search-api.elastic-swartz.213-238-168-122.plesk.page/api/v1';

    public function mount()
    {
        $this->loadJobs();
        $this->loadRecentSearches();
    }

    public function search()
    {
        $this->loading = true;
        $this->error = null;

        try {
            if (empty($this->searchPosition) && empty($this->searchCity)) {
                $this->loadJobs();
                return;
            }

            // Job Search API ile arama
            $response = Http::timeout(10)->post($this->apiUrl . '/search', [
                'position' => $this->searchPosition,
                'city' => $this->searchCity,
                'limit' => 20
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $this->jobs = $data['data'] ?? [];
                
                // Save search to recent searches
                $this->saveSearch();
            } else {
                $this->error = 'Arama sırasında bir hata oluştu.';
                $this->loadJobs(); // Fallback to default jobs
            }
        } catch (\Exception $e) {
            $this->error = 'Bağlantı hatası oluştu.';
            $this->loadJobs(); // Fallback to default jobs
        } finally {
            $this->loading = false;
        }
    }

    public function loadJobs()
    {
        $this->loading = true;
        $this->error = null;

        try {
            // Job Search API'den jobs al
            $response = Http::timeout(10)->get($this->apiUrl . '/jobs', [
                'limit' => 10,
                'city' => 'Istanbul' // Default city for homepage
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $this->jobs = $data['data'] ?? [];
            } else {
                // Fallback: Direkt Supabase'den al
                $this->loadJobsFromSupabase();
            }
        } catch (\Exception $e) {
            // Fallback: Direkt Supabase'den al
            $this->loadJobsFromSupabase();
        } finally {
            $this->loading = false;
        }
    }

    /**
     * Fallback method - direkt Supabase'den jobs al
     */
    private function loadJobsFromSupabase()
    {
        try {
            $response = Http::withHeaders([
                'apikey' => env('SUPABASE_SERVICE_KEY'),
                'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_KEY')
            ])->get(env('SUPABASE_URL') . '/rest/v1/job_postings?limit=10&order=created_at.desc&is_active=eq.true');

            if ($response->successful()) {
                $this->jobs = $response->json();
            }
        } catch (\Exception $e) {
            $this->error = 'Veri yüklenirken hata oluştu.';
            $this->jobs = [];
        }
    }

    public function loadRecentSearches()
    {
        try {
            // Job Search API'den recent searches al
            $response = Http::timeout(5)->get($this->apiUrl . '/recent-searches/anonymous');
            
            if ($response->successful()) {
                $this->recentSearches = $response->json();
            } else {
                // Fallback: Mock data
                $this->recentSearches = [
                    'Web Developer - Istanbul',
                    'Frontend Developer - Ankara',
                    'Full Stack Developer - İzmir'
                ];
            }
        } catch (\Exception $e) {
            // Fallback: Mock data
            $this->recentSearches = [
                'Web Developer - Istanbul',
                'Frontend Developer - Ankara',
                'Full Stack Developer - İzmir'
            ];
        }
    }

    public function saveSearch()
    {
        if (!empty($this->searchPosition) || !empty($this->searchCity)) {
            $searchQuery = trim($this->searchPosition . ' - ' . $this->searchCity, ' - ');
            
            try {
                // Job Search API'ye search kaydet
                Http::timeout(3)->post($this->apiUrl . '/recent-searches', [
                    'user_id' => 'anonymous',
                    'search_query' => $searchQuery
                ]);
            } catch (\Exception $e) {
                // Silent fail
            }

            // Local recent searches'e de ekle (immediate UI update)
            if (!in_array($searchQuery, $this->recentSearches)) {
                array_unshift($this->recentSearches, $searchQuery);
                $this->recentSearches = array_slice($this->recentSearches, 0, 5);
            }
        }
    }

    public function selectRecentSearch($search)
    {
        $parts = explode(' - ', $search);
        $this->searchPosition = $parts[0] ?? '';
        $this->searchCity = $parts[1] ?? '';
        $this->search();
    }

    /**
     * Get autocomplete suggestions for positions
     */
    public function getPositionSuggestions($query)
    {
        if (strlen($query) < 2) {
            return [];
        }

        try {
            $response = Http::timeout(3)->get($this->apiUrl . '/autocomplete/positions', [
                'q' => $query
            ]);

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            // Silent fail
        }

        return [];
    }

    /**
     * Get autocomplete suggestions for cities
     */
    public function getCitySuggestions($query)
    {
        if (strlen($query) < 2) {
            return [];
        }

        try {
            $response = Http::timeout(3)->get($this->apiUrl . '/autocomplete/cities', [
                'q' => $query
            ]);

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            // Silent fail
        }

        return [];
    }

    /**
     * Clear search and reload default jobs
     */
    public function clearSearch()
    {
        $this->searchPosition = '';
        $this->searchCity = '';
        $this->loadJobs();
    }

    public function render()
    {
        return view('livewire.home-page')->layout('components.layout', [
            'title' => 'Ana Sayfa - MicroJob'
        ]);
    }
}