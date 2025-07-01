<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Exception;

class MongoSearchService
{
    private $publicKey;
    private $privateKey;
    private $projectId;
    private $clusterName;
    private $database;
    private $collection;

    public function __construct()
    {
        $this->publicKey = env('MONGODB_PUBLIC_KEY', 'kkoclguu');
        $this->privateKey = env('MONGODB_PRIVATE_KEY', '3e2c95f6-19a6-4fcb-affb-dbca065b2139');
        $this->projectId = env('MONGODB_PROJECT_ID', '6862f4cffd466e5ed0d0f5c8');
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

            \Log::info('Sending to MongoDB Atlas: ' . json_encode($document));

            // Atlas Admin API ile kaydet
            $response = Http::withBasicAuth($this->publicKey, $this->privateKey)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post("https://cloud.mongodb.com/api/atlas/v1.0/groups/{$this->projectId}/clusters/{$this->clusterName}/restapi/insert", [
                    'database' => $this->database,
                    'collection' => $this->collection,
                    'document' => $document
                ]);

            \Log::info('Atlas API Response Status: ' . $response->status());
            \Log::info('Atlas API Response Body: ' . $response->body());

            if ($response->successful()) {
                $result = $response->json();
                return [
                    'success' => true,
                    'id' => $result['insertedId'] ?? time()
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Atlas API error: ' . $response->status() . ' - ' . $response->body()
                ];
            }

        } catch (Exception $e) {
            \Log::error('MongoDB save exception: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function getRecentSearches($userId = 'anonymous', $limit = 10)
    {
        try {
            // Atlas Admin API ile find
            $response = Http::withBasicAuth($this->publicKey, $this->privateKey)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post("https://cloud.mongodb.com/api/atlas/v1.0/groups/{$this->projectId}/clusters/{$this->clusterName}/restapi/find", [
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
            // Atlas Admin API ile aggregation
            $response = Http::withBasicAuth($this->publicKey, $this->privateKey)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post("https://cloud.mongodb.com/api/atlas/v1.0/groups/{$this->projectId}/clusters/{$this->clusterName}/restapi/aggregate", [
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
            $response = Http::withBasicAuth($this->publicKey, $this->privateKey)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post("https://cloud.mongodb.com/api/atlas/v1.0/groups/{$this->projectId}/clusters/{$this->clusterName}/restapi/find", [
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
        try {
            $response = Http::withBasicAuth($this->publicKey, $this->privateKey)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post("https://cloud.mongodb.com/api/atlas/v1.0/groups/{$this->projectId}/clusters/{$this->clusterName}/restapi/find", [
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
        try {
            // Basit implementation - gerçek benzerlik analizi karmaşık
            $response = Http::withBasicAuth($this->publicKey, $this->privateKey)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post("https://cloud.mongodb.com/api/atlas/v1.0/groups/{$this->projectId}/clusters/{$this->clusterName}/restapi/find", [
                    'database' => $this->database,
                    'collection' => $this->collection,
                    'filter' => ['user_id' => ['$ne' => $userId]],
                    'limit' => $limit
                ]);

            if ($response->successful()) {
                $result = $response->json();
                $similar = [];

                foreach ($result['documents'] ?? [] as $doc) {
                    $similar[] = [
                        'user_id' => $doc['user_id'],
                        'similarity_score' => 1,
                        'last_activity' => $doc['timestamp'] ?? null
                    ];
                }

                return $similar;
            }

            return [];

        } catch (Exception $e) {
            \Log::error('Find similar users error: ' . $e->getMessage());
            return [];
        }
    }
}