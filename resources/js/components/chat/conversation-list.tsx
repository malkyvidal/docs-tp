import { MessageSquare, Plus } from 'lucide-react';
import { Button } from '@/components/ui/button';

type Conversation = {
    id: number;
    title: string | null;
    created_at: string;
    updated_at: string;
};

export default function ConversationList({
    conversations,
    activeId,
    onSelect,
    onNew,
}: {
    conversations: Conversation[];
    activeId: number | null;
    onSelect: (id: number) => void;
    onNew: () => void;
}) {
    return (
        <div className="flex h-full flex-col border-r">
            <div className="flex items-center justify-between border-b p-3">
                <h3 className="text-sm font-medium">Conversaciones</h3>
                <Button variant="ghost" size="icon" onClick={onNew}>
                    <Plus className="size-4" />
                </Button>
            </div>
            <div className="flex-1 overflow-y-auto">
                {conversations.length === 0 ? (
                    <div className="flex flex-col items-center gap-2 p-4 text-center">
                        <MessageSquare className="size-8 text-muted-foreground" />
                        <p className="text-xs text-muted-foreground">
                            Sin conversaciones
                        </p>
                    </div>
                ) : (
                    conversations.map((conversation) => (
                        <button
                            key={conversation.id}
                            onClick={() => onSelect(conversation.id)}
                            className={`w-full border-b px-3 py-2.5 text-left text-sm hover:bg-accent/50 ${
                                activeId === conversation.id
                                    ? 'bg-accent'
                                    : ''
                            }`}
                        >
                            <p className="truncate font-medium">
                                {conversation.title ?? 'Nueva conversación'}
                            </p>
                        </button>
                    ))
                )}
            </div>
        </div>
    );
}
