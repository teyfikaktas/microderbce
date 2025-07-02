<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Exception;

class UserSearchService
{
    private $supabaseUrl;
    private $supabaseKey;

    public function __construct()
    {
        $this->supabaseUrl = env('SUPABASE_URL');
        $this->supabaseKey = env('SUPABASE_ANON_KEY');
    }

    /**
     * Get user's recent searches from Supabase
     */
    public function getRecentSearches(?string $userId, int $limit = 10): array
    {
        if (!$userId) {
            return [];
        }

        try {
            $response = Http::withHeaders([
                'apikey' => $this->supabaseKey,
                'Authorization' => 'Bearer ' . $this->supabaseKey
            ])->get($this->supabaseUrl . '/rest/v1/user_searches', [
                'user_id' => "eq.{$userId}",
                'order' => 'searched_at.desc',
                'limit' => $limit,
                'select' => 'search_query,position,city,searched_at'
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            return [];

        } catch (Exception $e) {
            \Log::error('Failed to get recent searches: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get user behavior analysis
     */
    public function getUserBehaviorAnalysis(?string $userId): ?array
    {
        if (!$userId) {
            return null;
        }

        try {
            $searches = $this->getRecentSearches($userId, 50);
            
            if (empty($searches)) {
                return null;
            }

            $positions = collect($searches)->pluck('position')->filter()->unique();
            $cities = collect($searches)->pluck('city')->filter()->unique();
            $totalSearches = count($searches);

            return [
                'total_searches' => $totalSearches,
                'preferred_positions' => $positions->values()->toArray(),
                'preferred_cities' => $cities->values()->toArray(),
                'last_activity' => $searches[0]['searched_at'] ?? null,
                'search_frequency' => $this->calculateSearchFrequency($totalSearches)
            ];

        } catch (Exception $e) {
            \Log::error('Failed to analyze user behavior: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get popular searches (for recommendations)
     */
    public function getPopularSearches(int $limit = 10): array
    {
        try {
            $thirtyDaysAgo = now()->subDays(30)->toISOString();
            
            $response = Http::withHeaders([
                'apikey' => $this->supabaseKey,
                'Authorization' => 'Bearer ' . $this->supabaseKey
            ])->get($this->supabaseUrl . '/rest/v1/user_searches', [
                'searched_at' => "gte.{$thirtyDaysAgo}",
                'search_query' => 'not.is.null',
                'select' => 'search_query'
            ]);

            if ($response->successful()) {
                $searches = $response->json();
                
                // Group by search_query and count
                $queryCounts = collect($searches)
                    ->groupBy('search_query')
                    ->map(fn($group) => $group->count())
                    ->sortDesc()
                    ->take($limit);

                return $queryCounts->map(function($count, $query) {
                    return [
                        'query' => $query,
                        'count' => $count
                    ];
                })->values()->toArray();
            }

            return [];

        } catch (Exception $e) {
            \Log::error('Failed to get popular searches: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get personalized recommendations based on user history
     */
    public function getPersonalizedRecommendations(?string $userId): array
    {
        if (!$userId) {
            return $this->getPopularSearches(5);
        }

        try {
            $behavior = $this->getUserBehaviorAnalysis($userId);
            
            if (!$behavior) {
                return $this->getPopularSearches(5);
            }

            $recommendations = [];

            // Based on preferred positions
            if (!empty($behavior['preferred_positions'])) {
                foreach ($behavior['preferred_positions'] as $position) {
                    $recommendations[] = [
                        'type' => 'position_based',
                        'suggestion' => $position,
                        'reason' => 'Daha önce bu pozisyonu aradınız'
                    ];
                }
            }

            // Based on preferred cities
            if (!empty($behavior['preferred_cities'])) {
                foreach ($behavior['preferred_cities'] as $city) {
                    $recommendations[] = [
                        'type' => 'city_based',
                        'suggestion' => $city,
                        'reason' => 'Bu şehirde iş arıyorsunuz'
                    ];
                }
            }

            // Mix with popular searches
            $popularSearches = $this->getPopularSearches(3);
            foreach ($popularSearches as $popular) {
                $recommendations[] = [
                    'type' => 'trending',
                    'suggestion' => $popular['query'],
                    'reason' => 'Popüler arama (' . $popular['count'] . ' kişi aradı)'
                ];
            }

            return array_slice($recommendations, 0, 5);

        } catch (Exception $e) {
            \Log::error('Failed to get personalized recommendations: ' . $e->getMessage());
            return $this->getPopularSearches(5);
        }
    }

    /**
     * Find similar users based on search patterns
     */
    public function findSimilarUsers(?string $userId, int $limit = 5): array
    {
        if (!$userId) {
            return [];
        }

        try {
            $userBehavior = $this->getUserBehaviorAnalysis($userId);
            
            if (!$userBehavior) {
                return [];
            }

            // Get recent searches from other users
            $thirtyDaysAgo = now()->subDays(30)->toISOString();
            
            $response = Http::withHeaders([
                'apikey' => $this->supabaseKey,
                'Authorization' => 'Bearer ' . $this->supabaseKey
            ])->get($this->supabaseUrl . '/rest/v1/user_searches', [
                'searched_at' => "gte.{$thirtyDaysAgo}",
                'user_id' => "neq.{$userId}",
                'user_id' => 'not.is.null',
                'select' => 'user_id,position,city'
            ]);

            if ($response->successful()) {
                $otherSearches = collect($response->json());
                
                // Group by user_id
                $userGroups = $otherSearches->groupBy('user_id');
                
                $similarities = [];
                
                foreach ($userGroups as $otherUserId => $searches) {
                    $otherPositions = $searches->pluck('position')->filter()->unique();
                    $otherCities = $searches->pluck('city')->filter()->unique();
                    
                    // Calculate similarity score
                    $positionOverlap = collect($userBehavior['preferred_positions'])
                        ->intersect($otherPositions)
                        ->count();
                    
                    $cityOverlap = collect($userBehavior['preferred_cities'])
                        ->intersect($otherCities)
                        ->count();
                    
                    $similarityScore = ($positionOverlap * 2 + $cityOverlap) / 10;
                    
                    if ($similarityScore > 0) {
                        $similarities[] = [
                            'user_id' => $otherUserId,
                            'similarity_score' => min(1, $similarityScore),
                            'search_count' => $searches->count(),
                            'common_positions' => collect($userBehavior['preferred_positions'])
                                ->intersect($otherPositions)
                                ->values()
                                ->toArray(),
                            'common_cities' => collect($userBehavior['preferred_cities'])
                                ->intersect($otherCities)
                                ->values()
                                ->toArray()
                        ];
                    }
                }
                
                return collect($similarities)
                    ->sortByDesc('similarity_score')
                    ->take($limit)
                    ->values()
                    ->toArray();
            }

            return [];

        } catch (Exception $e) {
            \Log::error('Failed to find similar users: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Save AI interaction for future analysis
     */
    public function saveAIInteraction(?string $userId, string $message, array $response): bool
    {
        try {
            $data = [
                'user_id' => $userId,
                'message' => $message,
                'response' => json_encode($response),
                'success' => $response['success'] ?? false,
                'intent' => $response['intent'] ?? null,
                'created_at' => now()->toISOString()
            ];

            $response = Http::withHeaders([
                'apikey' => $this->supabaseKey,
                'Authorization' => 'Bearer ' . $this->supabaseKey,
                'Content-Type' => 'application/json'
            ])->post($this->supabaseUrl . '/rest/v1/ai_interactions', $data);

            return $response->successful();

        } catch (Exception $e) {
            \Log::error('Failed to save AI interaction: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get search statistics for analytics
     */
    public function getSearchStatistics(?string $userId = null): array
    {
        try {
            $weekAgo = now()->subWeek()->toISOString();
            $todayStart = now()->startOfDay()->toISOString();
            
            $filters = [
                'searched_at' => "gte.{$weekAgo}",
                'select' => 'id'
            ];
            
            if ($userId) {
                $filters['user_id'] = "eq.{$userId}";
            }

            // Last week searches
            $weekResponse = Http::withHeaders([
                'apikey' => $this->supabaseKey,
                'Authorization' => 'Bearer ' . $this->supabaseKey
            ])->get($this->supabaseUrl . '/rest/v1/user_searches', $filters);

            // Today searches
            $filters['searched_at'] = "gte.{$todayStart}";
            $todayResponse = Http::withHeaders([
                'apikey' => $this->supabaseKey,
                'Authorization' => 'Bearer ' . $this->supabaseKey
            ])->get($this->supabaseUrl . '/rest/v1/user_searches', $filters);

            return [
                'total_searches_last_week' => $weekResponse->successful() ? count($weekResponse->json()) : 0,
                'total_searches_today' => $todayResponse->successful() ? count($todayResponse->json()) : 0,
                'user_id' => $userId
            ];

        } catch (Exception $e) {
            \Log::error('Failed to get search statistics: ' . $e->getMessage());
            return [
                'total_searches_last_week' => 0,
                'total_searches_today' => 0,
                'user_id' => $userId
            ];
        }
    }

    /**
     * Calculate search frequency category
     */
    private function calculateSearchFrequency(int $totalSearches): string
    {
        if ($totalSearches > 20) {
            return 'high';
        } elseif ($totalSearches > 5) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * Test Supabase connection
     */
    public function testConnection(): array
    {
        try {
            $response = Http::withHeaders([
                'apikey' => $this->supabaseKey,
                'Authorization' => 'Bearer ' . $this->supabaseKey
            ])->get($this->supabaseUrl . '/rest/v1/user_searches', [
                'limit' => 1
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Supabase connection successful',
                    'status' => $response->status()
                ];
            }

            return [
                'success' => false,
                'message' => 'Supabase connection failed',
                'status' => $response->status(),
                'error' => $response->body()
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection error: ' . $e->getMessage()
            ];
        }
    }
}