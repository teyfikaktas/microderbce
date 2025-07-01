<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\MongoSearchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SearchAnalyticsController extends Controller
{
    private $mongoSearchService;

    public function __construct(MongoSearchService $mongoSearchService)
    {
        $this->mongoSearchService = $mongoSearchService;
    }

    /**
     * Arama kaydet
     */
    public function saveSearch(Request $request)
    {
        \Log::info('SaveSearch called with data: ' . json_encode($request->all()));

        try {
            $validator = Validator::make($request->all(), [
                'position' => 'nullable|string|max:255',
                'city' => 'nullable|string|max:255',
                'user_id' => 'nullable|string',
                'results_count' => 'nullable|integer|min:0'
            ]);

            if ($validator->fails()) {
                \Log::error('Validation failed: ' . json_encode($validator->errors()));
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            // Search query oluştur
            $searchQuery = trim(($request->position ?? '') . ' - ' . ($request->city ?? ''), ' - ');

            $searchData = [
                'user_id' => $request->user_id ?? 'anonymous',
                'search_query' => $searchQuery,
                'position' => $request->position,
                'city' => $request->city,
                'filters' => $request->filters ?? [],
                'results_count' => $request->results_count ?? 0
            ];

            \Log::info('Calling MongoSearchService with: ' . json_encode($searchData));

            $result = $this->mongoSearchService->saveSearch($searchData);

            \Log::info('MongoSearchService result: ' . json_encode($result));

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Arama kaydedildi',
                    'id' => $result['id']
                ], 201);
            }

            return response()->json([
                'success' => false,
                'message' => 'Arama kaydedilemedi',
                'error' => $result['error']
            ], 500);

        } catch (\Exception $e) {
            \Log::error('SaveSearch exception: ' . $e->getMessage() . ' - ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Exception occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Son aramaları getir
     */
    public function getRecentSearches(Request $request, $userId = 'anonymous')
    {
        $limit = $request->get('limit', 10);
        
        $searches = $this->mongoSearchService->getRecentSearches($userId, $limit);

        return response()->json([
            'success' => true,
            'data' => $searches
        ]);
    }

    /**
     * Popüler aramaları getir
     */
    public function getPopularSearches(Request $request)
    {
        $limit = $request->get('limit', 10);
        
        $popular = $this->mongoSearchService->getPopularSearches($limit);

        return response()->json([
            'success' => true,
            'data' => $popular
        ]);
    }

    /**
     * Kullanıcı davranış analizi (AI Agent için)
     */
    public function getUserBehavior($userId)
    {
        $analysis = $this->mongoSearchService->getUserBehaviorAnalysis($userId);

        if ($analysis) {
            return response()->json([
                'success' => true,
                'data' => $analysis
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Kullanıcı davranış verisi bulunamadı'
        ], 404);
    }

    /**
     * Arama istatistikleri
     */
    public function getStatistics()
    {
        $stats = $this->mongoSearchService->getSearchStatistics();

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Benzer kullanıcıları bul
     */
    public function getSimilarUsers($userId)
    {
        $similar = $this->mongoSearchService->findSimilarUsers($userId);

        return response()->json([
            'success' => true,
            'data' => $similar
        ]);
    }

    /**
     * AI Agent için kullanıcı profili
     */
    public function getAIUserProfile($userId)
    {
        $behavior = $this->mongoSearchService->getUserBehaviorAnalysis($userId);
        $recentSearches = $this->mongoSearchService->getRecentSearches($userId, 5);
        $similar = $this->mongoSearchService->findSimilarUsers($userId, 3);

        return response()->json([
            'success' => true,
            'data' => [
                'user_id' => $userId,
                'behavior_analysis' => $behavior,
                'recent_searches' => $recentSearches,
                'similar_users' => $similar,
                'profile_generated_at' => now()->toISOString()
            ]
        ]);
    }
}