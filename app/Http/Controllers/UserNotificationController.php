<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

class UserNotificationController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('voir-notifications');

        $notifications = auth()->user()->notifications()
            ->when($request->filled('unread'), fn ($q) => $q->whereNull('read_at'))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('notifications.index', compact('notifications'));
    }

    public function show(DatabaseNotification $notification): View
    {
        $this->authorize('voir-notifications');
        $this->ensureOwns($notification);

        if ($notification->read_at === null) {
            $notification->markAsRead();
        }

        return view('notifications.show', compact('notification'));
    }

    public function markAsRead(DatabaseNotification $notification): RedirectResponse
    {
        $this->authorize('voir-notifications');
        $this->ensureOwns($notification);

        $notification->markAsRead();

        return redirect()->route('notifications.index');
    }

    public function markAllAsRead(): RedirectResponse
    {
        $this->authorize('voir-notifications');

        auth()->user()->unreadNotifications->markAsRead();

        return redirect()->route('notifications.index');
    }

    public function unread(): JsonResponse
    {
        $this->authorize('voir-notifications');

        $user = auth()->user();

        $unread = $user->unreadNotifications()->latest()->take(10)->get();

        return response()->json([
            'count' => $user->unreadNotifications()->count(),
            'notifications' => $unread->map(fn ($n) => [
                'id' => $n->id,
                'title' => $n->data['title'] ?? 'Notification',
                'type' => $n->data['type'] ?? 'information',
                'priority' => $n->data['priority'] ?? 'normal',
                'category' => $n->data['category'] ?? 'system',
                'url' => route('notifications.show', $n),
                'created_at' => $n->created_at?->diffForHumans(),
            ]),
        ]);
    }

    protected function ensureOwns(DatabaseNotification $notification): void
    {
        abort_if(
            $notification->notifiable_id !== auth()->id()
                || $notification->notifiable_type !== auth()->user()->getMorphClass(),
            403
        );
    }
}
