<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $query = Notification::orderByDesc('created_at');

        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }
        if ($request->get('unread')) {
            $query->whereNull('read_at');
        }

        $notifications = $query->paginate(25)->withQueryString();
        $unreadCount = Notification::whereNull('read_at')->count();
        $openCount = Notification::where('is_resolved', false)->count();
        $types = ['low_stock', 'out_of_stock', 'refund', 'adjustment', 'general'];

        return view('notifications.index', compact('notifications', 'types', 'unreadCount', 'openCount'));
    }

    public function markRead(Notification $notification)
    {
        $notification->update(['read_at' => now()]);

        return back()->with('success', 'Notification marked as read.');
    }

    public function resolve(Notification $notification)
    {
        $notification->update(['is_resolved' => true, 'resolved_at' => now(), 'read_at' => $notification->read_at ?? now()]);

        return back()->with('success', 'Notification resolved.');
    }

    public function markAllRead(Request $request)
    {
        Notification::whereNull('read_at')->update(['read_at' => now()]);

        return back()->with('success', 'All notifications marked as read.');
    }

    public function unreadCount()
    {
        $count = Notification::where('is_resolved', false)->whereNull('read_at')->count();

        return response()->json(['count' => $count]);
    }
}
