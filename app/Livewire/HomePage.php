<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;

class HomePage extends Component
{
    public $searchPosition = '';
    public $searchCity = '';
    public $jobs = [];
    public $recentSearches = [];
    public $loading = false;
    public $error = null;
    public $userId = null;

    // Job Search API URL
    private $apiUrl = 'https://job-search-api.elastic-swartz.213-238-168-122.plesk.page/api/v1';

    public function mount()
    {
        // Get authenticated user ID or set as null for anonymous
        $this->userId = Auth::check() ? Auth::id() : null;
        
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
                'user_id' => $this->userId, // UUID veya null
                'limit' => 20
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $this->jobs = $data['data'] ?? [];
                
                // Save search to analytics
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
                'city' => $this->getUserCity() // User's city or default
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
     * Get user's city from profile or use default
     */
    private function getUserCity()
    {
        if (Auth::check()) {
            // User authenticated - get city from profile
            $user = Auth::user();
            return $user->city ?? 'Istanbul'; // Fallback to Istanbul
        }
        
        // Anonymous user - try to get from browser location or use default
        return session('user_city', 'Istanbul');
    }

    /**
     * Fallback method - direkt Supabase'den jobs al
     */
    private function loadJobsFromSupabase()
    {
        try {
            $city = $this->getUserCity();
            
            $response = Http::withHeaders([
                'apikey' => env('SUPABASE_SERVICE_KEY'),
                'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_KEY')
            ])->get(env('SUPABASE_URL') . "/rest/v1/job_postings?limit=10&order=created_at.desc&is_active=eq.true&city=ilike.*{$city}*");

            if ($response->successful()) {
                $jobs = $response->json();
                
                // If no jobs in user's city, get from all cities
                if (empty($jobs)) {
                    $response = Http::withHeaders([
                        'apikey' => env('SUPABASE_SERVICE_KEY'),
                        'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_KEY')
                    ])->get(env('SUPABASE_URL') . '/rest/v1/job_postings?limit=10&order=created_at.desc&is_active=eq.true');
                    
                    $jobs = $response->successful() ? $response->json() : [];
                }
                
                $this->jobs = $jobs;
            }
        } catch (\Exception $e) {
            $this->error = 'Veri yüklenirken hata oluştu.';
            $this->jobs = [];
        }
    }

    public function loadRecentSearches()
    {
        try {
            // Authenticated user ise kendi aramalarını, değilse anonymous aramalarını al
            $userIdForSearch = $this->userId ?: 'anonymous';
            
            $response = Http::timeout(5)->get($this->apiUrl . "/recent-searches/{$userIdForSearch}");
            
            if ($response->successful()) {
                $this->recentSearches = $response->json();
            } else {
                // Fallback: Direkt Supabase'den al
                $this->loadRecentSearchesFromSupabase();
            }
        } catch (\Exception $e) {
            // Fallback: Direkt Supabase'den al
            $this->loadRecentSearchesFromSupabase();
        }
    }

    /**
     * Fallback: Recent searches'i direkt Supabase'den al
     */
    private function loadRecentSearchesFromSupabase()
    {
        try {
            $endpoint = '/rest/v1/user_searches?select=search_query,position,city&order=searched_at.desc&limit=5';
            
            if ($this->userId) {
                $endpoint .= "&user_id=eq.{$this->userId}";
            } else {
                $endpoint .= "&user_id=is.null";
            }

            $response = Http::withHeaders([
                'apikey' => env('SUPABASE_SERVICE_KEY'),
                'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_KEY')
            ])->get(env('SUPABASE_URL') . $endpoint);

            if ($response->successful()) {
                $searches = collect($response->json())->map(function($search) {
                    return trim(($search['position'] ?? '') . ' - ' . ($search['city'] ?? ''), ' - ');
                })->filter()->unique()->values()->toArray();

                $this->recentSearches = $searches;
            } else {
                // Final fallback: Mock data
                $this->recentSearches = [
                    'Web Developer - Istanbul',
                    'Frontend Developer - Ankara',
                    'Full Stack Developer - İzmir'
                ];
            }
        } catch (\Exception $e) {
            // Final fallback: Mock data
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
                Http::timeout(3)->post($this->apiUrl . '/save-recent-search', [
                    'user_id' => $this->userId, // UUID veya null
                    'search_query' => $searchQuery,
                    'position' => $this->searchPosition,
                    'city' => $this->searchCity
                ]);
            } catch (\Exception $e) {
                // Silent fail - fallback to Supabase
                $this->saveSearchToSupabase($searchQuery);
            }

            // Local recent searches'e de ekle (immediate UI update)
            if (!in_array($searchQuery, $this->recentSearches)) {
                array_unshift($this->recentSearches, $searchQuery);
                $this->recentSearches = array_slice($this->recentSearches, 0, 5);
            }
        }
    }

    /**
     * Fallback: Search'i direkt Supabase'e kaydet
     */
    private function saveSearchToSupabase($searchQuery)
    {
        try {
            $data = [
                'user_id' => $this->userId, // UUID veya null
                'search_query' => $searchQuery,
                'position' => $this->searchPosition,
                'city' => $this->searchCity,
                'filters' => null
            ];

            Http::withHeaders([
                'apikey' => env('SUPABASE_SERVICE_KEY'),
                'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_KEY'),
                'Content-Type' => 'application/json'
            ])->post(env('SUPABASE_URL') . '/rest/v1/user_searches', $data);

        } catch (\Exception $e) {
            // Silent fail
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

    /**
     * Set user city from browser geolocation
     */
    public function setUserCity($city)
    {
        session(['user_city' => $city]);
        $this->loadJobs(); // Reload jobs for the new city
    }

    /**
     * Get user info for display
     */
    public function getUserInfo()
    {
        return [
            'is_authenticated' => Auth::check(),
            'user_id' => $this->userId,
            'user_name' => Auth::check() ? Auth::user()->name : 'Misafir',
            'user_email' => Auth::check() ? Auth::user()->email : null,
            'user_city' => $this->getUserCity()
        ];
    }

    public function render()
    {
        return view('livewire.home-page', [
            'userInfo' => $this->getUserInfo()
        ])->layout('components.layout', [
            'title' => 'Ana Sayfa - MicroJob'
        ]);
    }
}