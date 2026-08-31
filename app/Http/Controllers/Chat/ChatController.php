<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Services\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatController extends Controller
{
    public function __construct(
        private readonly ChatService $chatService,
    ) {}

    /**
     * Show the chat page with conversations list.
     */
    public function index(Request $request): Response
    {
        $conversations = Conversation::where('user_id', $request->user()->id)
            ->orderByDesc('updated_at')
            ->get(['id', 'title', 'created_at', 'updated_at']);

        return Inertia::render('chat/index', [
            'conversations' => $conversations,
        ]);
    }

    /**
     * Create a new conversation.
     */
    public function store(Request $request): JsonResponse
    {
        $conversation = Conversation::create([
            'user_id' => $request->user()->id,
        ]);

        return response()->json([
            'id' => $conversation->id,
            'title' => $conversation->title,
            'created_at' => $conversation->created_at,
        ]);
    }

    /**
     * Get messages for a conversation.
     */
    public function show(Request $request, Conversation $conversation): JsonResponse
    {
        abort_unless($conversation->user_id === $request->user()->id, 403);

        $messages = $conversation->messages()
            ->orderBy('created_at')
            ->get(['id', 'role', 'content', 'created_at']);

        return response()->json([
            'id' => $conversation->id,
            'title' => $conversation->title,
            'messages' => $messages,
        ]);
    }

    /**
     * Send a message and stream the assistant response via SSE.
     */
    public function message(Request $request, Conversation $conversation): StreamedResponse
    {
        abort_unless($conversation->user_id === $request->user()->id, 403);

        $validated = $request->validate([
            'content' => 'required|string|max:4000',
        ]);

        return response()->stream(function () use ($conversation, $validated) {
            $stream = $this->chatService->handle($conversation, $validated['content']);

            foreach ($stream as $delta) {
                echo "data: ".json_encode(['type' => 'text-delta', 'delta' => $delta])."\n\n";
                if (connection_aborted()) {
                    break;
                }
            }

            echo "data: ".json_encode(['type' => 'done'])."\n\n";
            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
