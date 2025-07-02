<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;

class AIChatWidget extends Component
{
    public $messages = [];
    public $newMessage = '';
    public $isOpen = false;
    public $isLoading = false;
    public $error = null;
    public $isTyping = false;

    protected $listeners = [
        'openAIChat' => 'openChat',
        'closeAIChat' => 'closeChat'
    ];

    public function mount()
    {
        \Log::info('🔵 AIChatWidget mounted', ['user_id' => session('user_id')]);
        
        // Session-based authentication check
        if (session('user_id')) {
            $this->loadChatHistory();
        }
    }

    public function openChat()
    {
        \Log::info('🔵 openChat called', ['user_id' => session('user_id')]);
        
        if (session('user_id')) {
            $this->isOpen = true;
            if (empty($this->messages)) {
                $this->loadChatHistory();
            }
        } else {
            \Log::warning('🔴 openChat - No user session, redirecting to login');
            // Redirect to login
            return redirect('/login');
        }
    }

    public function closeChat()
    {
        \Log::info('🔵 closeChat called');
        $this->isOpen = false;
    }

    public function sendMessage()
    {
        \Log::info('🚀 sendMessage called', [
            'user_id' => session('user_id'),
            'message' => $this->newMessage,
            'session_id' => session()->getId()
        ]);

        if (!session('user_id')) {
            \Log::error('🔴 sendMessage - No user session');
            $this->error = 'Lütfen giriş yapın.';
            return redirect('/login');
        }
    
        if (empty(trim($this->newMessage))) {
            \Log::warning('🔴 sendMessage - Empty message');
            return;
        }
    
        $this->isLoading = true;
        $this->isTyping = true;
        $this->error = null;
        
        \Log::info('🔵 sendMessage - Starting process', ['loading' => true]);
    
        try {
            // Add user message to chat
            $this->messages[] = [
                'message' => $this->newMessage,
                'response' => null,
                'is_user' => true,
                'timestamp' => now()->toISOString(),
                'intent' => null
            ];
            
            \Log::info('🔵 sendMessage - User message added to chat', ['messages_count' => count($this->messages)]);
    
            $messageToSend = $this->newMessage;
            $this->newMessage = '';
            
            $apiUrl = 'https://ai-api.elastic-swartz.213-238-168-122.plesk.page/api/chat';
            $requestData = [
                'message' => $messageToSend,
                'user_id' => session('user_id'),
                'session_id' => session()->getId()
            ];
            
            \Log::info('🔵 sendMessage - Making HTTP request', [
                'url' => $apiUrl,
                'data' => $requestData
            ]);
    
            // Doğrudan AI API'ye gönder (route proxy kullanmak yerine)
            $response = Http::timeout(30)->post($apiUrl, $requestData);
            
            \Log::info('🔵 sendMessage - HTTP response received', [
                'status' => $response->status(),
                'successful' => $response->successful(),
                'body_preview' => substr($response->body(), 0, 200)
            ]);
    
            if ($response->successful()) {
                $data = $response->json();
                
                \Log::info('🔵 sendMessage - Response parsed', [
                    'success' => $data['success'] ?? 'unknown',
                    'intent' => $data['intent'] ?? 'none',
                    'response_length' => strlen($data['response'] ?? '')
                ]);
                
                if ($data['success']) {
                    // Add AI response to chat
                    $this->messages[] = [
                        'message' => $messageToSend,
                        'response' => $data['response'],
                        'is_user' => false,
                        'timestamp' => $data['timestamp'] ?? now()->toISOString(),
                        'intent' => $data['intent'] ?? null,
                        'data' => $data['data'] ?? null
                    ];
                    
                    \Log::info('🟢 sendMessage - AI response added successfully', [
                        'messages_count' => count($this->messages),
                        'intent' => $data['intent'] ?? null
                    ]);
                } else {
                    $errorMsg = $data['message'] ?? 'AI yanıt veremedi.';
                    $this->error = $errorMsg;
                    \Log::error('🔴 sendMessage - AI API returned success=false', ['error' => $errorMsg]);
                }
            } else {
                $errorMsg = 'AI servisi şu anda kullanılamıyor. Status: ' . $response->status();
                $this->error = $errorMsg;
                \Log::error('🔴 sendMessage - HTTP request failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
            }
    
        } catch (\Exception $e) {
            $errorMsg = 'Bağlantı hatası oluştu: ' . $e->getMessage();
            $this->error = $errorMsg;
            \Log::error('🔴 sendMessage - Exception caught', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        } finally {
            $this->isLoading = false;
            $this->isTyping = false;
            \Log::info('🔵 sendMessage - Process completed', ['loading' => false]);
        }
    
        // Auto scroll to bottom
        $this->dispatch('scrollToBottom');
        \Log::info('🔵 sendMessage - ScrollToBottom dispatched');
    }

    public function loadChatHistory()
    {
        \Log::info('🔵 loadChatHistory called', ['user_id' => session('user_id')]);
        
        if (!session('user_id')) {
            \Log::warning('🔴 loadChatHistory - No user session');
            return;
        }
    
        try {
            $historyUrl = "https://ai-api.elastic-swartz.213-238-168-122.plesk.page/api/chat/history/" . session('user_id');
            \Log::info('🔵 loadChatHistory - Making request', ['url' => $historyUrl]);
            
            // Doğrudan AI API'den history al
            $response = Http::timeout(10)->get($historyUrl);
            
            \Log::info('🔵 loadChatHistory - Response received', [
                'status' => $response->status(),
                'successful' => $response->successful()
            ]);
    
            if ($response->successful()) {
                $data = $response->json();
                
                if ($data['success'] && !empty($data['messages'])) {
                    $this->messages = collect($data['messages'])->map(function ($msg) {
                        return [
                            'message' => $msg['message'],
                            'response' => $msg['response'],
                            'is_user' => false,
                            'timestamp' => $msg['created_at'],
                            'intent' => $msg['intent']
                        ];
                    })->toArray();
                    
                    \Log::info('🟢 loadChatHistory - Messages loaded', ['count' => count($this->messages)]);
                } else {
                    \Log::info('🔵 loadChatHistory - No messages or success=false');
                }
            } else {
                \Log::warning('🔴 loadChatHistory - Request failed', ['status' => $response->status()]);
            }
        } catch (\Exception $e) {
            \Log::error('🔴 loadChatHistory - Exception', ['error' => $e->getMessage()]);
            // Silent fail for history loading
        }
    }

    public function clearChat()
    {
        \Log::info('🔵 clearChat called');
        $this->messages = [];
        
        try {
            $clearUrl = 'https://ai-api.elastic-swartz.213-238-168-122.plesk.page/api/chat/clear-context';
            \Log::info('🔵 clearChat - Making request', ['url' => $clearUrl]);
            
            Http::timeout(5)->post($clearUrl, [
                'user_id' => session('user_id')
            ]);
            
            \Log::info('🟢 clearChat - Request completed');
        } catch (\Exception $e) {
            \Log::error('🔴 clearChat - Exception', ['error' => $e->getMessage()]);
            // Silent fail
        }
    }

    public function quickSearch($searchQuery)
    {
        \Log::info('🔵 quickSearch called', ['query' => $searchQuery]);
        $this->newMessage = $searchQuery;
        $this->sendMessage();
    }

    public function render()
    {
        \Log::info('🔵 render called', [
            'isOpen' => $this->isOpen,
            'messages_count' => count($this->messages),
            'isLoading' => $this->isLoading
        ]);
        
        return view('livewire.a-i-chat-widget');
    }
}