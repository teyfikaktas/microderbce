<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\SupabaseService;
use Illuminate\Support\Facades\Http;

class HomePage extends Component
{
    public $searchPosition = '';
    public $searchCity = '';
    public $jobs = [];
    public $recentSearches = [];

    public function mount()
    {
        $this->loadJobs();
        $this->loadRecentSearches();
    }

    public function search()
    {
        if (empty($this->searchPosition) && empty($this->searchCity)) {
            $this->loadJobs();
            return;
        }

        // Supabase API ile arama
        $url = env('SUPABASE_URL') . '/rest/v1/job_postings?';
        
        $filters = [];
        if ($this->searchPosition) {
            $filters[] = "position=ilike.*{$this->searchPosition}*";
        }
        if ($this->searchCity) {
            $filters[] = "city=ilike.*{$this->searchCity}*";
        }
        
        $url .= implode('&', $filters);

        $response = Http::withHeaders([
            'apikey' => env('SUPABASE_SERVICE_KEY'),
            'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_KEY')
        ])->get($url);

        $this->jobs = $response->json();
        
        // Save search to recent searches
        $this->saveSearch();
    }

    public function loadJobs()
    {
        $response = Http::withHeaders([
            'apikey' => env('SUPABASE_SERVICE_KEY'),
            'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_KEY')
        ])->get(env('SUPABASE_URL') . '/rest/v1/job_postings?limit=10&order=created_at.desc');

        $this->jobs = $response->json();
    }

    public function loadRecentSearches()
    {
        // Mock data for now
        $this->recentSearches = [
            'Web Developer - Istanbul',
            'Frontend Developer - Ankara',
            'Full Stack Developer - İzmir'
        ];
    }

    public function saveSearch()
    {
        if (!empty($this->searchPosition) || !empty($this->searchCity)) {
            $searchQuery = trim($this->searchPosition . ' - ' . $this->searchCity, ' - ');
            
            // Add to recent searches (mock implementation)
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

    public function render()
    {
        return view('livewire.home-page')->layout('components.layout', ['title' => 'Ana Sayfa - MicroJob']);
    }
}