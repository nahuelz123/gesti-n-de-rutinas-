<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $notifications = $user->notifications()->paginate(20);

        $user->unreadNotifications->each->markAsRead();

        return view('client.notifications.index', compact('notifications'));
    }
}
