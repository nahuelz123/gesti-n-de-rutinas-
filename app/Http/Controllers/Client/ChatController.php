<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Message;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $coach = $user->myCoach();

        $messages = collect();

        if ($coach) {
            $messages = Message::query()
                ->betweenUsers($user->id, $coach->id)
                ->orderByDesc('id')
                ->limit(100)
                ->get()
                ->reverse()
                ->values();

            Message::query()
                ->where('recipient_id', $user->id)
                ->where('sender_id', $coach->id)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
        }

        return view('client.chat', [
            'coach' => $coach,
            'messages' => $messages,
        ]);
    }

    public function fetch(Request $request)
    {
        $user = $request->user();
        $coach = $user->myCoach();

        if (! $coach) {
            return response()->json(['messages' => []]);
        }

        $afterId = (int) $request->query('after_id', 0);

        $query = Message::query()->betweenUsers($user->id, $coach->id);

        if ($request->filled('before_id')) {
            $messages = $query
                ->where('id', '<', (int) $request->query('before_id'))
                ->orderByDesc('id')
                ->limit(100)
                ->get()
                ->reverse()
                ->values();
        } else {
            $messages = $query
                ->where('id', '>', $afterId)
                ->orderBy('id')
                ->limit(100)
                ->get();
        }

        if (! $request->filled('before_id')) {
            Message::query()
                ->where('recipient_id', $user->id)
                ->where('sender_id', $coach->id)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
        }

        return response()->json([
            'messages' => $messages->map(fn (Message $m) => [
                'id' => $m->id,
                'body' => $m->body,
                'mine' => $m->sender_id === $user->id,
                'time' => $m->created_at->format('H:i'),
            ]),
            'has_older' => $request->filled('before_id') && $messages->count() === 100,
        ]);
    }

    public function send(Request $request)
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $user = $request->user();
        $coach = $user->myCoach();

        abort_unless($coach, 422, 'No tenés un coach asignado todavía.');

        Message::create([
            'gym_id' => $user->gym_id,
            'sender_id' => $user->id,
            'recipient_id' => $coach->id,
            'body' => $data['body'],
        ]);

        return response()->json(['ok' => true]);
    }
}
