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

namespace Archipelago\Modules\Push\CLI;

use Archipelago\Common\Abstracts\Base;
use WP_CLI;

/**
 * Class CLI
 *
 * @package Archipelago\Modules\Events\CLI
 * @since 1.0.0
 */
class CLI extends Base
{

	/**
	 * Initialize the class.
	 *
	 * @since 1.0.0
	 */
	public function init()
	{
		/**
		 * This class is only being instantiated if WP_Cli is defined in the requester as requested in the Bootstrap class
		 *
		 * @see Requester::isCli()
		 * @see Bootstrap::__construct
		 */
		if (class_exists('WP_CLI')) {
			WP_CLI::add_command('push', [$this, 'cli']);
		}
	}

	/**
	 * cli command
	 * API reference: https://wp-cli.org/docs/internal-api/
	 *
	 * @param array $args The attributes.
	 * @return void
	 * @since 1.0.0
	 */
	public function cli(array $args)
	{
		if (!$args) WP_CLI::error('Provide Args');
		foreach ($args as $arg) {
			if (empty($arg)) WP_CLI::error('Please specify an action');

			if ($arg === 'install-db') {
				global $wpdb;
				$charset_collate = $wpdb->get_charset_collate();

				$schema = "CREATE TABLE {$wpdb->prefix}push_subscriptions (
				id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				user_id BIGINT(20) UNSIGNED NOT NULL,
				type varchar(25) NOT NULL,
				endpoint TEXT NOT NULL UNIQUE,
				p_key TEXT NOT NULL,
				auth_key TINYTEXT NOT NULL,
				FOREIGN KEY  (user_id) REFERENCES wp_users(id) ON DELETE CASCADE,
				PRIMARY KEY  id (id),
				KEY user_id (user_id)
				) $charset_collate;";

				require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
				dbDelta($schema);
			}

			if ($arg === 'drop-tables') {
				global $wpdb;
				$tables = [
					"{$wpdb->prefix}push_subscriptions",
				];
				$wpdb->query("DROP TABLE IF EXISTS " . implode(',', $tables) . ";");
			}
		}
	}
}
