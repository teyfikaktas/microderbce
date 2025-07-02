<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\JobAIAgentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AIChatController extends Controller
{
    private $aiAgent;

    public function __construct(JobAIAgentService $aiAgent)
    {
        $this->aiAgent = $aiAgent;
    }

    /**
     * Process chat message
     */
    public function chat(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'message' => 'required|string|max:1000',
                'user_id' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $message = $request->input('message');
            $userId = $request->input('user_id') ?: session('user_id');

            \Log::info('AI Chat Request:', [
                'message' => $message,
                'user_id' => $userId,
                'session_id' => session()->getId()
            ]);

            $response = $this->aiAgent->processMessage($message, $userId);

            return response()->json([
                'success' => true,
                'response' => $response['message'],
                'intent' => $response['intent'] ?? null,
                'data' => $response['data'] ?? null,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            \Log::error('AI Chat Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Üzgünüm, şu anda size yardım edemiyorum. Lütfen daha sonra tekrar deneyin.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal error'
            ], 500);
        }
    }

    /**
     * Get chat history (if you want to store in database)
     */
    public function getHistory(Request $request)
    {
        $userId = $request->input('user_id') ?: session('user_id');
        
        // Here you can implement chat history storage
        // For now, just return empty array
        
        return response()->json([
            'success' => true,
            'messages' => [],
            'user_id' => $userId
        ]);
    }

    /**
     * Clear chat context
     */
    public function clearContext(Request $request)
    {
        $userId = $request->input('user_id') ?: session('user_id');
        
        // Reset AI agent context here if needed
        
        return response()->json([
            'success' => true,
            'message' => 'Chat context cleared'
        ]);
    }
}