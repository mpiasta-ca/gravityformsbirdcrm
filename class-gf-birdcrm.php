<?php

// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

// Include the Bird CRM API class file
require_once plugin_dir_path( __FILE__ ) . 'includes/class-gf-birdcrm-api.php';

GFForms::include_feed_addon_framework();

/**
 * Gravity Forms Bird CRM Add-On.
 *
 * @since     1.6 Updated to use Bird CRM v2 API.
 * @since     1.0
 * @package   GravityForms
 * @author    Rocketgenius
 * @copyright Copyright (c) 2016, Rocketgenius
 */
class GFBirdCRM extends GFFeedAddOn {

	/**
	 * Version of this add-on which requires reauthentication with the API.
	 *
	 * Anytime updates are made to this class that requires a site to reauthenticate Gravity Forms with Bird, this
	 * constant should be updated to the value of GFForms::$version.
	 *
	 * @since 1.13
	 *
	 * @see GFForms::$version
	 */
	const LAST_REAUTHENTICATION_VERSION = '1.12';

	/**
	 * Contains an instance of this class, if available.
	 *
	 * @since  1.0
	 * @access private
	 * @var    object $_instance If available, contains an instance of this class.
	 */
	private static $_instance = null;

	/**
	 * Defines the version of the Bird CRM Add-On.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_version Contains the version, defined from birdcrm.php
	 */
	protected $_version = GF_BIRDCRM_VERSION;

	/**
	 * Defines the minimum Gravity Forms version required.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_min_gravityforms_version The minimum version required.
	 */
	protected $_min_gravityforms_version = '2.5';

	/**
	 * Defines the plugin slug.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_slug The slug used for this plugin.
	 */
	protected $_slug = 'gravityformsbirdcrm';

	/**
	 * Defines the main plugin file.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_path The path to the main plugin file, relative to the plugins folder.
	 */
	protected $_path = 'gravityformsbirdcrm/birdcrm.php';

	/**
	 * Defines the full path to this class file.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_full_path The full path.
	 */
	protected $_full_path = __FILE__;

	/**
	 * Defines the URL where this Add-On can be found.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string The URL of the Add-On.
	 */
	protected $_url = 'http://www.gravityforms.com';

	/**
	 * Defines the title of this Add-On.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_title The title of the Add-On.
	 */
	protected $_title = 'Gravity Forms Bird CRM Add-On';

	/**
	 * Defines the short title of the Add-On.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_short_title The short title.
	 */
	protected $_short_title = 'Bird CRM';

	/**
	 * Defines if Add-On should use Gravity Forms servers for update data.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    bool
	 */
	protected $_enable_rg_autoupgrade = true;

	/**
	 * Defines the capability needed to access the Add-On settings page.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_capabilities_settings_page The capability needed to access the Add-On settings page.
	 */
	protected $_capabilities_settings_page = 'gravityforms_birdcrm';

	/**
	 * Defines the capability needed to access the Add-On form settings page.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_capabilities_form_settings The capability needed to access the Add-On form settings page.
	 */
	protected $_capabilities_form_settings = 'gravityforms_birdcrm';

	/**
	 * Defines the capability needed to uninstall the Add-On.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_capabilities_uninstall The capability needed to uninstall the Add-On.
	 */
	protected $_capabilities_uninstall = 'gravityforms_birdcrm_uninstall';

	/**
	 * Defines the capabilities needed for the Bird CRM Add-On
	 *
	 * @since  1.0
	 * @access protected
	 * @var    array $_capabilities The capabilities needed for the Add-On
	 */
	protected $_capabilities = array( 'gravityforms_birdcrm', 'gravityforms_birdcrm_uninstall' );

	/** @var GF_BirdCRM_API */
	protected $api;

	/**
	 * Defines the transient name used to cache Bird CRM custom fields.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $fields_transient_name Transient name used to cache Bird CRM custom fields.
	 */
	protected $fields_transient_name = 'gform_birdcrm_fields';

	/**
	 * Whether Add-on framework has settings renderer support or not, settings renderer was introduced in Gravity Forms 2.5
	 *
	 * @since 1.11.2
	 *
	 * @var bool
	 */
	protected $_has_settings_renderer;

	/**
	 * Enabling background feed processing to prevent performance issues delaying form submission completion.
	 *
	 * @since 2.0.1
	 *
	 * @var bool
	 */
	protected $_async_feed_processing = true;

	/**
	 * Get instance of this class.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return GFBirdCRM
	 */
	public static function get_instance() {

		if ( null === self::$_instance ) {
			self::$_instance = new self;
		}

		return self::$_instance;
	}

	/**
	 * Plugin starting point.
	 *
	 * @since  1.2
	 * @access public
	 */
	public function init() {

		$this->_has_settings_renderer  = $this->is_gravityforms_supported( '2.5-beta' );

		parent::init();

		add_filter( 'cron_schedules', array( GFBirdCRM::get_instance(), 'add_cron_interval' ) );

		$this->scheduleCronEvent();
	}

	private function scheduleCronEvent()
	{
		if ( !wp_next_scheduled('gfbirdcrm_email_sync_event') ) {
			wp_schedule_event( time(), 'five_minutes', 'gfbirdcrm_email_sync_event' );
		}

		add_action('gfbirdcrm_email_sync_event', [$this, 'handle_cron_email_sync']);
	}

	/**
	 * Add custom interval for WP Cron.
	 *
	 * @param array $schedules Existing schedules.
	 *
	 * @return array Modified schedules.
	 */
	public function add_cron_interval( $schedules )
	{
		$schedules['five_minutes'] = array(
			'interval' => 180, // 3 minutes in seconds
			'display'  => esc_html__( 'Every Three Minutes' ),
		);
		return $schedules;
	}

