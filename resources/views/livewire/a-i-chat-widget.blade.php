<div>
    <!-- AI Chat Button -->
    @if(!$isOpen)
    <div class="fixed bottom-6 right-6 z-50">
        <button 
            wire:click="openChat"
            class="bg-blue-600 hover:bg-blue-700 text-white rounded-full p-4 shadow-lg"
            title="AI İş Asistanı"
        >
            🤖
        </button>
    </div>
    @endif

    <!-- AI Chat Window -->
    @if($isOpen)
    <div class="fixed bottom-6 right-6 z-50 w-96 h-[600px] bg-white rounded-lg shadow-2xl border flex flex-col">
        
        <!-- Header -->
        <div class="bg-blue-600 text-white p-4 rounded-t-lg flex justify-between items-center">
            <h3 class="font-semibold">AI İş Asistanı</h3>
            <button wire:click="closeChat" class="text-white">✕</button>
        </div>

        <!-- Quick Actions -->
        @if(empty($messages))
        <div class="p-4 border-b">
            <div class="flex flex-wrap gap-2">
                <button wire:click="quickSearch('İstanbul web developer')" class="bg-gray-100 px-3 py-1 rounded text-sm">
                    İş Ara
                </button>
                <button wire:click="quickSearch('Profilimi analiz et')" class="bg-gray-100 px-3 py-1 rounded text-sm">
                    Profilim
                </button>
            </div>
        </div>
        @endif

        <!-- Messages -->
        <div class="flex-1 overflow-y-auto p-4 space-y-4">
            @if(empty($messages))
                <div class="text-center py-8">
                    <p class="text-gray-600">Merhaba! Size nasıl yardımcı olabilirim?</p>
                </div>
            @endif

            @foreach($messages as $message)
                @if($message['is_user'])
                    <!-- User Message -->
                    <div class="flex justify-end">
                        <div class="bg-blue-600 text-white rounded-lg px-4 py-2 max-w-xs">
                            {{ $message['message'] }}
                        </div>
                    </div>
                @else
                    <!-- AI Response -->
                    <div class="flex justify-start">
                        <div class="bg-gray-100 rounded-lg px-4 py-2 max-w-xs">
                            {{ $message['response'] }}
                        </div>
                    </div>
                @endif
            @endforeach

            @if($isLoading)
                <div class="flex justify-start">
                    <div class="bg-gray-100 rounded-lg px-4 py-2">
                        AI düşünüyor...
                    </div>
                </div>
            @endif
        </div>

        <!-- Error -->
        @if($error)
        <div class="p-3 bg-red-50 border-t">
            <p class="text-sm text-red-600">{{ $error }}</p>
        </div>
        @endif

        <!-- Input -->
        <div class="p-4 border-t">
            <form wire:submit.prevent="sendMessage" class="flex space-x-2">
                <input 
                    type="text" 
                    wire:model="newMessage"
                    placeholder="Mesajınızı yazın..."
                    class="flex-1 border rounded-lg px-3 py-2 text-sm"
                    @if($isLoading) disabled @endif
                >
                <button 
                    type="submit"
                    class="bg-blue-600 text-white px-4 py-2 rounded-lg"
                    @if($isLoading) disabled @endif
                >
                    Gönder
                </button>
            </form>
        </div>
    </div>
    @endif
</div>