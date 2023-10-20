<?php

/**
 * Archipelago
 *
 * @package   archipelago
 * @author    Jonathan | ClubLISI <jonathan@clublisi.com>
 * @copyright 2022 Archipelago
 * @license   Proprietary
 * @link      https://clublisi.com
 */

declare(strict_types=1);

namespace Archipelago\Modules\Push\Backend;

use Archipelago\Common\Abstracts\Base;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

/**
 * Class Push
 *
 * @package Archipelago\Modules\Push\Backend
 * @since 1.0.0
 */
class PushManager extends Base
{
    /**
     * Initialize the class.
     *
     * @since 1.0.0
     */
    public function init()
    {
        add_action('admin_enqueue_scripts', [$this, 'pushResources'], 10);
        add_action('admin_head', [$this, 'pushManifestResources']);
    }

    public function pushManifestResources()
    {
        echo '<link rel="apple-touch-icon" sizes="180x180" href="/wp-content/plugins/archipelago-wp/src/Modules/Push/Backend/Assets/apple-touch-icon.jpg">
        <link rel="icon" type="image/png" sizes="32x32" href="/wp-content/plugins/archipelago-wp/src/Modules/Push/Backend/Assets/favicon-32x32.png">
        <link rel="icon" type="image/png" sizes="16x16" href="/wp-content/plugins/archipelago-wp/src/Modules/Push/Backend/Assets/favicon-16x16.png">
        <link rel="mask-icon" href="/wp-content/plugins/archipelago-wp/src/Modules/Push/Backend/Assets/safari-pinned-tab.svg" color="#5bbad5">
        <link rel="shortcut icon" href="/wp-content/plugins/archipelago-wp/src/Modules/Push/Backend/Assets/favicon.ico">
        <meta name="msapplication-TileColor" content="#2d89ef">
        <meta name="msapplication-config" content="/wp-content/plugins/archipelago-wp/src/Modules/Push/Backend/Assets/browserconfig.xml">
        <meta name="theme-color" content="#ffffff">
        <link rel="manifest" href="/webmanifest">';
    }

    public function pushResources()
    {
        $plugin_dir     = plugin_dir_path(__DIR__);
        $plugin_url     = plugin_dir_url(__DIR__);
        $push_js        = 'Backend/JS/push.js';
        wp_enqueue_script('push-service', $plugin_url . $push_js, [], filemtime($plugin_dir . $push_js));
    }

    public static function hasSubscription($user_id, $type, $endpoint)
    {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare("SELECT ID FROM {$wpdb->prefix}push_subscriptions WHERE user_id = %s AND type = %s AND endpoint = %s", $user_id, $type, $endpoint));
    }

    public static function updateSubscription($user_id, $type, $request)
    {
        $body = $request->get_json_params();
        $endpoint = $body['endpoint'];
        $p_key = $body['keys']['p256dh'];
        $auth_key = $body['keys']['auth'];

        $record = [
            'user_id'   => "'$user_id'",
            'type'      => "'$type'",
            'endpoint'  => "'$endpoint'",
            'p_key'     => "'$p_key'",
            'auth_key'  => "'$auth_key'"
        ];
        $record = implode(', ', $record);
        global $wpdb;
        $wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}push_subscriptions (user_id, type, endpoint, p_key, auth_key)
                    VALUES ($record)
                    ON DUPLICATE KEY UPDATE p_key=%s, auth_key=%s
                    ", $body['keys']['p256dh'], $body['keys']['auth']));
        if ($wpdb->insert_id) return true;
        return false;
    }

    public static function removeSubscription($endpoint)
    {
        global $wpdb;
        $deleted = $wpdb->delete("{$wpdb->prefix}push_subscriptions", ['endpoint' => $endpoint]);
        if ($deleted) return true;
        return false;
    }

    public static function pushNotice($type, $payload)
    {
        global $wpdb;
        $subscribers    = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}push_subscriptions WHERE type = '$type'");
        if (!$subscribers) return;

        foreach ($subscribers as $subscriber) {
            $notifications[] = [
                'subscription' => Subscription::create([
                    'endpoint' => $subscriber->endpoint,
                    'keys' => [
                        'p256dh' => $subscriber->p_key,
                        'auth' =>  $subscriber->auth_key
                    ]
                ]),
                'payload' => json_encode($payload)
            ];
        }
        //Needs moved to proper storage area some point publicKey privateKey email etc
        $auth = [
            'VAPID' => [
                'subject' => 'mailto:jonathan@clublisi.com',
                'publicKey' => "BEMkFJZUGpY79uafMKB1hwWQqThQwTB05p0tL9JfFpp6AR-8YnsMfo5KoWPtV2LHZdOYm7bIVcByzzygYdyqkzY",
                'privateKey' => "QEbnnmk6dm5HMdcEU_EJ-wL_OwBN5zevlF8KzbUrQ4I"
            ],
        ];
        $push = new WebPush($auth);
        foreach ($notifications as $notification) {
            $push->queueNotification(
                $notification['subscription'],
                $notification['payload']
            );
        }
        foreach ($push->flush() as $report) {
            $endpoint = $report->getRequest()->getUri()->__toString();
            if ($report->isSuccess()) {
                error_log("[v] Message sent successfully for subscription {$endpoint}.");
            } else {
                self::removeSubscription($endpoint);
                error_log("[x] Message failed to send for subscription {$endpoint}: {$report->getReason()}");
            }
        }
    }
}