	/**
	 * Handle the cron event to synchronize email templates.
	 * 
	 * @return void
	 */
	public function handle_cron_email_sync()
	{
		$this->ajax_email_sync();
	}

	/**
	 * Add AJAX callbacks.
	 *
	 * @since  1.6
	 */
	public function init_ajax() {
		parent::init_ajax();

		// Add AJAX callback for de-authorizing with Bird CRM.
		add_action( 'wp_ajax_gfbirdcrm_deauthorize', array( $this, 'ajax_deauthorize' ) );		
		add_action( 'wp_ajax_gfbirdcrm_email_sync', array( $this, 'ajax_email_sync' ) );
	}

	/**
	 * Enqueue admin scripts.
	 *
	 * @since 1.6
	 *
	 * @return array
	 */
	public function scripts() {

		$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG || isset( $_GET['gform_debug'] ) ? '' : '.min';

		$scripts = array(
			array(
				'handle'  => 'gform_birdcrm_pluginsettings',
				'deps'    => array( 'jquery' ),
				'src'     => $this->get_base_url() . "/js/plugin_settings{$min}.js",
				'version' => $this->_version,
				'enqueue' => array(
					array(
						'admin_page' => array( 'plugin_settings' ),
						'tab'        => $this->_slug,
					),
				),
				'strings' => array(
					'disconnect'        => wp_strip_all_tags( __( 'Are you sure you want to disconnect from Bird CRM?', 'gravityformsbirdcrm' ) ),
					'settings_url'      => admin_url( 'admin.php?page=gf_settings&subview=' . $this->get_slug() ),
					'nonce_deauthorize' => wp_create_nonce( 'gfbirdcrm_deauthorize' ),
					'nonce_email_sync'  => wp_create_nonce( 'gfbirdcrm_email_sync' ),
				),
			),
		);

		return array_merge( parent::scripts(), $scripts );
	}

	/**
	 * Register needed styles.
	 *
	 * @since  1.6 Added plugin settings CSS.
	 * @since  1.0
	 * @access public
	 *
	 * @return array $styles
	 */
	public function styles() {

		$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG || isset( $_GET['gform_debug'] ) ? '' : '.min';

		$styles = array(
			array(
				'handle'  => 'gform_birdcrm_form_settings_css',
				'src'     => $this->get_base_url() . "/css/form_settings{$min}.css",
				'version' => $this->_version,
				'enqueue' => array( array( 'admin_page' => array( 'form_settings' ) ) ),
			),
			array(
				'handle'  => 'gform_birdcrm_pluginsettings',
				'src'     => $this->get_base_url() . "/css/plugin_settings{$min}.css",
				'version' => $this->_version,
				'enqueue' => array(
					array(
						'admin_page' => array( 'plugin_settings' ),
						'tab'        => $this->_slug,
					),
				),
			),
		);

		return array_merge( parent::styles(), $styles );

	}





	// # PLUGIN SETTINGS -----------------------------------------------------------------------------------------------

	/**
	 * Return the plugin's icon for the plugin/form settings menu.
	 *
	 * @since 1.11.2
	 *
	 * @return string
	 */
	public function get_menu_icon() {
		return $this->is_gravityforms_supported( '2.5-beta-4' ) ? 'gform-icon--mail' : 'dashicons-admin-generic';
	}

	/**
	 * Add clear custom fields cache check.
	 *
	 * @access public
	 *
	 * @uses GFBirdCRM::maybe_clear_fields_cache()
	 */
	public function plugin_settings_page() {
		parent::plugin_settings_page();
	}

	/**
	 * Setup plugin settings fields.
	 *
	 * @since  1.7.4 Remove old authentication methods.
	 * @since  1.6   Added the OAuth authentication.
	 * @since  1.0
	 * @access public
	 *
	 * @return array
	 */
	public function plugin_settings_fields()
	{
		$api_secret_key = $this->get_plugin_setting( 'api_secret_key' );

		// Prepare plugin description.
		$description = '<p>';
		$description .= sprintf(
			esc_html__( 'Bird CRM platform allows you to organize and automate your messaging. If you don\'t have a Bird CRM account, you can %1$ssign up for one here.%2$s', 'gravityformsbirdcrm' ),
			'<a href="http://www.bird.com/" target="_blank">', '</a>'
		);
		$description .= '</p>';

		if ( !empty($api_secret_key) ) {
			$fields = array(
				array(
					'name'              => 'auth_token',
					'type'              => 'auth_token_button',
					'feedback_callback' => array( $this, 'initialize_api' ),
				),
				array(
					'type'       => 'save',
					'class'      => 'hidden',
				),
			);
		} else {
			$fields = array(
				array(
					'name'              => 'api_secret_key',
					'label'             => esc_html__( 'API Secret Key', 'gravityformsbirdcrm' ),
					'type'              => 'text',
					'class'             => 'medium',
					'feedback_callback' => array( $this, 'initialize_api' ),
					'required'          => true,
				),
				array(
					'name'              => 'workspace_id',
					'label'             => esc_html__( 'Workspace ID', 'gravityformsbirdcrm' ),
					'type'              => 'text',
					'class'             => 'medium',
					'feedback_callback' => array( $this, 'initialize_api' ),
					'required'          => true,
				),
				array(
					'name'              => 'channel_id',
					'label'             => esc_html__( 'Channel ID', 'gravityformsbirdcrm' ),
					'type'              => 'text',
					'class'             => 'medium',
					'feedback_callback' => array( $this, 'initialize_api' ),
					'required'          => true,
				),
				array(
					'name'              => 'from_name',
					'label'             => esc_html__( 'From Name', 'gravityformsbirdcrm' ),
					'type'              => 'text',
					'class'             => 'medium',
					'required'          => true,
				),
				array(
					'name'              => 'from_email_handle',
					'label'             => esc_html__( 'From Email Handle', 'gravityformsbirdcrm' ),
					'type'              => 'text',
					'class'             => 'medium',
					'required'          => true,
				),
				array(
					'type'       => 'save',
					'label'      => esc_html__( 'Save Settings', 'gravityformsbirdcrm' ),
					'messages'   => array(
						'success' => esc_html__( 'Bird CRM settings have been updated.', 'gravityformsbirdcrm' ),
					),
				),
			);
		}

		$settings = array(
			array(
				'title'       => '',
				'description' => $description,
				'fields'      => $fields,
			),
		);

		if ( !empty($api_secret_key) )
		{
			$settings[] = array(
				'title'  => esc_html__( 'Synchronize Email Templates', 'gravityformsbirdcrm' ),
				'fields' => array(
					array(
						'name'  => 'sync_email_templates',
						'label' => '',
						'type'  => 'sync_email_templates',
					),
				),
			);
		}

		return $settings;
	}

