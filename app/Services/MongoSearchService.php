<?

use MongoDB\Laravel\Connection;

class MongoSearchService 
{
    public function saveSearch($userId, $searchData)
    {
        DB::connection('mongodb')
            ->collection('job_searches')
            ->insert([
                'user_id' => $userId,
                'search_query' => $searchData['query'],
                'filters' => $searchData['filters'],
                'results_count' => $searchData['results_count'],
                'timestamp' => now(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);
    }
    
    public function getSearchAnalytics()
    {
        return DB::connection('mongodb')
            ->collection('job_searches')
            ->aggregate([
                ['$group' => [
                    '_id' => '$search_query',
                    'count' => ['$sum' => 1]
                ]],
                ['$sort' => ['count' => -1]],
                ['$limit' => 10]
            ]);
    }
}