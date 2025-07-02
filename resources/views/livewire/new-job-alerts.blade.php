<div
    wire:poll.10s="pollNewJobs"
    class="fixed bottom-4 right-4 w-80 bg-white shadow-lg rounded-lg overflow-hidden"
>
    <div class="bg-blue-600 text-white px-4 py-2 font-semibold">
        Yeni İş İlanları
    </div>

    @if(count($alerts) === 0)
        <div class="p-4 text-gray-600">
            Yeni ilan yok.
        </div>
    @else
        <ul class="divide-y">
            @foreach($alerts as $job)
                <li class="px-4 py-3 hover:bg-gray-50 transition">
                    <a wire:click="goToJob({{ $job['id'] }})" class="cursor-pointer block">
                        <div class="font-semibold text-gray-800">
                            {{ $job['position'] }} – {{ $job['company_name'] ?? 'Firma' }}
                        </div>
                        <div class="text-sm text-gray-500">
                            {{ $job['city'] }}, {{ \Carbon\Carbon::parse($job['created_at'])->diffForHumans() }}
                        </div>
                    </a>
                </li>
            @endforeach
        </ul>
    @endif

    @if(count($alerts) > 0)
    <div class="text-center p-2 border-t">
        <button
            wire:click="$set('alerts', [])"
            class="text-sm text-red-600 hover:underline"
        >
            Tümünü Temizle
        </button>
    </div>
    @endif
</div>
