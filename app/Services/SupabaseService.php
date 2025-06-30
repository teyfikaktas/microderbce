<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;

class SupabaseService
{
    private string $url;
    private string $serviceKey;
    private string $anonKey;

    public function getJobsWithCache($filters = [])
{
    $cacheKey = 'jobs:' . md5(serialize($filters));
    
    // Redis'ten kontrol et
    $cachedJobs = Redis::get($cacheKey);
    if ($cachedJobs) {
        return json_decode($cachedJobs, true);
    }
    
    // API'den çek
    $response = $this->getJobs($filters);
    $jobs = $response->json();
    
    // Redis'e kaydet (5 dakika)
    Redis::setex($cacheKey, 300, json_encode($jobs));
    
    return $jobs;
}

    public function __construct()
    {
        $this->url = config('services.supabase.url');
        $this->serviceKey = config('services.supabase.service_key');
        $this->anonKey = config('services.supabase.anon_key');
    }
    public function saveSearchToRedis($userId, $searchQuery, $position, $city)
    {
        $searchData = [
            'query' => $searchQuery,
            'position' => $position,
            'city' => $city,
            'timestamp' => now()->toISOString()
        ];
        
        // User'ın son aramalarını Redis'te tut
        Redis::lpush("user_searches:{$userId}", json_encode($searchData));
        Redis::ltrim("user_searches:{$userId}", 0, 4); // Son 5 arama
    }
    /**
     * Get all job postings
     */
    public function getJobs(array $filters = [])
    {
        $query = http_build_query($filters);
        
        return Http::withHeaders([
            'apikey' => $this->serviceKey,
            'Authorization' => 'Bearer ' . $this->serviceKey,
            'Content-Type' => 'application/json'
        ])->get($this->url . '/rest/v1/job_postings?' . $query);
    }

    /**
     * Create a new job posting
     */
    public function createJob(array $data)
    {
        return Http::withHeaders([
            'apikey' => $this->serviceKey,
            'Authorization' => 'Bearer ' . $this->serviceKey,
            'Content-Type' => 'application/json'
        ])->post($this->url . '/rest/v1/job_postings', $data);
    }

    /**
     * Get job by ID
     */
    public function getJob(int $id)
    {
        return Http::withHeaders([
            'apikey' => $this->serviceKey,
            'Authorization' => 'Bearer ' . $this->serviceKey
        ])->get($this->url . "/rest/v1/job_postings?id=eq.{$id}");
    }

    /**
     * Search jobs
     */
    public function searchJobs(string $position = '', string $city = '')
    {
        $filters = [];
        
        if ($position) {
            $filters['position'] = "ilike.*{$position}*";
        }
        
        if ($city) {
            $filters['city'] = "ilike.*{$city}*";
        }
        
        return $this->getJobs($filters);
    }

    /**
     * Get companies
     */
    public function getCompanies()
    {
        return Http::withHeaders([
            'apikey' => $this->serviceKey,
            'Authorization' => 'Bearer ' . $this->serviceKey
        ])->get($this->url . '/rest/v1/companies');
    }

    /**
     * Create company
     */
    public function createCompany(array $data)
    {
        return Http::withHeaders([
            'apikey' => $this->serviceKey,
            'Authorization' => 'Bearer ' . $this->serviceKey,
            'Content-Type' => 'application/json'
        ])->post($this->url . '/rest/v1/companies', $data);
    }

    /**
     * Job applications
     */
    public function createApplication(array $data)
    {
        return Http::withHeaders([
            'apikey' => $this->serviceKey,
            'Authorization' => 'Bearer ' . $this->serviceKey,
            'Content-Type' => 'application/json'
        ])->post($this->url . '/rest/v1/job_applications', $data);
    }

    /**
     * Get user applications
     */
    public function getUserApplications(int $userId)
    {
        return Http::withHeaders([
            'apikey' => $this->serviceKey,
            'Authorization' => 'Bearer ' . $this->serviceKey
        ])->get($this->url . "/rest/v1/job_applications?user_id=eq.{$userId}");
    }
}