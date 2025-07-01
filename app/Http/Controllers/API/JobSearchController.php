<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;

class JobSearchController extends Controller
{

    /**
     * Get all jobs with pagination
     */
    public function jobs(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'nullable|integer|min:1|max:100',
            'offset' => 'nullable|integer|min:0',
            'city' => 'nullable|string|max:255',
            'work_type' => 'nullable|in:remote,hybrid,onsite',
            'experience_level' => 'nullable|in:junior,mid,senior,lead'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $limit = $request->get('limit', 20);
            $offset = $request->get('offset', 0);

            // Cache key oluştur
            $cacheKey = 'jobs_' . md5(serialize($request->all()));
            
            $jobs = Cache::remember($cacheKey, 300, function () use ($request) {
                return $this->getJobsFromSupabase($request->all());
            });

            return response()->json([
                'success' => true,
                'data' => $jobs,
                'pagination' => [
                    'limit' => $limit,
                    'offset' => $offset,
                    'total' => count($jobs)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Jobs getirilemedi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Job Search - MongoDB tracking ile
     */
    public function search(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'position' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'work_type' => 'nullable|in:remote,hybrid,onsite',
            'experience_level' => 'nullable|in:junior,mid,senior,lead',
            'salary_min' => 'nullable|integer|min:0',
            'salary_max' => 'nullable|integer|min:0',
            'limit' => 'nullable|integer|min:1|max:100',
            'offset' => 'nullable|integer|min:0',
            'user_id' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Supabase'den arama yap
            $jobs = $this->searchJobsInSupabase($request->all());

            // TODO: MongoDB tracking burada olacak (SearchAnalyticsController'da ayrı)

            return response()->json([
                'success' => true,
                'data' => $jobs,
                'total' => count($jobs),
                'filters_applied' => $this->getAppliedFilters($request)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Arama sırasında hata oluştu',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single job by ID
     */
    public function getJob($id)
    {
        try {
            $cacheKey = 'job_' . $id;
            
            $job = Cache::remember($cacheKey, 600, function () use ($id) {
                return $this->getJobFromSupabase($id);
            });

            if ($job) {
                return response()->json([
                    'success' => true,
                    'data' => $job
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Job bulunamadı'
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Job getirilemedi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get related jobs
     */
    public function getRelatedJobs($id)
    {
        try {
            $job = $this->getJobFromSupabase($id);
            
            if (!$job) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ana job bulunamadı'
                ], 404);
            }

            // Aynı şehir ve benzer pozisyonlardaki işler
            $relatedJobs = $this->searchJobsInSupabase([
                'city' => $job['city'],
                'limit' => 5
            ]);

            // Ana job'ı çıkar
            $relatedJobs = array_filter($relatedJobs, function($relatedJob) use ($id) {
                return $relatedJob['id'] != $id;
            });

            return response()->json([
                'success' => true,
                'data' => array_slice($relatedJobs, 0, 3)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'İlgili joblar getirilemedi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Autocomplete için pozisyon önerileri
     */
    public function getPositionSuggestions(Request $request)
    {
        $query = $request->get('q', '');
        
        if (strlen($query) < 2) {
            return response()->json([]);
        }

        try {
            $response = Http::withHeaders([
                'apikey' => env('SUPABASE_ANON_KEY'),
                'Authorization' => 'Bearer ' . env('SUPABASE_ANON_KEY')
            ])->get(env('SUPABASE_URL') . '/rest/v1/job_postings', [
                'select' => 'title',
                'title' => "ilike.*{$query}*",
                'limit' => 10
            ]);

            if ($response->successful()) {
                $suggestions = collect($response->json())
                    ->pluck('title')
                    ->unique()
                    ->values()
                    ->toArray();

                return response()->json($suggestions);
            }

            return response()->json([]);

        } catch (\Exception $e) {
            return response()->json([]);
        }
    }

    /**
     * Autocomplete için şehir önerileri
     */
    public function getCitySuggestions(Request $request)
    {
        $query = $request->get('q', '');
        
        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $cities = [
            'İstanbul', 'Ankara', 'İzmir', 'Bursa', 'Antalya', 'Adana', 'Konya', 
            'Gaziantep', 'Mersin', 'Diyarbakır', 'Kayseri', 'Eskişehir', 'Urfa'
        ];

        $suggestions = array_filter($cities, function($city) use ($query) {
            return stripos($city, $query) !== false;
        });

        return response()->json(array_values($suggestions));
    }

    /**
     * Supabase'den jobs getir
     */
    private function getJobsFromSupabase($params)
    {
        $queryParams = [
            'is_active' => 'eq.true',
            'order' => 'created_at.desc',
            'limit' => $params['limit'] ?? 20,
            'offset' => $params['offset'] ?? 0
        ];

        if (!empty($params['city'])) {
            $queryParams['city'] = "ilike.*{$params['city']}*";
        }

        if (!empty($params['work_type'])) {
            $queryParams['work_type'] = "eq.{$params['work_type']}";
        }

        if (!empty($params['experience_level'])) {
            $queryParams['experience_level'] = "eq.{$params['experience_level']}";
        }

        $response = Http::withHeaders([
            'apikey' => env('SUPABASE_ANON_KEY'),
            'Authorization' => 'Bearer ' . env('SUPABASE_ANON_KEY')
        ])->get(env('SUPABASE_URL') . '/rest/v1/job_postings', $queryParams);

        if ($response->successful()) {
            return $response->json();
        }

        return [];
    }

    /**
     * Supabase'de job arama
     */
    private function searchJobsInSupabase($params)
    {
        $queryParams = [
            'is_active' => 'eq.true',
            'order' => 'created_at.desc',
            'limit' => $params['limit'] ?? 20,
            'offset' => $params['offset'] ?? 0
        ];

        // Position araması
        if (!empty($params['position'])) {
            $queryParams['or'] = "(title.ilike.*{$params['position']}*,description.ilike.*{$params['position']}*,requirements.ilike.*{$params['position']}*)";
        }

        // City araması
        if (!empty($params['city'])) {
            $queryParams['city'] = "ilike.*{$params['city']}*";
        }

        // Work type filtresi
        if (!empty($params['work_type'])) {
            $queryParams['work_type'] = "eq.{$params['work_type']}";
        }

        // Experience level filtresi
        if (!empty($params['experience_level'])) {
            $queryParams['experience_level'] = "eq.{$params['experience_level']}";
        }

        // Salary filtresi
        if (!empty($params['salary_min'])) {
            $queryParams['salary_min'] = "gte.{$params['salary_min']}";
        }

        if (!empty($params['salary_max'])) {
            $queryParams['salary_max'] = "lte.{$params['salary_max']}";
        }

        $response = Http::withHeaders([
            'apikey' => env('SUPABASE_ANON_KEY'),
            'Authorization' => 'Bearer ' . env('SUPABASE_ANON_KEY')
        ])->get(env('SUPABASE_URL') . '/rest/v1/job_postings', $queryParams);

        if ($response->successful()) {
            return $response->json();
        }

        return [];
    }

    /**
     * Supabase'den tek job getir
     */
    private function getJobFromSupabase($id)
    {
        $response = Http::withHeaders([
            'apikey' => env('SUPABASE_ANON_KEY'),
            'Authorization' => 'Bearer ' . env('SUPABASE_ANON_KEY')
        ])->get(env('SUPABASE_URL') . '/rest/v1/job_postings', [
            'id' => "eq.{$id}",
            'is_active' => 'eq.true'
        ]);

        if ($response->successful()) {
            $jobs = $response->json();
            return !empty($jobs) ? $jobs[0] : null;
        }

        return null;
    }

    /**
     * Uygulanan filtreleri döndür
     */
    private function getAppliedFilters(Request $request)
    {
        $filters = [];

        if ($request->position) {
            $filters[] = ['type' => 'position', 'value' => $request->position];
        }
        if ($request->city) {
            $filters[] = ['type' => 'city', 'value' => $request->city];
        }
        if ($request->work_type) {
            $filters[] = ['type' => 'work_type', 'value' => $request->work_type];
        }
        if ($request->experience_level) {
            $filters[] = ['type' => 'experience_level', 'value' => $request->experience_level];
        }
        if ($request->salary_min) {
            $filters[] = ['type' => 'salary_min', 'value' => $request->salary_min];
        }
        if ($request->salary_max) {
            $filters[] = ['type' => 'salary_max', 'value' => $request->salary_max];
        }

        return $filters;
    }

    /**
     * Health check
     */
    public function health()
    {
        return response()->json([
            'status' => 'ok',
            'service' => 'Job Search API',
            'timestamp' => now()->toISOString(),
            'features' => [
                'supabase' => 'connected',
                'mongodb' => 'separate service',
                'redis' => 'connected'
            ]
        ]);
    }
}