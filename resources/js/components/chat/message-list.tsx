import { useEffect, useRef } from 'react';
import { User } from 'lucide-react';

type Message = {
    id?: number;
    role: 'user' | 'assistant';
    content: string;
};

export default function MessageList({
    messages,
    streamingContent,
}: {
    messages: Message[];
    streamingContent: string;
}) {
    const bottomRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        bottomRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [messages, streamingContent]);

    return (
        <div className="flex-1 overflow-y-auto p-4 space-y-4">
            {messages.length === 0 && streamingContent === '' && (
                <div className="flex h-full items-center justify-center">
                    <p className="text-sm text-muted-foreground">
                        Haz una pregunta para comenzar...
                    </p>
                </div>
            )}

            {messages.map((message, index) => (
                <div
                    key={message.id ?? `msg-${index}`}
                    className={`flex gap-3 ${message.role === 'user' ? 'justify-end' : 'justify-start'}`}
                >
                    {message.role === 'assistant' && (
                        <div className="flex size-8 shrink-0 items-center justify-center rounded-full bg-primary text-primary-foreground">
                            <User className="size-4" />
                        </div>
                    )}
                    <div
                        className={`max-w-[75%] rounded-xl px-4 py-2 text-sm whitespace-pre-wrap ${
                            message.role === 'user'
                                ? 'bg-primary text-primary-foreground'
                                : 'bg-muted'
                        }`}
                    >
                        {message.content}
                    </div>
                </div>
            ))}

            {streamingContent !== '' && (
                <div className="flex gap-3 justify-start">
                    <div className="flex size-8 shrink-0 items-center justify-center rounded-full bg-primary text-primary-foreground">
                        <User className="size-4" />
                    </div>
                    <div className="max-w-[75%] rounded-xl px-4 py-2 text-sm bg-muted whitespace-pre-wrap">
                        {streamingContent}
                        <span className="inline-block w-1.5 h-4 ml-0.5 bg-foreground/70 animate-pulse align-text-bottom" />
                    </div>
                </div>
            )}

            <div ref={bottomRef} />
        </div>
    );
}
