<?php

/**
 * Archipelago
 * @package archipelago
 * @author Makanaokeakua Edwards | makana@clublisi.com
 * @copyright 2023 @ ClubLISI
 * @license Proprietary
 * @link https://clublisi.com
 */

declare(strict_types=1);

namespace Archipelago\Modules\Push\API;

use Archipelago\Common\Abstracts\Base;
use Archipelago\Modules\Push\Backend\PushManager;
use WP_REST_Response;

/**
 * Managing user queries via ajax
 */
class Subscriptions extends Base
{
    public function init()
    {
        add_action('rest_api_init', [$this, 'registerEndpoint']);
    }

    function registerEndpoint()
    {
        register_rest_route('archipelago/v1', '/push/subscribe', array(
            'methods' => 'POST',
            'callback' => function ($request) {
                if (empty($_GET['user_id']) || empty($_GET['type'])) return new WP_REST_Response(['code' => 400, 'message' => 'bad request'], 300);
                if (PushManager::updateSubscription($_GET['user_id'], $_GET['type'], $request)) return new WP_REST_Response(['code' => 200, 'message' => 'success'], 200);
                return new WP_REST_Response(['code' => 400, 'message' => 'bad request'], 300);
            }
        ));

        register_rest_route('archipelago/v1', '/push/unsubscribe', array(
            'methods' => 'POST',
            'callback' => function ($request) {
                $body = $request->get_json_params();
                if (PushManager::removeSubscription($body['endpoint'])) return new WP_REST_Response(['code' => 200, 'message' => 'success'], 200);
                return new WP_REST_Response(['code' => 200, 'message' => 'success'], 200);
            }
        ));
    }
}