	/**
	 * Create Generate Auth Token settings field.
	 *
	 * @since  1.1
	 * @access public
	 *
	 * @param  array $field Field properties.
	 * @param  bool  $echo  Display field contents. Defaults to true.
	 *
	 * @return string
	 */
	public function settings_auth_token_button( $field, $echo = true )
	{
		if ( $this->initialize_api() ) {
			$html = '<p style="color:#339966; font-weight:700; margin:0; padding:0; ">' . esc_html__( 'Connected to Bird CRM.', 'gravityformsbirdcrm' );
			$html .= '</p>';
			$html .= sprintf(
				' <a href="#" class="button" id="gform_birdcrm_deauth_button">%1$s</a>',
				esc_html__( 'Disconnect from Bird CRM', 'gravityformsbirdcrm' )
			);
		}

		if ( $echo ) {
			echo $html;
		}

		return $html;
	}

	/**
	 * Generates button to sync email templates from the Settings page.
	 *
	 * @param  array $field Field properties.
	 * @param  bool  $echo  Display field contents. Defaults to true.
	 *
	 * @since  1.11.2
	 *
	 * @return string
	 */
	public function settings_sync_email_templates( $field, $echo = true ) {
		$html ='
				<div class="success-alert-container alert-container hidden" >
					<div class="gform-alert gform-alert--success" data-js="gform-alert">
						<span class="gform-alert__icon gform-icon gform-icon--circle-check" aria-hidden="true"></span>
						<div class="gform-alert__message-wrap">
							<p class="gform-alert__message">' . esc_html__( 'Templates were updated successfully.', 'gravityformsbirdcrm' ) . '</p>
						</div>
					</div>
				</div>
				<div class="error-alert-container alert-container hidden" >
					<div class="gform-alert gform-alert--error" data-js="gform-alert">
						<span class="gform-alert__icon gform-icon gform-icon--circle-close" aria-hidden="true"></span>
						<div class="gform-alert__message-wrap">
							<p class="gform-alert__message">' . esc_html__( 'Failed to synchronize, try again.', 'gravityformsbirdcrm' ) . '</p>
						</div>
					</div>
				</div>';

		$workspace_id = $this->get_plugin_setting( 'workspace_id' );

		$html .= sprintf(
			esc_html__( 'When an %1$s Email Template in Bird%2$s has been created or deleted, we need to synchronize the Email database in WordPress.', 'gravityformsbirdcrm' ),
			'<a href="https://app.bird.com/workspaces/'. $workspace_id .'/studio/htmlEmail/savedTemplates" target="_blank">', '</a>'
		);

		$html .= '<p><a id="gf_birdcrm_sync" class="primary button large">' . esc_html__( 'Synchronize', 'gravityformsbirdcrm' ) . '</a></p>';

		$settings             = $this->get_plugin_settings();
		$last_email_sync = rgar( $settings, 'last_email_template_sync' );

		$readable_time = $last_email_sync ? date( "Y-m-d g:ia", $last_email_sync ) : esc_html__( 'never', 'gravityformsbirdcrm' );
		$html         .= '<p id="last_email_template_sync">' . esc_html__( 'Last time synced manually: ', 'gravityformsbirdcrm' ) . '<span class="time">' . $readable_time . '</span></p>';

		if ( $echo ) {
			echo html_entity_decode( $html );
		}

		return $html;
	}


	// # FEED SETTINGS -------------------------------------------------------------------------------------------------

