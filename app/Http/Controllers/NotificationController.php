<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\NotificationRead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'type' => ['nullable', 'in:low_stock,out_of_stock,refund,adjustment,general'],
            'unread' => ['nullable', 'boolean'],
        ]);
        $role = $request->user()->role;
        $visibleNotifications = fn () => Notification::forRole($role);
        $query = $visibleNotifications()
            ->withExists(['reads as read_by_user' => fn ($reads) => $reads->where('user_id', $request->user()->id)])
            ->orderByDesc('created_at');

        if ($type = $filters['type'] ?? null) {
            $query->where('type', $type);
        }
        if ($request->boolean('unread')) {
            $query->unreadForUser($request->user());
        }

        $notifications = $query->paginate(25)->withQueryString();
        $unreadCount = $visibleNotifications()->unreadForUser($request->user())->count();
        $openCount = $visibleNotifications()->where('is_resolved', false)->count();
        $types = ['low_stock', 'out_of_stock', 'refund', 'adjustment', 'general'];

        return view('notifications.index', compact('notifications', 'types', 'unreadCount', 'openCount'));
    }

    public function markRead(Request $request, Notification $notification)
    {
        abort_unless(
            Notification::forRole($request->user()->role)->whereKey($notification->id)->exists(),
            404
        );
        NotificationRead::updateOrCreate(
            ['notification_id' => $notification->id, 'user_id' => $request->user()->id],
            ['read_at' => now()]
        );

        return back()->with('success', 'Notification marked as read.');
    }

    public function resolve(Request $request, Notification $notification)
    {
        abort_unless(
            Notification::forRole($request->user()->role)->whereKey($notification->id)->exists(),
            404
        );
        DB::transaction(function () use ($notification, $request) {
            $notification->update(['is_resolved' => true, 'resolved_at' => now()]);
            NotificationRead::updateOrCreate(
                ['notification_id' => $notification->id, 'user_id' => $request->user()->id],
                ['read_at' => now()]
            );
        });

        return back()->with('success', 'Notification resolved.');
    }

    public function markAllRead(Request $request)
    {
        $readAt = now();
        Notification::forRole($request->user()->role)
            ->unreadForUser($request->user())
            ->select('id')
            ->chunkById(500, function ($notifications) use ($request, $readAt) {
                $rows = $notifications->map(fn ($notification) => [
                    'notification_id' => $notification->id,
                    'user_id' => $request->user()->id,
                    'read_at' => $readAt,
                    'created_at' => $readAt,
                    'updated_at' => $readAt,
                ])->all();

                NotificationRead::insertOrIgnore($rows);
            });

        return back()->with('success', 'All notifications marked as read.');
    }

    public function unreadCount(Request $request)
    {
        $count = Notification::forRole($request->user()->role)
            ->where('is_resolved', false)
            ->unreadForUser($request->user())
            ->count();

        return response()->json(['count' => $count]);
    }
}
