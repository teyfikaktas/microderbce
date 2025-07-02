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
        // Session-based authentication check
        if (session('user_id')) {
            $this->loadChatHistory();
        }
    }

    public function openChat()
    {
        if (session('user_id')) {
            $this->isOpen = true;
            if (empty($this->messages)) {
                $this->loadChatHistory();
            }
        } else {
            // Redirect to login
            return redirect('/login');
        }
    }

    public function closeChat()
    {
        $this->isOpen = false;
    }

    public function sendMessage()
    {
        if (!session('user_id')) {
            $this->error = 'Lütfen giriş yapın.';
            return redirect('/login');
        }

        if (empty(trim($this->newMessage))) {
            return;
        }

        $this->isLoading = true;
        $this->isTyping = true;
        $this->error = null;

        try {
            // Add user message to chat
            $this->messages[] = [
                'message' => $this->newMessage,
                'response' => null,
                'is_user' => true,
                'timestamp' => now()->toISOString(),
                'intent' => null
            ];

            $messageToSend = $this->newMessage;
            $this->newMessage = '';

            // Send to AI API via main site proxy
            $response = Http::timeout(30)->post(route('ai.chat'), [
                'message' => $messageToSend
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
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
                } else {
                    $this->error = $data['message'] ?? 'AI yanıt veremedi.';
                }
            } else {
                $this->error = 'AI servisi şu anda kullanılamıyor.';
            }

        } catch (\Exception $e) {
            $this->error = 'Bağlantı hatası oluştu.';
        } finally {
            $this->isLoading = false;
            $this->isTyping = false;
        }

        // Auto scroll to bottom
        $this->dispatch('scrollToBottom');
    }

    public function loadChatHistory()
    {
        if (!session('user_id')) {
            return;
        }

        try {
            $response = Http::timeout(10)->get(route('ai.history'));

            if ($response->successful()) {
                $data = $response->json();
                
                if ($data['success'] && !empty($data['messages'])) {
                    $this->messages = collect($data['messages'])->map(function ($msg) {
                        return [
                            'message' => $msg['message'],
                            'response' => $msg['response'],
                            'is_user' => false, // These are conversation pairs
                            'timestamp' => $msg['created_at'],
                            'intent' => $msg['intent']
                        ];
                    })->toArray();
                }
            }
        } catch (\Exception $e) {
            // Silent fail for history loading
        }
    }

    public function clearChat()
    {
        $this->messages = [];
        
        try {
            Http::timeout(5)->post('https://ai-api.elastic-swartz.213-238-168-122.plesk.page/api/chat/clear-context', [
                'user_id' => session('user_id')
            ]);
        } catch (\Exception $e) {
            // Silent fail
        }
    }

    public function quickSearch($searchQuery)
    {
        $this->newMessage = $searchQuery;
        $this->sendMessage();
    }

    public function render()
    {
        return view('livewire.ai-chat-widget');
    }
}