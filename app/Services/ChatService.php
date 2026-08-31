<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Str;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Streaming\Events\TextDeltaEvent;
use Prism\Prism\ValueObjects\Messages\AssistantMessage;
use Prism\Prism\ValueObjects\Messages\SystemMessage;
use Prism\Prism\ValueObjects\Messages\UserMessage;

class ChatService
{
    private const SYSTEM_PROMPT = <<<'PROMPT'
Eres el asistente virtual de SignaDocs, una plataforma de gestión y firma electrónica de documentos.
Tu objetivo es ayudar a los usuarios a entender qué pueden hacer en la plataforma, cómo usarla y resolver sus dudas.

Ten acceso al manual de usuario a través de la herramienta `ask_manual`. Úsala siempre que el usuario pregunte sobre funcionalidades, permisos, uso de la plataforma, o cualquier cosa que requiera consultar el manual.

Responde siempre en español de forma clara y concisa. Si no sabes la respuesta, indícalo honestamente.
PROMPT;

    public function __construct(
        private readonly McpClient $mcpClient,
    ) {}

    /**
     * Handle a chat message and return a streaming generator.
     *
     * @return \Generator<int, string>
     */
    public function handle(Conversation $conversation, string $userMessage): \Generator
    {
        $history = $this->buildConversationHistory($conversation);
        $history[] = new UserMessage($userMessage);

        $tools = $this->mcpClient->getPrismTools();

        $stream = Prism::text()
            ->using(Provider::Gemini, config('services.gemini.model'))
            ->withSystemPrompt(self::SYSTEM_PROMPT)
            ->withMessages($history)
            ->withTools($tools)
            ->withMaxSteps(3)
            ->withClientOptions(['timeout' => 60])
            ->asStream();

        $fullResponse = '';

        foreach ($stream as $event) {
            if ($event instanceof TextDeltaEvent) {
                $delta = $event->delta;
                $fullResponse .= $delta;
                yield $delta;
            }
        }

        if (trim($fullResponse) !== '') {
            Message::create([
                'conversation_id' => $conversation->id,
                'role' => 'user',
                'content' => $userMessage,
            ]);

            Message::create([
                'conversation_id' => $conversation->id,
                'role' => 'assistant',
                'content' => $fullResponse,
            ]);

            if ($conversation->title === null) {
                $conversation->update([
                    'title' => Str::limit($userMessage, 60),
                ]);
            }
        }
    }

    /**
     * Build Prism message objects from the conversation history.
     *
     * @return array<int, SystemMessage|UserMessage|AssistantMessage>
     */
    private function buildConversationHistory(Conversation $conversation): array
    {
        $messages = $conversation->messages()
            ->orderBy('created_at')
            ->get();

        $history = [];

        /** @var Message $msg */
        foreach ($messages as $msg) {
            if ($msg->role === 'user') {
                $history[] = new UserMessage($msg->content);
            } elseif ($msg->role === 'assistant') {
                $history[] = new AssistantMessage($msg->content);
            }
        }

        return $history;
    }
}
