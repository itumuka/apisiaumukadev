<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

class NotificationHelper
{
    /**
     * Send a notification to a specific username
     *
     * @param string $username Recipient's username/NIDN/NIM
     * @param string $title Notification title
     * @param string $message Notification content
     * @param string|null $targetUrl Redirection target URL
     * @param string $type Category/type
     * @return bool
     */
    public static function send($username, $title, $message, $targetUrl = null, $type = 'general')
    {
        if (empty($username)) {
            return false;
        }

        try {
            DB::table('sys_notifications')->insert([
                'username'   => $username,
                'title'      => $title,
                'message'    => $message,
                'type'       => $type,
                'target_url' => $targetUrl,
                'is_read'    => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            return true;
        } catch (\Exception $e) {
            logger()->error('Notification send failed: ' . $e->getMessage());
            return false;
        }
    }
}
