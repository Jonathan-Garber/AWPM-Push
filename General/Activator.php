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

namespace Archipelago\Modules\Push\General;

use Archipelago\Common\Abstracts\Base;

/**
 * Class Activator
 *
 * @package Archipelago\Modules\Push\General
 * @since 1.0.0
 */
class Activator extends Base
{
    /**
     * Initialize the class.
     *
     * @since 1.0.0
     */
    public function init()
    {
        add_action('init', [$this, 'pushServiceWorker']);
    }

    public function pushServiceWorker()
    {
        $uri = $_SERVER['REQUEST_URI'];
        if (str_contains($uri, '/service-worker')) {
            $plugin_dir     = plugin_dir_path(__DIR__);
            $file           = $plugin_dir . 'Backend/Assets/service-worker.js';
            header("Content-Type: application/javascript");
            header("Service-Worker-Allowed: /wp-admin/");
            $worker = file_get_contents($file);
            echo $worker;
            exit;
        }
        if (str_contains($uri, '/webmanifest')) {
            $plugin_dir     = plugin_dir_path(__DIR__);
            $file           = $plugin_dir . 'Backend/Assets/manifest.webmanifest';
            header("Content-Type: application/json");
            $worker = file_get_contents($file);
            echo $worker;
            exit;
        }
    }
}
