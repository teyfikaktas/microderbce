<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Exception;

class MongoSearchService
{
    private $atlasUrl;
    private $apiKey;
    private $database = 'job_search_analytics';
    private $collection = 'user_searches';

    public function __construct()
    {
        // MongoDB Atlas Admin API kullanacağız
        $this->publicKey = env('MONGODB_PUBLIC_KEY', 'kkoclguu');
        $this->privateKey = env('MONGODB_PRIVATE_KEY', '3e2c95f6-19a6-4fcb-affb-dbca065b2139');
        $this->projectId = env('MONGODB_PROJECT_ID', '6862f4cffd466e5ed0d0f5d9');
        $this->clusterName = 'Cluster0';
        $this->database = 'job_search_analytics';
        $this->collection = 'user_searches';
        
        \Log::info('MongoSearchService initialized with Atlas Admin API');
    }

    public function saveSearch($searchData)
    {
        try {
            $document = [
                'user_id' => $searchData['user_id'] ?? 'anonymous',
                'search_query' => $searchData['search_query'] ?? '',
                'position' => $searchData['position'] ?? '',
                'city' => $searchData['city'] ?? '',
                'filters' => $searchData['filters'] ?? [],
                'results_count' => $searchData['results_count'] ?? 0,
                'timestamp' => time(),
                'ip_address' => request()->ip() ?? '',
                'user_agent' => request()->userAgent() ?? '',
                'session_id' => session()->getId() ?? '',
                'created_at' => now()->toISOString()
            ];

            // Atlas Data API ile kaydet
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'api-key' => $this->apiKey
            ])->post($this->atlasUrl . '/action/insertOne', [
                'dataSource' => 'Cluster0',
                'database' => $this->database,
                'collection' => $this->collection,
                'document' => $document
            ]);

            if ($response->successful()) {
                $result = $response->json();
                \Log::info('MongoDB Atlas save successful: ' . json_encode($result));
                
                return [
                    'success' => true,
                    'id' => $result['insertedId'] ?? 'unknown'
                ];
            } else {
                \Log::error('MongoDB Atlas save failed: ' . $response->body());
                return [
                    'success' => false,
                    'error' => 'Atlas API error: ' . $response->status()
                ];
            }

        } catch (Exception $e) {
            \Log::error('MongoDB Atlas save exception: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function getRecentSearches($userId = 'anonymous', $limit = 10)
    {
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'api-key' => $this->apiKey
            ])->post($this->atlasUrl . '/action/find', [
                'dataSource' => 'Cluster0',
                'database' => $this->database,
                'collection' => $this->collection,
                'filter' => ['user_id' => $userId],
                'sort' => ['timestamp' => -1],
                'limit' => $limit
            ]);

            if ($response->successful()) {
                $result = $response->json();
                $searches = [];
                
                foreach ($result['documents'] ?? [] as $doc) {
                    $searchText = trim(($doc['position'] ?? '') . ' - ' . ($doc['city'] ?? ''), ' - ');
                    if (!empty($searchText)) {
                        $searches[] = $searchText;
                    }
                }

                return array_unique($searches);
            }

            return [];

        } catch (Exception $e) {
            \Log::error('Get recent searches error: ' . $e->getMessage());
            return [];
        }
    }

    public function getPopularSearches($limit = 10)
    {
        try {
            // Atlas Data API ile aggregation
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'api-key' => $this->apiKey
            ])->post($this->atlasUrl . '/action/aggregate', [
                'dataSource' => 'Cluster0',
                'database' => $this->database,
                'collection' => $this->collection,
                'pipeline' => [
                    ['$match' => ['timestamp' => ['$gte' => time() - (30 * 24 * 60 * 60)]]],
                    ['$group' => [
                        '_id' => '$search_query',
                        'count' => ['$sum' => 1]
                    ]],
                    ['$match' => ['_id' => ['$ne' => '']]],
                    ['$sort' => ['count' => -1]],
                    ['$limit' => $limit]
                ]
            ]);

            if ($response->successful()) {
                $result = $response->json();
                $popular = [];

                foreach ($result['documents'] ?? [] as $doc) {
                    $popular[] = [
                        'query' => $doc['_id'],
                        'count' => $doc['count']
                    ];
                }

                return $popular;
            }

            return [];

        } catch (Exception $e) {
            \Log::error('Get popular searches error: ' . $e->getMessage());
            return [];
        }
    }

    public function getUserBehaviorAnalysis($userId)
    {
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'api-key' => $this->apiKey
            ])->post($this->atlasUrl . '/action/find', [
                'dataSource' => 'Cluster0',
                'database' => $this->database,
                'collection' => $this->collection,
                'filter' => ['user_id' => $userId],
                'sort' => ['timestamp' => -1],
                'limit' => 50
            ]);

            if ($response->successful()) {
                $result = $response->json();
                $documents = $result['documents'] ?? [];

                if (empty($documents)) {
                    return null;
                }

                $positions = [];
                $cities = [];
                $totalSearches = count($documents);
                $totalResults = 0;

                foreach ($documents as $doc) {
                    if (!empty($doc['position'])) {
                        $positions[] = $doc['position'];
                    }
                    if (!empty($doc['city'])) {
                        $cities[] = $doc['city'];
                    }
                    $totalResults += $doc['results_count'] ?? 0;
                }

                return [
                    'total_searches' => $totalSearches,
                    'preferred_positions' => array_unique($positions),
                    'preferred_cities' => array_unique($cities),
                    'last_activity' => $documents[0]['timestamp'] ?? null,
                    'avg_results_per_search' => $totalSearches > 0 ? round($totalResults / $totalSearches, 2) : 0
                ];
            }

            return null;

        } catch (Exception $e) {
            \Log::error('Get user behavior error: ' . $e->getMessage());
            return null;
        }
    }

    public function getSearchStatistics()
    {
        // Basit istatistik - son 7 günlük veri
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'api-key' => $this->apiKey
            ])->post($this->atlasUrl . '/action/find', [
                'dataSource' => 'Cluster0',
                'database' => $this->database,
                'collection' => $this->collection,
                'filter' => ['timestamp' => ['$gte' => time() - (7 * 24 * 60 * 60)]]
            ]);

            if ($response->successful()) {
                $result = $response->json();
                return [
                    'total_searches_last_week' => count($result['documents'] ?? [])
                ];
            }

            return [];

        } catch (Exception $e) {
            return [];
        }
    }

    public function findSimilarUsers($userId, $limit = 5)
    {
        // Basit implementation - gerçek benzerlik analizi karmaşık
        return [];
    }
}