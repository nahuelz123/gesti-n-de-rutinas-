<?php

namespace App\Filament\Pages;

use App\Models\Message;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class AdminChatOversight extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEye;

    protected static ?string $navigationLabel = 'Conversaciones';

    protected static ?string $title = 'Supervisión de chats';

    protected string $view = 'filament.pages.admin-chat-oversight';

    public ?string $selectedKey = null;

    public string $search = '';

    /**
     * Agrupa todos los mensajes del gym en conversaciones únicas coach↔cliente,
     * ordenadas por el mensaje más reciente.
     */
    public function getConversationsProperty(): Collection
    {
        $user = Auth::user();

        $messages = Message::query()
            ->when($user->role !== 'super_admin', fn ($q) => $q->where('gym_id', $user->gym_id))
            ->with(['sender', 'recipient'])
            ->orderByDesc('id')
            ->get();

        $conversations = $messages
            ->groupBy(fn (Message $m) => $this->pairKey($m->sender_id, $m->recipient_id))
            ->map(function (Collection $group) {
                $last = $group->first(); // ya viene ordenado desc por id

                $isSenderStaff = in_array($last->sender->role, ['coach', 'admin', 'super_admin']);
                $coach = $isSenderStaff ? $last->sender : $last->recipient;
                $client = $isSenderStaff ? $last->recipient : $last->sender;

                return [
                    'key' => $this->pairKey($last->sender_id, $last->recipient_id),
                    'coach' => $coach,
                    'client' => $client,
                    'last_message' => $last->body,
                    'last_at' => $last->created_at,
                    'count' => $group->count(),
                ];
            })
            ->sortByDesc('last_at')
            ->values();

        if (trim($this->search) === '') {
            return $conversations;
        }

        $needle = mb_strtolower(trim($this->search));

        return $conversations->filter(
            fn ($conv) => str_contains(mb_strtolower($conv['coach']->name), $needle)
                || str_contains(mb_strtolower($conv['client']->name), $needle)
        )->values();
    }

    public function getThreadProperty(): Collection
    {
        if (! $this->selectedKey) {
            return collect();
        }

        [$idA, $idB] = explode('-', $this->selectedKey);

        return Message::query()
            ->betweenUsers((int) $idA, (int) $idB)
            ->orderBy('id')
            ->get();
    }

    public function selectConversation(string $key): void
    {
        $this->selectedKey = $key;
    }

    private function pairKey(int $a, int $b): string
    {
        return $a < $b ? "{$a}-{$b}" : "{$b}-{$a}";
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user && in_array($user->role, ['super_admin', 'admin']);
    }
}
