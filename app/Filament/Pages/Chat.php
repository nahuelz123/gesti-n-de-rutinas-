<?php

namespace App\Filament\Pages;

use App\Models\Message;
use App\Models\User;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class Chat extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $navigationLabel = 'Chat';

    protected static ?string $title = 'Chat con clientes';

    protected string $view = 'filament.pages.chat';

    public ?int $selectedClientId = null;

    public string $body = '';

    public string $clientSearch = '';

    public function mount(): void
    {
        $clients = $this->getClients();

        $this->selectedClientId = $clients->first()?->id;
    }

    public function getClients(): Collection
    {
        $user = Auth::user();

        $query = User::query()
            ->where('role', 'client')
            ->when($user->role !== 'super_admin', fn ($q) => $q->where('gym_id', $user->gym_id));

        if (trim($this->clientSearch) !== '') {
            $query->where(fn ($q) => $q
                ->where('name', 'like', '%'.$this->clientSearch.'%')
                ->orWhere('email', 'like', '%'.$this->clientSearch.'%'));
        }

        return $query->orderBy('name')->get();
    }

    public function getUnreadCountsProperty(): array
    {
        $user = Auth::user();

        return Message::query()
            ->where('recipient_id', $user->id)
            ->whereNull('read_at')
            ->selectRaw('sender_id, count(*) as total')
            ->groupBy('sender_id')
            ->pluck('total', 'sender_id')
            ->toArray();
    }

    public function getMessagesProperty(): Collection
    {
        // Revalidar autorización: aunque selectClient() ya filtró,
        // la propiedad pública podría ser manipulada vía Livewire.
        $validClientId = $this->authorizeClientId($this->selectedClientId);

        if (! $validClientId) {
            return collect();
        }

        $user = Auth::user();

        $messages = Message::query()
            ->betweenUsers($user->id, $validClientId)
            ->orderBy('id')
            ->get();

        Message::query()
            ->where('recipient_id', $user->id)
            ->where('sender_id', $validClientId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return $messages;
    }

    public function selectClient(int $clientId): void
    {
        $this->selectedClientId = $this->authorizeClientId($clientId);
    }

    public function send(): void
    {
        $this->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        // Revalidar en el momento del envío (no confiar en que selectClient ya validó)
        $validClientId = $this->authorizeClientId($this->selectedClientId);

        if (! $validClientId) {
            return;
        }

        $user = Auth::user();

        Message::create([
            'gym_id' => $user->gym_id,
            'sender_id' => $user->id,
            'recipient_id' => $validClientId,
            'body' => $this->body,
        ]);

        $this->body = '';
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user && in_array($user->role, ['super_admin', 'admin', 'coach']);
    }

    /**
     * Verifica que un clientId pertenezca al mismo gym del usuario autenticado.
     * super_admin puede acceder a cualquier cliente.
     * Devuelve el ID validado, o null si no corresponde.
     */
    private function authorizeClientId(?int $clientId): ?int
    {
        if (! $clientId) {
            return null;
        }

        $user = Auth::user();

        $exists = User::query()
            ->where('id', $clientId)
            ->where('role', 'client')
            ->when($user->role !== 'super_admin', fn ($q) => $q->where('gym_id', $user->gym_id))
            ->exists();

        return $exists ? $clientId : null;
    }
}
