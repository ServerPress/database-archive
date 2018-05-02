<?php

/**
 * Implements configuration capabilities within the WordPress Admin
 */

class DS_Database_Archive_Admin
{
	private static $_instance = NULL;

	const SETTINGS_PAGE = 'database-archive-options';
	const SETTINGS_FIELDS = 'database_archive_group';
	const OPTION_NAME = 'database-archive';
	const SETTINGS_UPDATED_NOTICE = 'database-archive-settings-saved';

	private $_admin_page = NULL;
	private $msg = NULL;

	private function __construct()
	{
		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'add_configuration_page' ) );
			add_action( 'admin_init', array( $this, 'settings_api_init' ) );
		}
	}

	/**
	 * Return singleton instance of the admin class
	 * @return object Singleton reference to the class
	 */
	public static function get_instance()
	{
		if ( NULL === self::$_instance )
			self::$_instance = new self();
		return self::$_instance;
	}

	/**
	 * Adds the Database Archive option page to the Tools menu
	 * @return type
	 */
	public function add_configuration_page()
	{
		$this->_admin_page = add_management_page( __( 'DesktopServer Database Archive', 'database-archive' ),
			__( 'DS Database Archive', 'database-archive' ),
			'manage_options',
			self::SETTINGS_PAGE,
			array( $this, 'settings_page' )
		);
		add_action( 'load-' . $this->_admin_page, array( $this, 'contextual_help' ) );

		return $this->_admin_page;
	}

	public function contextual_help()
	{
		$screen = get_current_screen();
		if ( $this->_admin_page !== $screen->id )
			return;

		$screen->set_help_sidebar(
			'<p><string>' . __('For more information:', 'database-archive' ) . '</strong></p>' .
			'<p>' . sprintf( '<a href="%2$s" target="_blank">%1$s</a>%3$s<a href="%5$s" target="_blank">%4$s</a>.',
				__( 'See our documentation here: ', 'database-archive'),
				'https://serverpress.com/database-archive/',
				__( ', or visit our ', 'database-archive'),
				__( 'GitHub Repository', 'database-archive' ),
				'https://github.com/ServerPress/database-archive') . '</p>'
		);

		$screen->add_help_tab( array(
			'id'		=> 'ds-database-archive',
			'title'		=> __( 'DS Database Archive', 'database-archive' ),
			'content'	=>
				'<p><b>' . __( 'Perform Database Archive:', 'database-archive') . '</b> - ' .
					__( 'Archives of all DesktopServer sites will be made at the requested interval. One of "Daily", "Every two days", "Every three days", "Weekly" or "Never". If selecting "Never", it would be better to disable the DS Database Archive plugin from the Design Time Plugins loaded by DesktopServer.', 'database-archive' ) . '</b> - '.
				'</p>' .
				'<p><b>' . __( 'Time to Perform Backup', 'database-archive' ) . '</b> - ' .
					__( 'Sets the time of day that the Archive will be performed. Choice are: "12am" (Midnight), "4am", "8am", "12pm" (Noon), "4pm", and "8pm".', 'database-archive' ) . '</p>' .
				'<p><b>' . __( 'Archive Mode', 'database-archive' ) . '</b> - '.
					__( 'The Archive Mode describes how backup files will be created. Choice are "A single .sql file for each site" where a single .sql file will be used and overwritten with each archive performed. Or "A .sql file with date stamp for each site" where each archive will create a new .sql file with the date as part of the file name.', 'database-archive' ) . '</p>' .
				'<p><b>' . __( 'Location', 'database-archive' ) . '</b> - ' .
					__( 'The Location describes where the archive files will be placed on your computer. This will default to your a "DesktopServer_Archive/" directory under your User Directory.', 'database-archive' ) . '</p>'
		));

		do_action( 'database-archive-options_contextual_help', $screen );
	}

	/**
	 * Initializes the Settings API and adds the fields for the settings page
	 */
	public function settings_api_init()
	{
		$dba = DS_Database_Archive::get_instance();
		$option_values = $dba->options;
		$section_id = 'database_archive';
		$render = new DS_Field_Render( self::OPTION_NAME );

		register_setting(
			self::SETTINGS_FIELDS,							// option group, used for settings_fields()
			self::OPTION_NAME,								// option name, used as key in database
			array( $this, 'validate_settings' )
		);

		add_settings_section(
			$section_id,									// section id
			__( 'DS Database Archive', 'database-archive' ),// title
			'__return_true',								// callback
			self::SETTINGS_PAGE								// option page
		);

		add_settings_field(
			'archive',										// field id
			__( 'Perform Database Archive:', 'database-archive' ),	// title
			array( $render, 'radio_field' ),				// callback
			self::SETTINGS_PAGE,							// page
			$section_id,									// section id
			array(
				'name' => 'archive',
				'value' => $option_values->get( 'archive', 'daily' ),
				'description' => __( 'How often to perform Database Archive Operations. See Help screen above for detailed explanation of options.', 'database-archive' ),
				'options' => array(
					'daily' => __( 'Once Daily', 'database-archive' ),
					'2days' => __( 'Once Every Two Days', 'database-archive' ),
					'3days' => __( 'Once Every Three Days', 'database-archive' ),
					'weekly' => __( 'Once a Week', 'database-archive' ),
					'never' => __('Never', 'database-archive' ),
				)
			)
		);

		add_settings_field(
			'time',											// field id
			__( 'Time to Perform Backup:', 'database-archive' ),
			array( $render, 'select_field' ),
			self::SETTINGS_PAGE,
			$section_id,
			array(
				'name' => 'time',
				'value' => $option_values->get( 'time', '0' ),
				'description' => __( 'Time of day to perform archive.', 'database-archive' ),
				'options' => array(
					'00' => '12am',
					'04' => '4am',
					'08' => '8am',
					'12' => '12pm',
					'16' => '4pm',
					'20' => '8pm',
				),
			)
		);

		add_settings_field(
			'mode',											// field id
			__( 'Archive Mode:', 'database-archive' ),
			array( $render, 'select_field' ),
			self::SETTINGS_PAGE,
			$section_id,
			array(
				'name' => 'mode',
				'value' => $option_values->get( 'mode', 'single' ),
				'description' => __( 'How archives are to be stored.', 'database-archive' ),
				'options' => array(
					'single' => __( 'A single .sql file for each site.', 'database-archive' ),
					'sequential' => __( 'A .sql file with date stamp for each site.', 'database-archive' ),
				),
				'description' => __( 'Single files conserve space but will be overwritten with each Archive. Seperate files uses more space but stores multiple revisions to revert to.', 'database-archive'),
			)
		);

		if ( '' === $option_values->get( 'location' ) ) {
			$dirs = DS_Database_Archive::get_instance()->get_directories();
			$option_values->set( 'location', $dirs['user_dir'] . DS_Database_Archive::ARCHIVE_DIR_NAME . DIRECTORY_SEPARATOR );
		}
		add_settings_field(
			'location',										// field id
			__( 'Archive Location:', 'database-archive' ),
			array( $render, 'text_field' ),
			self::SETTINGS_PAGE,
			$section_id,
			array(
				'name' => 'location',
				'value' => $option_values->get( 'location', '' ),
				'description' => __( 'Location to store archives of all local sites. Each site will have a .sql file with the site\'s name.', 'database-archive' ),
				'size' => 50,
			)
		);

		// handle display of "Settings Saved" notice
		$key = self::SETTINGS_UPDATED_NOTICE . get_current_user_id();
		if ( FALSE !== $this->msg = get_transient( $key ) ) {
			delete_transient( $key );
			add_action( 'admin_notices', array( $this, 'show_notice' ) );
		}
	}

	/**
	 * Shows admin notice to let user know settings saved or error
	 */
	public function show_notice()
	{
		if ( !empty( $this->msg ) ) {
			$class = 'notice-error';
		} else {
			$class = 'notice-success';
			$this->msg = __( 'Your Database Archive changes have been saved and will be used for all DesktopServer sites.', 'database-archive' );
		}

		echo '<div class="notice ', $class, ' is-dismissible">';
		echo	'<p>', $this->msg, '</p>';
		echo '</div>';
	}

	/**
	 * Callback for the settings page
	 */
	public function settings_page()
	{
		echo '<div class="wrap ds-database-archive-settings">';
		echo '<h2>', __( 'DesktopServer Database Archive Tool - Configuration Settings', 'database-archive' ), '</h2>';

		echo '<p>', __('Please note: these settings will be shared and used across <em>all</em> of your DesktopServer sites. Making changes here will affect all future Archive operations.', 'database-archive' ), '</p>';

		echo '<form id="ds-database-archive-form" action="options.php" method="POST">';
		settings_fields( self::SETTINGS_FIELDS );
		do_settings_sections( self::SETTINGS_PAGE );
		submit_button();
		echo '</form>';
		echo '</div>';
	}

	/**
	 * Callback for validating settings. Called from the Settings API
	 * @param array $values An associative array of the form fields being saved.
	 */
	public function validate_settings( $values )
	{
		if ( ! current_user_can( 'manage_options' ) )
			return NULL;

		$dba = DS_Database_Archive::get_instance();
		$options = $dba->options;
		$error = '';

		foreach ( $values as $key => $value ) {
			$valid = FALSE;
			switch ( $key ) {
			case 'archive':
				if ( in_array( $value, array( 'daily', '2days', '3days', 'weekly', 'never' ) ) )
					$valid = TRUE;
				break;

			case 'time':
				if ( in_array( $value, array( '00', '04', '08', '12', '16', '20' ) ) )
					$valid = TRUE;
				break;

			case 'mode':
				if ( in_array( $value, array( 'single', 'sequential' ) ) )
					$valid = TRUE;
				break;

			case 'location':
				$value = rtrim( $value, '/\\' ) . DIRECTORY_SEPARATOR;
				@mkdir( $value, 0664, TRUE );
				if ( is_writable( $value ) )
					$valid = TRUE;
				else
					$error = __( 'Directory location is not writable.', 'database-archive' );
				break;
			}

			if ( $valid )
				$options->set( $key, $value );
		}
DS_Database_Archive::_log(__METHOD__.'() options: ' . var_export($options, TRUE));
		$options->save();

		// block the settings from being written to the database
		add_filter( 'pre_update_option', array( $this, 'block_update_option' ), 10, 3 );
		// signal display of settings saved message
		set_transient( self::SETTINGS_UPDATED_NOTICE . get_current_user_id(), $error );

		return NULL;
	}

	/**
	 * Callback for the 'pre_update_option' filter. Used to block saving options in the options table
	 * @param mixed $value The value being saved
	 * @param string $option The option name being saved
	 * @param mixed $old_value The old value before the update
	 * @return mixed The value to be saved in the option.
	 */
	public function block_update_option( $value, $option, $old_value )
	{
		if ( self::OPTION_NAME === $option ) {
			// if it's our option name, set the option to the $old_value.
			// This has the effect of making update_option() not write anything to the database.
			$value = $old_value;
		}
		return $value;
	}
}
