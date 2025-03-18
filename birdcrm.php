<?php

// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Plugin Name: Gravity Forms Bird CRM Add-On
 * Plugin URI: https://gravityforms.com
 * Description: Integrates Gravity Forms with Bird CRM, allowing form submissions to be automatically sent to your Bird CRM account.
 * Version: 1.0.3
 * Author: Gravity Forms
 * Author URI: https://gravityforms.com
 * License: GPL-2.0+
 * Text Domain: gravityformsbirdcrm
 * Domain Path: /languages
 *
 * ------------------------------------------------------------------------
 * Copyright 2009-2024 Rocketgenius, Inc.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA.
 */

define( 'GF_BIRDCRM_VERSION', '1.0.3' );

// If Gravity Forms is loaded, bootstrap the Bird CRM Add-On.
add_action( 'gform_loaded', array( 'GF_BirdCRM_Bootstrap', 'load' ), 5 );

// Unschedule the event upon plugin deactivation
register_deactivation_hook( __FILE__, function() {
	$timestamp = wp_next_scheduled( 'gfbirdcrm_email_sync_event' );
	wp_unschedule_event( $timestamp, 'gfbirdcrm_email_sync_event' );
});

/**
 * Class GF_BirdCRM_Bootstrap
 *
 * Handles the loading of the Bird CRM Add-On and registers with the Add-On framework.
 */
class GF_BirdCRM_Bootstrap {

	/**
	 * If the Feed Add-On Framework exists, Bird CRM Add-On is loaded.
	 *
	 * @since  1.0
	 * @access public
	 */
	public static function load() {

		if ( ! method_exists( 'GFForms', 'include_feed_addon_framework' ) ) {
			return;
		}

		require_once 'birdcrm/http_request_client.php';
		require_once 'birdcrm/log_factory.php';
		require_once 'birdcrm/log_service.php';
		require_once 'birdcrm/base_provider.php';
		require_once 'birdcrm/contact_provider.php';
		require_once 'birdcrm/template_provider.php';
		require_once 'birdcrm/email_sender.php';

		require_once 'includes/class-gf-birdcrm-api.php';

		require_once 'class-gf-birdcrm.php';

		GFAddOn::register( 'GFBirdCRM' );
	}
}

/**
 * Returns an instance of the GFBirdCRM class
 *
 * @see    GFBirdCRM::get_instance()
 *
 * @return GFBirdCRM
 */
function gf_birdcrm()
{
	return GFBirdCRM::get_instance();
}