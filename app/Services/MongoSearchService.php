<?php

namespace App\Services;

use MongoDB\Client;
use Exception;

class MongoSearchService
{
    private $client;
    private $database;
    private $collection;

    public function __construct()
    {
        try {
            $this->client = new Client(env('MONGODB_URI'));
            $this->database = $this->client->selectDatabase('job_search_analytics');
            $this->collection = $this->database->selectCollection('user_searches');
        } catch (Exception $e) {
            \Log::error('MongoDB connection failed: ' . $e->getMessage());
        }
    }

    /**
     * Kullanıcı aramasını kaydet
     */
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
                'session_id' => session()->getId() ?? ''
            ];

            $result = $this->collection->insertOne($document);
            
            return [
                'success' => true,
                'id' => (string) $result->getInsertedId()
            ];

        } catch (Exception $e) {
            \Log::error('MongoDB save search error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Kullanıcının son aramalarını getir
     */
    public function getRecentSearches($userId = 'anonymous', $limit = 10)
    {
        try {
            $pipeline = [
                ['$match' => ['user_id' => $userId]],
                ['$sort' => ['timestamp' => -1]],
                ['$limit' => $limit],
                ['$project' => [
                    'search_query' => 1,
                    'position' => 1,
                    'city' => 1,
                    'timestamp' => 1
                ]]
            ];

            $results = $this->collection->aggregate($pipeline);
            $searches = [];

            foreach ($results as $result) {
                $searchText = trim(($result['position'] ?? '') . ' - ' . ($result['city'] ?? ''), ' - ');
                if (!empty($searchText)) {
                    $searches[] = $searchText;
                }
            }

            return array_unique($searches);

        } catch (Exception $e) {
            \Log::error('MongoDB get recent searches error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Popüler arama terimlerini getir
     */
    public function getPopularSearches($limit = 10)
    {
        try {
            $pipeline = [
                ['$match' => [
                    'timestamp' => [
                        '$gte' => strtotime('-30 days')
                    ]
                ]],
                ['$group' => [
                    '_id' => '$search_query',
                    'count' => ['$sum' => 1]
                ]],
                ['$match' => ['_id' => ['$ne' => '']]],
                ['$sort' => ['count' => -1]],
                ['$limit' => $limit]
            ];

            $results = $this->collection->aggregate($pipeline);
            $popular = [];

            foreach ($results as $result) {
                $popular[] = [
                    'query' => $result['_id'],
                    'count' => $result['count']
                ];
            }

            return $popular;

        } catch (Exception $e) {
            \Log::error('MongoDB get popular searches error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Kullanıcı davranış analizi (AI Agent için)
     */
    public function getUserBehaviorAnalysis($userId)
    {
        try {
            $pipeline = [
                ['$match' => ['user_id' => $userId]],
                ['$sort' => ['timestamp' => -1]],
                ['$limit' => 50],
                ['$group' => [
                    '_id' => null,
                    'total_searches' => ['$sum' => 1],
                    'unique_positions' => ['$addToSet' => '$position'],
                    'unique_cities' => ['$addToSet' => '$city'],
                    'last_search' => ['$first' => '$timestamp'],
                    'avg_results' => ['$avg' => '$results_count']
                ]]
            ];

            $results = $this->collection->aggregate($pipeline)->toArray();
            
            if (empty($results)) {
                return null;
            }

            $analysis = $results[0];
            
            return [
                'total_searches' => $analysis['total_searches'] ?? 0,
                'preferred_positions' => array_filter($analysis['unique_positions'] ?? []),
                'preferred_cities' => array_filter($analysis['unique_cities'] ?? []),
                'last_activity' => $analysis['last_search'] ?? null,
                'avg_results_per_search' => $analysis['avg_results'] ?? 0
            ];

        } catch (Exception $e) {
            \Log::error('MongoDB get user behavior error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Arama istatistikleri
     */
    public function getSearchStatistics()
    {
        try {
            $pipeline = [
                ['$match' => [
                    'timestamp' => [
                        '$gte' => strtotime('-7 days')
                    ]
                ]],
                ['$group' => [
                    '_id' => [
                        'year' => ['$year' => '$timestamp'],
                        'month' => ['$month' => '$timestamp'],
                        'day' => ['$dayOfMonth' => '$timestamp']
                    ],
                    'count' => ['$sum' => 1]
                ]],
                ['$sort' => ['_id' => 1]]
            ];

            $results = $this->collection->aggregate($pipeline);
            $stats = [];

            foreach ($results as $result) {
                $stats[] = [
                    'date' => sprintf('%04d-%02d-%02d', 
                        $result['_id']['year'], 
                        $result['_id']['month'], 
                        $result['_id']['day']
                    ),
                    'searches' => $result['count']
                ];
            }

            return $stats;

        } catch (Exception $e) {
            \Log::error('MongoDB get search statistics error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Benzer kullanıcıları bul (AI önerileri için)
     */
    public function findSimilarUsers($userId, $limit = 5)
    {
        try {
            // Kullanıcının son aramalarını al
            $userSearches = $this->collection->find(
                ['user_id' => $userId],
                ['sort' => ['timestamp' => -1], 'limit' => 10]
            )->toArray();

            if (empty($userSearches)) {
                return [];
            }

            $userPositions = array_unique(array_column($userSearches, 'position'));
            $userCities = array_unique(array_column($userSearches, 'city'));

            $pipeline = [
                ['$match' => [
                    'user_id' => ['$ne' => $userId],
                    '$or' => [
                        ['position' => ['$in' => $userPositions]],
                        ['city' => ['$in' => $userCities]]
                    ]
                ]],
                ['$group' => [
                    '_id' => '$user_id',
                    'common_searches' => ['$sum' => 1],
                    'last_activity' => ['$max' => '$timestamp']
                ]],
                ['$sort' => ['common_searches' => -1]],
                ['$limit' => $limit]
            ];

            $results = $this->collection->aggregate($pipeline);
            $similar = [];

            foreach ($results as $result) {
                $similar[] = [
                    'user_id' => $result['_id'],
                    'similarity_score' => $result['common_searches'],
                    'last_activity' => $result['last_activity']
                ];
            }

            return $similar;

        } catch (Exception $e) {
            \Log::error('MongoDB find similar users error: ' . $e->getMessage());
            return [];
        }
    }
}