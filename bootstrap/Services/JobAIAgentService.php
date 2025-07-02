<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Exception;

class JobAIAgentService
{
    private $geminiApiKey;
    private $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent';
    private $mongoSearchService;
    
    // Conversation context
    private $lastSearchQuery = null;
    private $lastJobs = [];
    private $selectedJobId = null;
    private $userId = null;

    public function __construct(MongoSearchService $mongoSearchService)
    {
        $this->geminiApiKey = env('GEMINI_API_KEY');
        $this->mongoSearchService = $mongoSearchService;
    }

    /**
     * Main AI processing pipeline
     */
    public function processMessage(string $message, ?string $userId = null): array
    {
        try {
            $this->userId = $userId;
            
            \Log::info("🤖 AI Agent processing: {$message}", ['user_id' => $userId]);
            
            // 1. Build conversation context from user's search history
            $context = $this->buildConversationContext();
            
            // 2. Analyze intent with Gemini
            $intentResult = $this->analyzeWithGemini($message, $context);
            
            \Log::info("🎯 Intent detected:", $intentResult);
            
            // 3. Execute API calls based on intent
            $apiResponse = $this->executeIntent($intentResult);
            
            // 4. Generate human-like response
            $response = $this->generateResponse($intentResult, $apiResponse);
            
            return [
                'success' => true,
                'message' => $response['message'],
                'intent' => $intentResult['intent'],
                'data' => $response['data'] ?? null
            ];
            
        } catch (Exception $e) {
            \Log::error('AI Agent error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Üzgünüm, bir hata oluştu. Tekrar dener misin?',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Build conversation context from user's search history
     */
    private function buildConversationContext(): string
    {
        $context = "Kullanıcı bilgileri:\n";
        
        if ($this->userId) {
            // Get user's behavior analysis
            $behavior = $this->mongoSearchService->getUserBehaviorAnalysis($this->userId);
            
            if ($behavior) {
                $context .= "- Toplam arama: {$behavior['total_searches']}\n";
                
                if (!empty($behavior['preferred_positions'])) {
                    $context .= "- İlgi alanları: " . implode(', ', $behavior['preferred_positions']) . "\n";
                }
                
                if (!empty($behavior['preferred_cities'])) {
                    $context .= "- Tercih şehirler: " . implode(', ', $behavior['preferred_cities']) . "\n";
                }
            }
            
            // Get recent searches
            $recentSearches = $this->mongoSearchService->getRecentSearches($this->userId, 3);
            if (!empty($recentSearches)) {
                $context .= "- Son aramalar: " . implode(', ', $recentSearches) . "\n";
            }
        }
        
        // Current conversation state
        if ($this->lastSearchQuery) {
            $context .= "- Son arama sorgusu: {$this->lastSearchQuery}\n";
        }
        
        if (!empty($this->lastJobs)) {
            $context .= "- " . count($this->lastJobs) . " iş ilanı listede\n";
        }
        
        if ($this->selectedJobId) {
            $context .= "- Seçili iş: {$this->selectedJobId}\n";
        }
        
        return $context;
    }

    /**
     * Analyze user message with Gemini AI
     */
    private function analyzeWithGemini(string $message, string $context): array
    {
        $prompt = <<<EOT
Kullanıcı mesajını analiz et: "$message"

Konuşma durumu:
$context

İş arama sistemi için intent'leri belirle:

Intent seçenekleri:
- SEARCH_JOBS: İş arama ("İstanbul'da web developer arıyorum", "frontend işi")
- LIST_JOBS: Mevcut ilanları listele ("ilanları göster", "ne var")
- SELECT_JOB: İş seçme ("1 numaralıyı seç", "birinci işi")
- APPLY_JOB: Başvuru ("başvur", "başvurmak istiyorum")
- JOB_DETAILS: Detay ("detayları", "şartları neler")
- USER_PROFILE: Profil analizi ("profilim", "öneriler")
- GREETING: Selamlaşma ("merhaba", "selam")
- HELP: Yardım ("neler yapabilirsin", "yardım")
- THANKS: Teşekkür ("teşekkürler", "sağol")

Türk şehirleri: İstanbul, Ankara, İzmir, Antalya, Bursa, Adana, Trabzon

Popüler pozisyonlar: Web Developer, Frontend Developer, Backend Developer, Full Stack Developer, UI/UX Designer, DevOps Engineer, Data Scientist, Mobile Developer, Python Developer, Java Developer, React Developer, Angular Developer, Vue.js Developer, PHP Developer, Laravel Developer

JSON formatında döndür:
{
  "intent": "SEARCH_JOBS",
  "confidence": 0.9,
  "parameters": {
    "position": "pozisyon adı",
    "city": "şehir adı",
    "jobNumber": "seçilen iş numarası",
    "workType": "fulltime/parttime/remote"
  },
  "reasoning": "Neden bu intent seçildi"
}
EOT;

        try {
            $response = Http::timeout(10)->post($this->baseUrl, [
                'headers' => ['Content-Type' => 'application/json'],
                'query' => ['key' => $this->geminiApiKey],
                'json' => [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ]
                ]
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $generatedText = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
                
                // Extract JSON from response
                if (preg_match('/\{[\s\S]*\}/', $generatedText, $matches)) {
                    $intentResult = json_decode($matches[0], true);
                    
                    if ($intentResult) {
                        return $intentResult;
                    }
                }
            }
            
            throw new Exception('Gemini parsing failed');
            
        } catch (Exception $e) {
            \Log::warning('Gemini API failed, using fallback: ' . $e->getMessage());
            return $this->fallbackIntentAnalysis($message);
        }
    }

    /**
     * Fallback intent analysis when Gemini fails
     */
    private function fallbackIntentAnalysis(string $message): array
    {
        $lowerMsg = strtolower($message);
        
        // Greeting
        if (str_contains($lowerMsg, 'merhaba') || str_contains($lowerMsg, 'selam')) {
            return [
                'intent' => 'GREETING',
                'confidence' => 0.9,
                'parameters' => [],
                'reasoning' => 'Greeting detected'
            ];
        }
        
        // Thanks
        if (str_contains($lowerMsg, 'teşekkür') || str_contains($lowerMsg, 'sağol')) {
            return [
                'intent' => 'THANKS',
                'confidence' => 0.9,
                'parameters' => [],
                'reasoning' => 'Thanks detected'
            ];
        }
        
        // Help
        if (str_contains($lowerMsg, 'yardım') || str_contains($lowerMsg, 'neler yapabilir')) {
            return [
                'intent' => 'HELP',
                'confidence' => 0.9,
                'parameters' => [],
                'reasoning' => 'Help request detected'
            ];
        }
        
        // Apply job
        if (str_contains($lowerMsg, 'başvur')) {
            return [
                'intent' => 'APPLY_JOB',
                'confidence' => 0.8,
                'parameters' => [],
                'reasoning' => 'Apply intent detected'
            ];
        }
        
        // Select job (number patterns)
        if (preg_match('/(\d+).*seç|(\d+).*numar|birinci|ikinci|üçüncü/', $lowerMsg, $matches)) {
            $jobNumber = $matches[1] ?? $matches[2] ?? '1';
            return [
                'intent' => 'SELECT_JOB',
                'confidence' => 0.8,
                'parameters' => ['jobNumber' => $jobNumber],
                'reasoning' => 'Job selection detected'
            ];
        }
        
        // Job details
        if (str_contains($lowerMsg, 'detay') || str_contains($lowerMsg, 'şart')) {
            return [
                'intent' => 'JOB_DETAILS',
                'confidence' => 0.8,
                'parameters' => [],
                'reasoning' => 'Details request detected'
            ];
        }
        
        // List jobs
        if (str_contains($lowerMsg, 'listele') || str_contains($lowerMsg, 'göster') || str_contains($lowerMsg, 'ne var')) {
            return [
                'intent' => 'LIST_JOBS',
                'confidence' => 0.8,
                'parameters' => [],
                'reasoning' => 'List request detected'
            ];
        }
        
        // Search jobs (default if contains job-related terms)
        $cities = ['istanbul', 'ankara', 'izmir', 'antalya', 'bursa'];
        $positions = ['developer', 'frontend', 'backend', 'web', 'mobile', 'php', 'javascript', 'react', 'vue', 'angular'];
        
        $foundCity = null;
        $foundPosition = null;
        
        foreach ($cities as $city) {
            if (str_contains($lowerMsg, $city)) {
                $foundCity = ucfirst($city);
                break;
            }
        }
        
        foreach ($positions as $position) {
            if (str_contains($lowerMsg, $position)) {
                $foundPosition = $position;
                break;
            }
        }
        
        if ($foundCity || $foundPosition || str_contains($lowerMsg, 'iş') || str_contains($lowerMsg, 'ara')) {
            return [
                'intent' => 'SEARCH_JOBS',
                'confidence' => 0.7,
                'parameters' => [
                    'city' => $foundCity,
                    'position' => $foundPosition
                ],
                'reasoning' => 'Job search detected'
            ];
        }
        
        // Default: help
        return [
            'intent' => 'HELP',
            'confidence' => 0.5,
            'parameters' => [],
            'reasoning' => 'Default fallback'
        ];
    }

    /**
     * Execute intent-based actions
     */
    private function executeIntent(array $intentResult): array
    {
        $intent = $intentResult['intent'];
        $params = $intentResult['parameters'] ?? [];
        
        switch ($intent) {
            case 'SEARCH_JOBS':
                return $this->searchJobs($params);
                
            case 'LIST_JOBS':
                return $this->listJobs();
                
            case 'SELECT_JOB':
                return $this->selectJob($params);
                
            case 'APPLY_JOB':
                return $this->applyJob();
                
            case 'JOB_DETAILS':
                return $this->getJobDetails();
                
            case 'USER_PROFILE':
                return $this->getUserProfile();
                
            case 'GREETING':
            case 'HELP':
            case 'THANKS':
                return $this->handleCasual($intent);
                
            default:
                return [
                    'success' => false,
                    'message' => 'Bu konuda yardımcı olamıyorum.'
                ];
        }
    }

    /**
     * Search jobs via Supabase API
     */
    private function searchJobs(array $params): array
    {
        try {
            $searchData = [
                'position' => $params['position'] ?? '',
                'city' => $params['city'] ?? '',
                'work_type' => $params['workType'] ?? '',
                'user_id' => $this->userId,
                'limit' => 5
            ];
            
            // Call job search API
            $response = Http::timeout(10)->post(
                'https://ai-api.elastic-swartz.213-238-168-122.plesk.page/api/chat',
                $searchData
            );
            
            if ($response->successful()) {
                $data = $response->json();
                $this->lastJobs = $data['data'] ?? [];
                $this->lastSearchQuery = trim(($params['position'] ?? '') . ' ' . ($params['city'] ?? ''));
                
                // Save search analytics
                $this->mongoSearchService->saveSearch([
                    'user_id' => $this->userId,
                    'search_query' => $this->lastSearchQuery,
                    'position' => $params['position'] ?? '',
                    'city' => $params['city'] ?? '',
                    'filters' => $params,
                    'results_count' => count($this->lastJobs)
                ]);
                
                return [
                    'success' => true,
                    'jobs' => $this->lastJobs,
                    'total' => count($this->lastJobs)
                ];
            }
            
            throw new Exception('Job search API failed');
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'İş arama sırasında hata oluştu: ' . $e->getMessage()
            ];
        }
    }

    /**
     * List current jobs
     */
    private function listJobs(): array
    {
        return [
            'success' => true,
            'jobs' => $this->lastJobs,
            'total' => count($this->lastJobs)
        ];
    }

    /**
     * Select a job from the list
     */
    private function selectJob(array $params): array
    {
        $jobNumber = (int) ($params['jobNumber'] ?? 1);
        
        if (empty($this->lastJobs)) {
            return [
                'success' => false,
                'message' => 'Önce iş araması yapmalısınız!'
            ];
        }
        
        if ($jobNumber < 1 || $jobNumber > count($this->lastJobs)) {
            return [
                'success' => false,
                'message' => 'Geçersiz iş numarası. 1-' . count($this->lastJobs) . ' arası seçin.'
            ];
        }
        
        $selectedJob = $this->lastJobs[$jobNumber - 1];
        $this->selectedJobId = $selectedJob['id'];
        
        return [
            'success' => true,
            'job' => $selectedJob,
            'jobNumber' => $jobNumber
        ];
    }

    /**
     * Apply to selected job
     */
    private function applyJob(): array
    {
        if (!$this->selectedJobId) {
            return [
                'success' => false,
                'message' => 'Önce bir iş seçmelisiniz!'
            ];
        }
        
        if (!$this->userId) {
            return [
                'success' => false,
                'message' => 'Başvuru için giriş yapmalısınız!',
                'requiresLogin' => true
            ];
        }
        
        // Here you would call your job application API
        return [
            'success' => true,
            'message' => 'Başvurunuz alındı!',
            'jobId' => $this->selectedJobId
        ];
    }

    /**
     * Get job details
     */
    private function getJobDetails(): array
    {
        if (!$this->selectedJobId) {
            return [
                'success' => false,
                'message' => 'Önce bir iş seçmelisiniz!'
            ];
        }
        
        $selectedJob = collect($this->lastJobs)->firstWhere('id', $this->selectedJobId);
        
        return [
            'success' => true,
            'job' => $selectedJob
        ];
    }

    /**
     * Get user profile analysis
     */
    private function getUserProfile(): array
    {
        if (!$this->userId) {
            return [
                'success' => false,
                'message' => 'Profil analizi için giriş yapmalısınız!'
            ];
        }
        
        $profile = $this->mongoSearchService->getAIUserProfile($this->userId);
        
        return [
            'success' => true,
            'profile' => $profile
        ];
    }

    /**
     * Handle casual conversation
     */
    private function handleCasual(string $intent): array
    {
        $messages = [
            'GREETING' => 'Merhaba! 👋 Size iş aramada nasıl yardımcı olabilirim? İstediğiniz pozisyon ve şehri söyleyin.',
            'HELP' => '🤖 Size şu konularda yardımcı olabilirim:\n\n• İş arama ("İstanbul web developer")\n• İş listesi gösterme\n• İş seçimi ("1 numaralıyı seç")\n• İş başvurusu\n• Profil analizi\n\nNe yapmak istersiniz?',
            'THANKS' => 'Rica ederim! 😊 Başka yardımcı olabileceğim bir şey var mı?'
        ];
        
        return [
            'success' => true,
            'message' => $messages[$intent] ?? 'Size nasıl yardımcı olabilirim?'
        ];
    }

    /**
     * Generate human-like response
     */
    private function generateResponse(array $intentResult, array $apiResponse): array
    {
        $intent = $intentResult['intent'];
        
        switch ($intent) {
            case 'SEARCH_JOBS':
                if ($apiResponse['success']) {
                    $jobs = $apiResponse['jobs'];
                    $total = $apiResponse['total'];
                    
                    if ($total > 0) {
                        $message = "🎯 {$total} adet iş buldum:\n\n";
                        
                        foreach ($jobs as $index => $job) {
                            $num = $index + 1;
                            $message .= "{$num}. **{$job['position']}** - {$job['city']}\n";
                            $message .= "   💰 " . ($job['salary_min'] ? number_format($job['salary_min']) . '₺+' : 'Maaş belirtilmemiş') . "\n";
                            $message .= "   🏢 " . ($job['company_name'] ?? 'Şirket belirtilmemiş') . "\n\n";
                        }
                        
                        $message .= "Hangi işi seçmek istersiniz? (örn: '1 numaralıyı seç')";
                        
                        return [
                            'message' => $message,
                            'data' => ['jobs' => $jobs]
                        ];
                    } else {
                        return [
                            'message' => "😔 Aradığınız kriterlerde iş bulamadım. Farklı şehir veya pozisyon deneyin."
                        ];
                    }
                } else {
                    return [
                        'message' => "❌ İş arama sırasında sorun oluştu: " . $apiResponse['message']
                    ];
                }
                
            case 'SELECT_JOB':
                if ($apiResponse['success']) {
                    $job = $apiResponse['job'];
                    $message = "✅ {$apiResponse['jobNumber']} numaralı işi seçtiniz:\n\n";
                    $message .= "**{$job['position']}**\n";
                    $message .= "🏢 {$job['company_name']}\n";
                    $message .= "📍 {$job['city']}\n";
                    $message .= "💰 " . ($job['salary_min'] ? number_format($job['salary_min']) . '₺+' : 'Maaş belirtilmemiş') . "\n\n";
                    $message .= "Bu işe başvurmak ister misiniz? 'Başvur' yazın.";
                    
                    return [
                        'message' => $message,
                        'data' => ['selectedJob' => $job]
                    ];
                } else {
                    return ['message' => "❌ " . $apiResponse['message']];
                }
                
            case 'APPLY_JOB':
                if ($apiResponse['success']) {
                    if (isset($apiResponse['requiresLogin'])) {
                        return [
                            'message' => "🔐 Başvuru için giriş yapmanız gerekiyor. Giriş yaptıktan sonra tekrar deneyin.",
                            'data' => ['requiresLogin' => true]
                        ];
                    }
                    return [
                        'message' => "🎉 Başvurunuz başarıyla gönderildi! Şirket size en kısa sürede dönüş yapacak."
                    ];
                } else {
                    return ['message' => "❌ " . $apiResponse['message']];
                }
                
            case 'USER_PROFILE':
                if ($apiResponse['success']) {
                    $profile = $apiResponse['profile'];
                    $behavior = $profile['behavior_analysis'];
                    
                    $message = "📊 Profil Analiziniz:\n\n";
                    $message .= "🔍 Toplam arama: {$behavior['total_searches']}\n";
                    
                    if (!empty($behavior['preferred_positions'])) {
                        $message .= "💼 İlgi alanları: " . implode(', ', $behavior['preferred_positions']) . "\n";
                    }
                    
                    if (!empty($behavior['preferred_cities'])) {
                        $message .= "🌍 Tercih şehirler: " . implode(', ', $behavior['preferred_cities']) . "\n";
                    }
                    
                    return ['message' => $message];
                } else {
                    return ['message' => "❌ " . $apiResponse['message']];
                }
                
            default:
                return [
                    'message' => $apiResponse['message'] ?? 'İşlem tamamlandı.'
                ];
        }
    }
}