	/**
	 * Setup fields for feed settings.
	 *
	 * @since  1.7.1 Display settings based on available module.
	 * @since  1.0
	 * @access public
	 *
	 * @uses GFFeedAddOn::get_default_feed_name()
	 * @uses GFBirdCRM::send_email_settings_fields()
	 * @uses GFBirdCRM::send_email_global_template_settings_fields()
	 *
	 * @return array
	 */
	public function feed_settings_fields() {

		$settings_fields = array();

		$actions = array(
			array(
				'label' => esc_html__( 'Select an Action', 'gravityformsbirdcrm' ),
				'value' => null,
			),
			array(
				'label' => esc_html__( 'Send Email (email template set by form field)', 'gravityformsbirdcrm' ),
				'value' => 'send_email',
			),
			array(
				'label' => esc_html__( 'Send Email (global email template)', 'gravityformsbirdcrm' ),
				'value' => 'send_email_global_template',
			),
		);

		// Prepare base feed settings section.
		$settings_fields[] = array(
			'fields' => array(
				array(
					'name'          => 'feedName',
					'label'         => esc_html__( 'Feed Name', 'gravityformsbirdcrm' ),
					'type'          => 'text',
					'required'      => true,
					'default_value' => $this->get_default_feed_name(),
					'class'         => 'medium',
					'tooltip'       => '<h6>' . esc_html__( 'Name', 'gravityformsbirdcrm' ) . '</h6>' . esc_html__( 'Enter a feed name to uniquely identify this setup.', 'gravityformsbirdcrm' ),
				),
				array(
					'name'     => 'action',
					'label'    => esc_html__( 'Action', 'gravityformsbirdcrm' ),
					'required' => true,
					'type'     => 'select',
					'onchange' => "jQuery(this).parents('form').submit();",
					'tooltip'  => '<h6>' . esc_html__( 'Action', 'gravityformsbirdcrm' ) . '</h6>' . esc_html__( 'Choose what will happen when this feed is processed.', 'gravityformsbirdcrm' ),
					'choices'  => $actions,
				),
			),
		);

		// Get module feed settings sections.
		$settings_fields[] = $this->send_email_settings_fields();
		$settings_fields[] = $this->send_email_template_settings_fields();
		$settings_fields[] = $this->send_email_global_template_settings_fields();

		// Prepare conditional logic settings section.
		$settings_fields[] = array(
			'title'      => esc_html__( 'Feed Conditional Logic', 'gravityformsbirdcrm' ),
			'dependency' => array( 'field' => 'action', 'values' => array( 'send_email', 'send_email_global_template' ) ),
			'fields'     => array(
				array(
					'name'           => 'feedCondition',
					'type'           => 'feed_condition',
					'label'          => esc_html__( 'Conditional Logic', 'gravityformsbirdcrm' ),
					'checkbox_label' => esc_html__( 'Enable', 'gravityformsbirdcrm' ),
					'instructions'   => esc_html__( 'Export to Bird CRM if', 'gravityformsbirdcrm' ),
					'tooltip'        => '<h6>' . esc_html__( 'Conditional Logic', 'gravityformsbirdcrm' ) . '</h6>' . esc_html__( 'When conditional logic is enabled, form submissions will only be exported to Bird CRM when the condition is met. When disabled, all form submissions will be posted.', 'gravityformsbirdcrm' ),
				),
			),
		);

		return $settings_fields;
	}

	/**
	 * Setup common fields for feed settings for Send Email.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @uses GFAddOn::add_field_after()
	 * @uses GFBirdCRM::get_field_map_for_module()
	 *
	 * @return array
	 */
	public function send_email_settings_fields() {

		// Prepare send email settings fields.
		$fields = array(
			'title'      => esc_html__( 'Email Settings', 'gravityformsbirdcrm' ),
			'dependency' => array( 'field' => 'action', 'values' => array( 'send_email', 'send_email_global_template' ) ),
			'fields'     => array(
				array(
					'name'      => 'sendEmailFields',
					'label'     => esc_html__( 'Map Fields', 'gravityformsbirdcrm' ),
					'type'      => 'field_map',
					'field_map' => array(
						array(
							'name'       => 'first_name',
							'label'      => esc_html__( 'First Name', 'gravityformsbirdcrm' ),
							'required'   => false,
						),
						array(
							'name'       => 'last_name',
							'label'      => esc_html__( 'Last Name', 'gravityformsbirdcrm' ),
							'required'   => false,
						),
						array(
							'name'       => 'email',
							'label'      => esc_html__( 'Email To', 'gravityformsbirdcrm' ),
							'required'   => true,
						),
					),
					'tooltip'   => '<h6>' . esc_html__( 'Map Fields', 'gravityformsbirdcrm' ) . '</h6>' . esc_html__( 'Select which Gravity Form fields pair with their respective Bird CRM fields.', 'gravityformsbirdcrm' ),
				),
			),
		);

		// Get file field choices.
		$file_choices = $this->get_file_fields_for_feed_setting();

		if ( !empty($file_choices) )
		{
			$fields['fields'][] = array(
				'name'    => 'include_attachments',
				'type'    => 'checkbox',
				'label'   => esc_html__( 'Attach files', 'gravityformsbirdcrm' ),
				'choices' => $file_choices,
				'tooltip' => esc_html__( 'Select which file fields to include as attachments in the email', 'gravityformsbirdcrm' )
			);
		}

		return $fields;
	}

	/**
	 * Setup fields for feed settings for Send Email with email template mapped to a form field.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @uses GFAddOn::add_field_after()
	 * @uses GFBirdCRM::get_field_map_for_module()
	 *
	 * @return array
	 */
	public function send_email_template_settings_fields() {

		// Prepare send email settings fields.
		$fields = array(
			'title'      => esc_html__( 'Email Template', 'gravityformsbirdcrm' ),
			'dependency' => array( 'field' => 'action', 'values' => array( 'send_email') ),
			'fields'     => array(
				array(
					'name'      => 'emailTemplateFields',
					'type'      => 'field_map',
					'field_map' => array(
						array(
							'name'       => 'email_template',
							'label'      => esc_html__( 'Email Template', 'gravityformsbirdcrm' ),
							'required'   => true,
						),
					),
				),
			),
		);

		return $fields;
	}

	/**
	 * Setup fields for feed settings for Send Email with a global email template.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @uses GFAddOn::add_field_after()
	 * @uses GFBirdCRM::get_field_map_for_module()
	 *
	 * @return array
	 */
	public function send_email_global_template_settings_fields() {

		// Prepare send email settings fields.
		$fields = array(
			'title'      => esc_html__( 'Email Template', 'gravityformsbirdcrm' ),
			'dependency' => array( 'field' => 'action', 'values' => array( 'send_email_global_template' ) ),
			'fields'     => array(
				array(
					'name'      => 'global_email_template',
					'label'     => esc_html__( 'Select a global email template', 'gravityformsbirdcrm' ),
					'type'      => 'select',
					'required'   => true,
					'choices'   => $this->get_global_email_templates(),
				),
			),
		);

		return $fields;
	}

