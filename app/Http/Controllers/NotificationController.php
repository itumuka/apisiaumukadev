<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    /**
     * Get latest notifications and unread count for current user
     */
    public function get_notifications(Request $request)
    {
        $username = $request->header('username') ?? $request->username;
        if (empty($username)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Username tidak ditemukan di header / parameter request.'
            ], 400);
        }

        // Fetch latest 15 notifications
        $notifications = DB::table('sys_notifications')
            ->where('username', $username)
            ->orderBy('created_at', 'desc')
            ->limit(15)
            ->get();

        // Calculate unread count
        $unread_count = DB::table('sys_notifications')
            ->where('username', $username)
            ->where('is_read', false)
            ->count();

        // Map human readable diffForHumans
        $notifications = $notifications->map(function($notif) {
            try {
                $notif->time_diff = \Carbon\Carbon::parse($notif->created_at)->locale('id')->diffForHumans();
            } catch (\Exception $e) {
                $notif->time_diff = 'Baru saja';
            }
            return $notif;
        });

        return response()->json([
            'status'        => 'success',
            'notifications' => $notifications,
            'unread_count'  => $unread_count
        ]);
    }

    /**
     * Mark single notification or all notifications as read
     */
    public function mark_as_read(Request $request)
    {
        $username = $request->header('username') ?? $request->username;
        if (empty($username)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Username tidak ditemukan di header / parameter request.'
            ], 400);
        }

        $id = $request->id; // Optional

        $query = DB::table('sys_notifications')
            ->where('username', $username);

        if ($id) {
            $query->where('id', $id);
        }

        $query->update([
            'is_read'    => true,
            'updated_at' => now()
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Notifikasi berhasil ditandai telah dibaca.'
        ]);
    }
}
