import { Head } from '@inertiajs/react';
import { useCallback, useState } from 'react';
import Heading from '@/components/heading';
import ConversationList from '@/components/chat/conversation-list';
import MessageInput from '@/components/chat/message-input';
import MessageList from '@/components/chat/message-list';
import { dashboard } from '@/routes';

type Conversation = {
    id: number;
    title: string | null;
    created_at: string;
    updated_at: string;
};

type Message = {
    id?: number;
    role: 'user' | 'assistant';
    content: string;
};

type Props = {
    conversations: Conversation[];
};

export default function ChatIndex({ conversations: initialConversations }: Props) {
    const [conversations, setConversations] = useState(initialConversations);
    const [activeConversationId, setActiveConversationId] = useState<number | null>(null);
    const [messages, setMessages] = useState<Message[]>([]);
    const [streamingContent, setStreamingContent] = useState('');
    const [isStreaming, setIsStreaming] = useState(false);

    const selectConversation = useCallback(async (id: number) => {
        setActiveConversationId(id);
        setStreamingContent('');

        try {
            const response = await fetch(`/chat/conversations/${id}`);
            const data = await response.json();
            setMessages(data.messages ?? []);
        } catch {
            setMessages([]);
        }
    }, []);

    const handleNewConversation = useCallback(async () => {
        try {
            const response = await fetch('/chat/conversations', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': decodeURIComponent(
                        document.cookie
                            .split('; ')
                            .find((c) => c.startsWith('XSRF-TOKEN='))
                            ?.split('=')[1] ?? '',
                    ),
                },
            });
            const conversation = await response.json();

            setConversations((prev) => [
                {
                    id: conversation.id,
                    title: conversation.title,
                    created_at: conversation.created_at,
                    updated_at: conversation.created_at,
                },
                ...prev,
            ]);
            setActiveConversationId(conversation.id);
            setMessages([]);
            setStreamingContent('');
        } catch {
            // silently fail
        }
    }, []);

    const handleSend = useCallback(
        async (content: string) => {
            let conversationId = activeConversationId;

            if (conversationId === null) {
                try {
                    const response = await fetch('/chat/conversations', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-XSRF-TOKEN': decodeURIComponent(
                                document.cookie
                                    .split('; ')
                                    .find((c) => c.startsWith('XSRF-TOKEN='))
                                    ?.split('=')[1] ?? '',
                            ),
                        },
                    });
                    const conversation = await response.json();
                    conversationId = conversation.id;
                    setActiveConversationId(conversationId);
                    setConversations((prev) => [
                        {
                            id: conversation.id,
                            title: conversation.title,
                            created_at: conversation.created_at,
                            updated_at: conversation.created_at,
                        },
                        ...prev,
                    ]);
                } catch {
                    return;
                }
            }

            setMessages((prev) => [...prev, { role: 'user', content }]);
            setIsStreaming(true);
            setStreamingContent('');

            try {
                const response = await fetch(
                    `/chat/conversations/${conversationId}/messages`,
                    {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-XSRF-TOKEN': decodeURIComponent(
                                document.cookie
                                    .split('; ')
                                    .find((c) => c.startsWith('XSRF-TOKEN='))
                                    ?.split('=')[1] ?? '',
                            ),
                        },
                        body: JSON.stringify({ content }),
                    },
                );

                const reader = response.body?.getReader();
                if (!reader) return;

                const decoder = new TextDecoder();
                let assistantContent = '';

                while (true) {
                    const { done, value } = await reader.read();
                    if (done) break;

                    const chunk = decoder.decode(value, { stream: true });
                    const lines = chunk.split('\n');

                    for (const line of lines) {
                        if (!line.startsWith('data: ')) continue;
                        const data = line.slice(6);

                        try {
                            const parsed = JSON.parse(data);
                            if (parsed.type === 'text-delta') {
                                assistantContent += parsed.delta;
                                setStreamingContent(assistantContent);
                            } else if (parsed.type === 'done') {
                                setMessages((prev) => [
                                    ...prev,
                                    {
                                        role: 'assistant',
                                        content: assistantContent,
                                    },
                                ]);
                                setStreamingContent('');
                            }
                        } catch {
                            // skip invalid JSON
                        }
                    }
                }

                if (assistantContent !== '' && !streamingContent) {
                    setMessages((prev) => {
                        if (prev.some((m) => m.role === 'assistant' && m.content === assistantContent)) {
                            return prev;
                        }
                        return [
                            ...prev,
                            { role: 'assistant', content: assistantContent },
                        ];
                    });
                    setStreamingContent('');
                }
            } catch {
                setStreamingContent('');
            } finally {
                setIsStreaming(false);
            }
        },
        [activeConversationId, streamingContent],
    );

    return (
        <>
            <Head title="Chat" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-hidden rounded-xl p-4">
                <Heading
                    variant="small"
                    title="Asistente"
                    description="Consulta el manual de usuario de SignaDocs"
                />

                <div className="flex flex-1 overflow-hidden rounded-xl border">
                    <div className="w-64 shrink-0">
                        <ConversationList
                            conversations={conversations}
                            activeId={activeConversationId}
                            onSelect={selectConversation}
                            onNew={handleNewConversation}
                        />
                    </div>
                    <div className="flex flex-1 flex-col">
                        <MessageList
                            messages={messages}
                            streamingContent={streamingContent}
                        />
                        <MessageInput
                            onSend={handleSend}
                            disabled={isStreaming}
                        />
                    </div>
                </div>
            </div>
        </>
    );
}

ChatIndex.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
        {
            title: 'Chat',
            href: '/chat',
        },
    ],
};
