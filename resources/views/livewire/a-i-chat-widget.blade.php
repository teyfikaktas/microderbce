<div>
    <!-- AI Chat Button (Fixed Position) -->
    @if(!$isOpen)
    <div class="fixed bottom-6 right-6 z-50">
        <button 
            wire:click="openChat"
            class="bg-blue-600 hover:bg-blue-700 text-white rounded-full p-4 shadow-lg transition-all duration-200 transform hover:scale-105"
            title="AI İş Asistanı"
        >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-4l-4 4z"></path>
            </svg>
            <!-- Notification badge for new features -->
            <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                🤖
            </span>
        </button>
    </div>
    @endif

    <!-- AI Chat Window -->
    @if($isOpen)
    <div class="fixed bottom-6 right-6 z-50 w-96 h-[600px] bg-white rounded-lg shadow-2xl border border-gray-200 flex flex-col">
        
        <!-- Chat Header -->
        <div class="bg-blue-600 text-white p-4 rounded-t-lg flex justify-between items-center">
            <div class="flex items-center space-x-2">
                <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                    🤖
                </div>
                <div>
                    <h3 class="font-semibold">AI İş Asistanı</h3>
                    <p class="text-xs text-blue-100">
                        @if($isTyping)
                            Yazıyor...
                        @else
                            Size nasıl yardımcı olabilirim?
                        @endif
                    </p>
                </div>
            </div>
            <div class="flex space-x-2">
                <button 
                    wire:click="clearChat"
                    class="text-blue-100 hover:text-white transition-colors"
                    title="Sohbeti Temizle"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                </button>
                <button 
                    wire:click="closeChat"
                    class="text-blue-100 hover:text-white transition-colors"
                    title="Kapat"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Quick Actions -->
        @if(empty($messages))
        <div class="p-4 border-b border-gray-100">
            <p class="text-sm text-gray-600 mb-3">Hızlı başlangıç:</p>
            <div class="flex flex-wrap gap-2">
                <button 
                    wire:click="quickSearch('Profilimi analiz et')"
                    class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1 rounded-full text-xs transition-colors"
                >
                    📊 Profilim
                </button>
                <button 
                    wire:click="quickSearch('İstanbul web developer')"
                    class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1 rounded-full text-xs transition-colors"
                >
                    🔍 İş Ara
                </button>
                <button 
                    wire:click="quickSearch('Aynı tür işler arıyorum')"
                    class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1 rounded-full text-xs transition-colors"
                >
                    🔄 Benzer İşler
                </button>
                <button 
                    wire:click="quickSearch('Neler yapabilirsin')"
                    class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1 rounded-full text-xs transition-colors"
                >
                    ❓ Yardım
                </button>
            </div>
        </div>
        @endif

        <!-- Chat Messages -->
        <div class="flex-1 overflow-y-auto p-4 space-y-4" id="chat-messages">
            @if(empty($messages))
                <div class="text-center py-8">
                    <div class="text-6xl mb-4">🤖</div>
                    <p class="text-gray-600">Merhaba! Size iş aramada nasıl yardımcı olabilirim?</p>
                </div>
            @endif

            @foreach($messages as $index => $message)
                @if($message['is_user'])
                    <!-- User Message -->
                    <div class="flex justify-end">
                        <div class="bg-blue-600 text-white rounded-lg px-4 py-2 max-w-xs">
                            <p class="text-sm">{{ $message['message'] }}</p>
                            <span class="text-xs text-blue-100">
                                {{ \Carbon\Carbon::parse($message['timestamp'])->format('H:i') }}
                            </span>
                        </div>
                    </div>
                @else
                    <!-- AI Response -->
                    <div class="flex justify-start">
                        <div class="bg-gray-100 text-gray-800 rounded-lg px-4 py-2 max-w-xs">
                            <div class="flex items-center space-x-1 mb-1">
                                <span class="text-xs">🤖</span>
                                @if($message['intent'])
                                <span class="text-xs bg-blue-100 text-blue-800 px-2 py-0.5 rounded">
                                    {{ $message['intent'] }}
                                </span>
                                @endif
                            </div>
                            
                            <div class="text-sm whitespace-pre-line">
                                {!! nl2br(e($message['response'])) !!}
                            </div>
                            
                            @if(isset($message['data']['jobs']) && !empty($message['data']['jobs']))
                            <div class="mt-2 space-y-1">
                                @foreach(array_slice($message['data']['jobs'], 0, 3) as $job)
                                <div class="bg-white p-2 rounded border text-xs">
                                    <div class="font-medium">{{ $job['position'] }}</div>
                                    <div class="text-gray-600">{{ $job['city'] }} • {{ $job['company_name'] ?? 'Şirket' }}</div>
                                </div>
                                @endforeach
                            </div>
                            @endif
                            
                            <span class="text-xs text-gray-500">
                                {{ \Carbon\Carbon::parse($message['timestamp'])->format('H:i') }}
                            </span>
                        </div>
                    </div>
                @endif
            @endforeach

            @if($isLoading)
                <div class="flex justify-start">
                    <div class="bg-gray-100 text-gray-800 rounded-lg px-4 py-2">
                        <div class="flex items-center space-x-2">
                            <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"></div>
                            <span class="text-sm">AI düşünüyor...</span>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Error Message -->
        @if($error)
        <div class="p-3 bg-red-50 border-t border-red-100">
            <p class="text-sm text-red-600">{{ $error }}</p>
        </div>
        @endif

        <!-- Message Input -->
        <div class="p-4 border-t border-gray-100">
            <form wire:submit.prevent="sendMessage" class="flex space-x-2">
                <input 
                    type="text" 
                    wire:model="newMessage"
                    wire:keydown.enter.prevent="sendMessage"
                    placeholder="Mesajınızı yazın..."
                    class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    @if($isLoading) disabled @endif
                >
                <button 
                    type="submit"
                    @if($isLoading || empty(trim($newMessage))) disabled @endif
                    class="bg-blue-600 hover:bg-blue-700 disabled:bg-gray-300 text-white px-4 py-2 rounded-lg transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                    </svg>
                </button>
            </form>
        </div>
    </div>
    @endif
</div>

<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('scrollToBottom', () => {
            const chatMessages = document.getElementById('chat-messages');
            if (chatMessages) {
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
        });
    });
</script>