	/**
	 * Get email templates from Bird CRM.
	 *
	 * @return array
	 */
	private function get_global_email_templates()
	{
		$options = array(
			array(
				'label' => esc_html__( 'Select an Email Template', 'gravityformsbirdcrm' ),
				'value' => '',
			),
		);

		$posts = get_posts(array('post_type' => 'email', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC'));

		foreach ( $posts as $post )
		{
			$options[] = array(
				'label' => get_post_meta($post->ID, 'template_name', true),
				'value' => get_post_meta($post->ID, 'template_id', true),
			);
		}

		return $options;
	}

	/**
	 * Get form file fields for feed field settings.
	 *
	 * @return array
	 */
	public function get_file_fields_for_feed_setting()
	{
		$choices = array();
		$form = $this->get_current_form();

		$file_fields = GFAPI::get_fields_by_type( $form, array( 'fileupload' ), true );

		if ( ! empty($file_fields) )
		{
			foreach ( $file_fields as $field )
			{
				// Add file field as choice.
				$choices[] = array(
					'name'          => 'include_attachments[' . $field->id . ']',
					'label'         => $field->label,
					'default_value' => 0,
				);
			}
		}

		return $choices;
	}

	/**
	 * Process the Bird CRM feed.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param  array $feed  Feed object.
	 * @param  array $entry Entry object.
	 * @param  array $form  Form object.
	 */
	public function process_feed( $feed, $entry, $form )
	{
		// If API instance is not initialized, exit.
		if ( ! $this->initialize_api() )
		{
			// Log that we cannot process the feed.
			$this->add_feed_error( esc_html__( 'Feed was not processed because API was not initialized.', 'gravityformsbirdcrm' ), $feed, $entry, $form );
			return;
		}

		$action = rgars( $feed, 'meta/action' );

		// Send email.
		if ( in_array( $action, ['send_email', 'send_email_global_template'] ) )
		{
			// Get email headers.
			$email_headers = [
				'from' => [
					'name' => $this->get_plugin_setting( 'from_name' ),
					'email_handle' => $this->get_plugin_setting( 'from_email_handle' ),
				]
			];

			// Get email data.			
			$email_data = $this->get_email_data( $action, $feed, $entry, $form );

			// If email data is valid, send the email.
			if ( ! empty( $email_data ) )
			{
				$this->send_email( $email_headers, $email_data, $feed, $entry, $form );
			}
		}
	}

	/**
	 * Get email data from a feed.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param array $action Action type.
	 * @param array $feed  Feed object.
	 * @param array $entry Entry object.
	 * @param array $form  Form object.
	 *
	 * @return array|null $email_data
	 */
	public function get_email_data( $action, $feed, $entry, $form ) {

		// Initialize email data object.
		$email_data = array(
			'first_name' => rgar( $entry, rgars($feed, 'meta/sendEmailFields_first_name') ),
			'last_name' => rgar( $entry, rgars($feed, 'meta/sendEmailFields_last_name') ),
			'email' => rgar( $entry, rgars($feed, 'meta/sendEmailFields_email') ),
			'attachments' => []
		);

		// Get email template value based on action.
		switch ($action)
		{
			case 'send_email':
				$emailTemplate = rgar( $entry, rgars($feed, 'meta/emailTemplateFields_email_template') );

				// Fallback to old mapping value
				if ( empty($emailTemplate) ) {
					$emailTemplate = rgar( $entry, rgars($feed, 'meta/sendEmailFields_email_template') );
				}

				$email_data['email_template'] = $emailTemplate;
				break;

			case 'send_email_global_template':
				$email_data['email_template'] = rgars($feed, 'meta/global_email_template');
				break;
		}

		$attachmentFields = rgars($feed, 'meta/include_attachments') ?? [];
		$includeAttachments = !empty($attachmentFields) && is_array($attachmentFields) ? count($attachmentFields) : false;
		if ( $includeAttachments )
		{
			$email_data['attachments'] = $this->get_email_attachments($feed, $entry, $form);
		}

		$log = new GF_BirdCRM_LogFactory('GFBirdCRM');
		//$log->debug(__FUNCTION__, "\$entry: " . json_encode($entry));
		//$log->debug(__FUNCTION__, "\$form: " . json_encode($form));

		// Validate email data.
		if ( empty( $email_data['email'] ) || empty( $email_data['email_template'] ) ) {	
			$log->delta(__FUNCTION__, 'Required email data is missing: ' . json_encode($email_data));
			return null;
		}

		// Get the email template from the `email` pod fields.
		try {
			$emailTemplate = $this->get_email_template( $email_data['email_template'] );
			$email_data = array_merge($email_data, $emailTemplate);
		}
		catch ( Exception $e ) {
			$log->delta(__FUNCTION__, 'Email template not found: ' . $e->getMessage() .' '. json_encode($email_data));
			return null;
		}

		return $email_data;
	}

	/**
	 * Pick attachments from $form fields, check $entry if it had any files uploaded to the array.
	 * 
	 * @param array $feed
	 * @param array $entry
	 * @param array $form
	 * 
	 * @return array
	 */
	private function get_email_attachments( $feed, $entry, $form )
	{
		$log = new GF_BirdCRM_LogFactory('GFBirdCRM', GF_BirdCRM_LogFactory::LEVEL_ERROR);

		if ( !rgars($feed, 'meta/include_attachments') )
		{
			$log->debug(__FUNCTION__, 'No file upload fields selected. <Skip>');
			return [];
		}

		$file_fields = [];

		//see which fileupload fields to include as attachments
		foreach ($feed['meta']['include_attachments'] as $field_id => $is_checked)
		{
			if ( !empty($is_checked) ) {
				$file_fields[] = $field_id;
			}
		}

		if ( empty($file_fields) ) {
			$log->debug(__FUNCTION__, 'No file upload fields selected. <Skip>');
			return [];
		}

		$attachments = [];

		foreach ( $file_fields as $fieldId )
		{
			$field = GFFormsModel::get_field( $form, $fieldId );
			$fieldLabel = $field->adminLabel ?? $this->generateKeyFromLabel($field->label ?? '');

			$uploads = $this->get_field_value( $form, $entry, $fieldId );
			
			if ( empty($uploads) ) {
				$log->debug(__FUNCTION__, "No uploads found for field $fieldLabel");
				continue;
			}

			// Convert files value to array.
			$uploads = $this->is_json( $uploads ) ? json_decode( $uploads, true ) : explode( ' , ', $uploads );
			
			$log->debug(__FUNCTION__, "Found uploads for field $fieldLabel: " . json_encode($uploads));

			foreach( $uploads as $key => $fileUrl )
			{
				$count = $key + 1;
				$url = trim( $fileUrl );

				if ( empty($url) || !filter_var($url, FILTER_VALIDATE_URL) ) {
					continue;
				}

				// file exists and is public
				$file_headers = @get_headers($url);
				if ( !strpos($file_headers[0], '200') ) {
					$log->debug(__FUNCTION__, "File not found or not public: $url");
					continue;
				}

				$date = date('Ymd\THi');
				$ext = pathinfo( $url, PATHINFO_EXTENSION );

				$filename = "{$fieldLabel}_{$date}_#{$count}.{$ext}";

				$attachments[] = [
					'file_url' => $url,
					'file_name' => $filename,
				];

				$log->debug(__FUNCTION__, "Added attachment: $filename");
			}
		}

		return $attachments;
	}

	/**
	 * Pick uploads from $form fields, check $entry if it had any files uploaded to the array.
	 * 
	 * @param array $entry
	 * @param array $form
	 * 
	 * @return array
	 */
	private function get_file_uploads( $entry, $form )
	{
		$log = new GF_BirdCRM_LogFactory('GFBirdCRM', GF_BirdCRM_LogFactory::LEVEL_ERROR);

		$file_fields = GFAPI::get_fields_by_type( $form, array( 'fileupload' ), true );

		if ( empty($file_fields) ) {
			$log->debug(__FUNCTION__, 'No file upload fields. <Skip>');
			return [];
		}

		$files_list = [];

		foreach ( $file_fields as $field )
		{
			$fieldLabel = $field->adminLabel ?? $this->generateKeyFromLabel($field->label ?? '');

			$uploads = $this->get_field_value( $form, $entry, $field->id );
			
			if ( empty($uploads) ) {
				$log->debug(__FUNCTION__, "No uploads found for field $fieldLabel");
				continue;
			}

			// Convert files value to array.
			$uploads = $this->is_json( $uploads ) ? json_decode( $uploads, true ) : explode( ' , ', $uploads );
			
			$log->debug(__FUNCTION__, "Found uploads for field $fieldLabel: " . json_encode($uploads));

			foreach( $uploads as $fileUrl )
			{
				$url = trim( $fileUrl );

				if ( empty($url) || !filter_var($url, FILTER_VALIDATE_URL) ) {
					continue;
				}

				// file exists and is public
				$file_headers = @get_headers($url);
				if ( empty($file_headers) || !strpos($file_headers[0], '200') ) {
					$log->debug(__FUNCTION__, "File not found or not public: $url");
					continue;
				}

				$files_list[] = $url;

				$log->debug(__FUNCTION__, "Added attachment: $url");
			}
		}

		return $files_list;
	}

	/**
	 * Get email template from Bird CRM to confirm it still exists, and then get the
	 * version id, since we need it to send an email.
	 * 
	 * @param int $birdTemplateId
	 * 
	 * @return array
	 */
	private function get_email_template( $email_template_id = null )
	{
		if ( empty($email_template_id) ) {
			throw new Exception('email_template_not_set');
		}

		$email_template = $this->api->get_email_template_by_id($email_template_id);
		$published_version_id = $email_template['activeResourceId'] ?? null;

		if ( empty($email_template_id) || empty($published_version_id) ) {
			throw new Exception('email_template_not_resolved');
		}

		return [
			'template_id' => $email_template_id,
			'published_version_id' => $published_version_id
		];
	}

	/**
	 * Send email using Bird CRM API.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param array $email_headers  Email headers.
	 * @param array $email_data     Email data.
	 * @param array $feed           Feed object.
	 * @param array $entry          Entry object.
	 * @param array $form           Form object.
	 */
	public function send_email( $email_headers, $email_data, $feed, $entry, $form )
	{
		$log = new GF_BirdCRM_LogFactory('GFBirdCRM');

		try {
			$email_variables = $this->map_entries_to_labels($entry, $form);

			//add files variable as a json string array
			$file_url_list = $this->get_file_uploads($entry, $form);
			$email_variables['files_list'] = json_encode($file_url_list);
		}
		catch(Throwable $e) {
			$log->error(__FUNCTION__, 'Could not map entries to labels: ' . $e->getMessage());
			return;
		}

		// Send email using Bird CRM API.
		$response = $this->api->send_email( $email_headers, $email_data, $email_variables );

		if ( is_wp_error( $response ) ) {
			// Log that email could not be sent.
			$this->add_feed_error( esc_html__( 'Could not send email; ', 'gravityformsbirdcrm' ) . $response->get_error_message(), $feed, $entry, $form );
			return;
		}
	}

	/**
	 * Convert entries $entries keys from ie. "1", "2", "3" to "first_name", "last_name", "email", etc.
	 * If value is not set, return it as an empty string (so email template can always compare against
	 * the expected key).
	 * 
	 * Also, don't include fileupload field values, because attachments are included separately.
	 * 
	 * @param array $entries
	 * @param array $form
	 * 
	 * @return array
	 */
	public function map_entries_to_labels($entries, $form)
	{
		$listFieldTypes = ['multiselect', 'checkbox', 'list', 'multi_choice'];
		$variables = [];

		foreach ( $form['fields'] as $field )
		{
			//skip file fields
			if ( $field->type === 'fileupload' ) {
				continue;
			}

			//get field label
			$publicLabel = $this->generateKeyFromLabel( $field->label );
			$fieldKey = $field->adminLabel ?? $publicLabel ?? (string) $field->id;

			if ( isset($field->inputs) && is_array($field->inputs) )
			{
				//field with multiple inputs:
				if ( in_array($field->type, $listFieldTypes, true) )
				{
					//list field (ie. Checkbox, Multiple Choice)
					$listValues = [];

					foreach ($field->inputs as $input)
					{
						$childId = (string) $input['id'];
						$fieldValue = isset( $entries[$childId] ) ? $entries[$childId] : null;
						
						if ( !empty($fieldValue) ) {
							$listValues[] = $fieldValue;
						}
					}

					$variables[$fieldKey] = implode(', ', $listValues);
				}
				else
				{
					//aggregate fields (ie. Name, Address)
					foreach ($field->inputs as $input)
					{
						$childKey = $this->generateKeyFromLabel( $input['customLabel'] ?? $input['label'] );
						$childId = (string) $input['id'];
	
						if ( isset( $entries[$childId] ) )
						{
							$variables["{$fieldKey}_{$childKey}"] = isset( $entries[$childId] ) ? $entries[$childId] : '';
						}
					}
				}
			}
			else
			{
				//field with single input:
				$fieldId = (string) $field->id;

				if ( isset( $entries[$fieldId] ) )
				{
					$variables[$fieldKey] = isset( $entries[$fieldId] ) ? $entries[$fieldId] : '';
				}
			}
		}

		return $variables;
	}

	/**
	 * Convert string like "Latest Utility Bill" to "latest_utility_bill".
	 * 
	 * @param string $label
	 * 
	 * @return string
	 */
	public function generateKeyFromLabel($label = '')
	{
		if ( empty($label) ) {
			return '';
		}

		$label = strtolower($label);
		$label = preg_replace('/[^a-z0-9]+/i', '_', $label); //non-alphanumeric to underscore
		$label = preg_replace('/_+/', '_', $label); //multiple underscores to single

		return $label;
	}

	// ...existing code...

	/**
	 * Enable feed duplication.
	 *
	 * @since  1.1.7
	 * @access public
	 *
	 * @param int $id Feed ID requesting duplication.
	 *
	 * @return bool
	 */
	public function can_duplicate_feed( $id ) {

		return true;

	}

	/**
	 * Setup columns for feed list table.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return array
	 */
	public function feed_list_columns() {

		return array(
			'feedName' => esc_html__( 'Name', 'gravityformsbirdcrm' ),
			'action'   => esc_html__( 'Action', 'gravityformsbirdcrm' ),
		);

	}

	/**
	 * Get value for action feed list column.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param  array $feed Feed for current table row.
	 *
	 * @return string
	 */
	public function get_column_value_action( $feed ) {

		if ( rgars( $feed, 'meta/action' ) == 'send_email' ) {
			return esc_html__( 'Send Email', 'gravityformsbirdcrm' );
		}
		
		if ( rgars( $feed, 'meta/action' ) == 'send_email_global_template' ) {
			return esc_html__( 'Send Email', 'gravityformsbirdcrm' );
		}

		return esc_html__( 'No Action', 'gravityformsbirdcrm' );

	}

	/**
	 * Initializes the Bird CRM API if credentials are valid.
	 *
	 * @uses GFAddOn::get_plugin_setting()
	 * @uses GF_BirdCRM_API::get_users()
	 *
	 * @return bool|null API initialization state. Returns null if no API Secret Key is provided.
	 */
	public function initialize_api() {

		// If the API is already initialized, return true.
		if ( ! empty( $this->api ) ) {
			return true;
		}

		// Get the API Secret Key, Workspace ID, and Email Channel ID from WordPress options.
		$api_secret_key = $this->get_plugin_setting( 'api_secret_key' );
		$workspace_id = $this->get_plugin_setting( 'workspace_id' );
		$email_channel_id = $this->get_plugin_setting( 'channel_id' );

		// If the API Secret Key is not set, return null.
		if ( rgblank( $api_secret_key ) || rgblank( $workspace_id ) || rgblank( $email_channel_id ) ) {
			$this->log_error( __METHOD__ . '(): API credentials are missing.' );
			return false;
		}

		// Log that we are testing the API credentials.
		$this->log_debug( __METHOD__ . "(): Validating API credentials." );

		try {
			// Initialize a new Bird CRM API instance.
			$bird_crm = GF_BirdCRM_API::get_instance( $api_secret_key, $workspace_id, $email_channel_id );

			// Test the API credentials.
			$email_templates = $bird_crm->is_connected();
			if ( empty($email_templates) ) {
				throw new Exception( 'No email templates found. Please check your API credentials.' );
			}

			// Assign Bird CRM API instance to the Add-On instance.
			$this->api = $bird_crm;

		} catch ( Exception $e ) {
			// Log the error message.
			$this->log_error( __METHOD__ . '(): API initialization failed: ' . $e->getMessage() );

			return false;
		}

		return true;
	}

	// ...existing code...

	/**
	 * Revoke token and remove them from Settings.
	 *
	 * @since  1.6
	 */
	public function ajax_deauthorize() {
		// Verify nonce.
		if ( wp_verify_nonce( rgget( 'nonce' ), 'gfbirdcrm_deauthorize' ) === false ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Access denied.', 'gravityformsbirdcrm' ) ) );
		}

		// If user is not authorized, exit.
		if ( ! GFCommon::current_user_can_any( $this->_capabilities_settings_page ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Access denied.', 'gravityformsbirdcrm' ) ) );
		}

		// Remove API Secret Key from settings.
		$settings = $this->get_plugin_settings();

		$settings['api_secret_key'] = '';

		$this->update_plugin_settings( $settings );

		// Log that we removed the API Secret Key.
		$this->log_debug( __METHOD__ . '(): API Secret Key removed.' );

		// Return success response.
		wp_send_json_success();
	}
	
	/**
	 * Handles the ajax request to synchronize email templates from Bird CRM to Pods `email` post type.
	 *
 	 * Add cron to WordPress that will run every 5 minutes.
	 * 
	 * The cron will do the following:
	 * - get list of all templates from Bird CRM as $templates and pick the fields: 
	 *     - Template Name:         `name`
	 *     - Template Id:           `id`
	 * - get all posts stored in the Pods custom post type `email` as $posts, which has fields:
	 *     - Template Name:         `template_name`
	 *     - Template Id:           `template_id`
	 * - loop through $posts and check for matches on $template.`id` = $post.`template_id`:
	 *     - if match is found, update post with the latest template name
	 *     - if match is not found, add a new post with $template data in the fields
	 *     - if the template is no longer available in Bird CRM, delete the post
	 *
	 * @since 2.1
	 */
	public function ajax_email_sync()
	{
		$log = new GF_BirdCRM_LogFactory('GFBirdCRM', GF_BirdCRM_LogFactory::LEVEL_DELTA);

		if ( !check_ajax_referer( 'gfbirdcrm_email_sync', 'nonce', false ) && !defined('DOING_CRON') ) {
			$log->error(__FUNCTION__, 'Access denied: nonce');
			wp_send_json_error();
		}

		if ( !$this->initialize_api() ) {
			$log->error(__FUNCTION__, 'Failed: API not initialized!');

			if ( !defined('DOING_CRON') ) {
				wp_send_json_error();
			}

			return;
		}

		try {
			$posts = get_posts(['post_type' => 'email', 'numberposts' => -1]); //get `email` posts (Pods)
			$templates = $this->api->list_email_templates(); //Email Templates from Bird API

			$log->debug(__FUNCTION__, 'Email templates from Bird CRM: '. count($templates));
			$log->debug(__FUNCTION__, 'Email templates in WordPress: '. count($posts));
			
			$postsCache = [];
			$templateExists = [];

			//loop through $posts
			foreach ($posts as $post)
			{
				$post_id = $post->ID;
				$post_template_name = get_post_meta($post->ID, 'template_name', true);
				$post_template_id = get_post_meta($post->ID, 'template_id', true);

				$postsCache[$post_template_id] = [
					'id' => $post_id,
					'template_name' => $post_template_name,
					'template_id' => $post_template_id
				];
			}

			//loop through $templates from API
			foreach ($templates as $template)
			{
				$template_id = $template['id'];
				$template_name = trim( $template['name'] );

				$templateExists[$template_id] = true;

				if ( !empty( $postsCache[$template_id] ) )
				{
					//update existing post title
					if ( $template_name !== $postsCache[$template_id]['template_name'] )
					{
						$log->delta(__FUNCTION__, 'Update email template with new name: ' . $template_name);

						$post_id = $postsCache[$template_id]['id'];

						update_post_meta($post_id, 'template_name', $template_name);
						
						do_action('save_post', $post_id, get_post($post_id), true);
					}
				}
				else
				{
					$log->delta(__FUNCTION__, 'Add new email template: ' . $template_name);

					// Create new post using Pods API
					$pod = pods('email');
					
					$post_id = $pod->add([
						'post_status' => 'publish',
						'template_id' => $template_id,
						'template_name' => $template_name,
					]);

					do_action('save_post', $post_id, get_post($post_id), true);

					$postsCache[$template_id] = [
						'id' => $post_id,
						'template_name' => $template_name,
						'template_id' => $template_id
					];
				}
			}

			//loop through $posts to check for deleted
			foreach ($postsCache as $post)
			{
				$post_template_id = $post['template_id'];

				//if template was deleted in Bird CRM, move to trash
				if ( empty( $templateExists[$post_template_id] ) )
				{
					$log->delta(__FUNCTION__, 'Trash old email template which no longer exists: ' . $post['template_name']);
					wp_trash_post( $post['id'] );
				}		
			}
		}
		catch (Throwable $e)
		{
			$this->log_debug( __METHOD__ . '() : failed to get email templates from Bird CRM: ' . $e->getMessage() );
			
			if ( !defined('DOING_CRON') ) {
				wp_send_json_error();
			}

			return;
		}

		$log->debug(__FUNCTION__, 'Email templates synchronized successfully.');

		//update timestamp
		$settings = $this->get_plugin_settings();
		$settings['last_email_template_sync'] = time();
		$this->update_plugin_settings( $settings );

		if ( !defined('DOING_CRON') ) {
			wp_send_json_success(
				array(
					'last_clearance' => date( 'Y-m-d g:ia', $settings['last_email_template_sync'] ),
				)
			);
		}
	}
}