import { FormEvent, useRef } from 'react';
import { Send } from 'lucide-react';
import { Button } from '@/components/ui/button';

export default function MessageInput({
    onSend,
    disabled,
}: {
    onSend: (message: string) => void;
    disabled: boolean;
}) {
    const inputRef = useRef<HTMLTextAreaElement>(null);

    function handleSubmit(e: FormEvent) {
        e.preventDefault();
        const input = inputRef.current;
        if (!input) return;

        const message = input.value.trim();
        if (message === '' || disabled) return;

        onSend(message);
        input.value = '';
        input.style.height = 'auto';
    }

    function handleKeyDown(e: React.KeyboardEvent<HTMLTextAreaElement>) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            handleSubmit(e);
        }
    }

    function handleInput(e: React.ChangeEvent<HTMLTextAreaElement>) {
        const target = e.target;
        target.style.height = 'auto';
        target.style.height = `${Math.min(target.scrollHeight, 150)}px`;
    }

    return (
        <form
            onSubmit={handleSubmit}
            className="flex items-end gap-2 border-t p-4"
        >
            <textarea
                ref={inputRef}
                rows={1}
                placeholder="Escribe tu pregunta..."
                disabled={disabled}
                onKeyDown={handleKeyDown}
                onInput={handleInput}
                className="flex-1 resize-none rounded-xl border bg-background px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
            />
            <Button type="submit" size="icon" disabled={disabled}>
                <Send className="size-4" />
            </Button>
        </form>
    );
}
