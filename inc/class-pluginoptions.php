<?php

/**
 * Encapsulates Options handling, including reading and writing .json config file or wp_options table.
 */

if ( !class_exists( 'DS_Plugin_Options', FALSE )) {
	class DS_Plugin_Options
	{
		private $filename = NULL;				// file name to store options in as json data
		private $option_name = NULL;			// option key to store options in via wp_options table
		private $defaults = array();			// array of default option data

		private $_options = NULL;				// array of option data read from persistent data
		private $_dirty = FALSE;				// set to TRUE when options are set

		public function __construct( $option = NULL, $file = NULL, $defaults = NULL )
		{
			if ( NULL === $option && NULL === $file)
				throw new Exception( __('Instantiation requires $option or $file parameter', 'desktopserver' ));

			if ( NULL !== $defaults ) {
				if ( is_array( $defaults ) )
					$this->defaults = $defaults;
				else
					throw new Exception( __( '$defaults parameter must be an array', 'desktopserver' ));
			}

			if ( NULL !== $option ) {
				$this->option_name = $option;
				$this->load_options();
				return;
			}

			if ( NULL !== $file ) {
				$this->filename = $file;
				$this->load_options();
				return;
			}
		}

		/**
		 * Loads the plugin's options from the persistent store
		 */
		private function load_options()
		{
			$options = NULL;

			// load from persistent store
			if ( NULL !== $this->option_name ) {
				// reading from database
				$options = get_option( $this->option_name, $this->defaults );
			} else if ( NULL !== $this->filename ) {
				// reading from filesystem
				if ( file_exists( $this->filename ) ) {
					$data = file_get_contents( $this->filename );
					$options = json_decode( $data, TRUE );
				}
			}
			// TODO: add capability to store in DS specific table

			// this ensures we have *something*, even if it's an empty array
			if ( NULL === $options )
				$options = $this->defaults;

			$this->_options = array_merge( $this->defaults, $options );
		}

		/**
		 * Gets a single option value
		 * @param string $name The name of the option field
		 * @param mixed $default A default value to use if the option is not found
		 * @return mixed The named option value or the default value
		 */
		public function get( $name, $default = '' )
		{
			if ( isset( $this->_options[$name] ) )
				return $this->_options[$name];
			return $default;
		}

		/**
		 * Retrieve all the store options
		 * @return array An associative array containing all the configuration options
		 */
		public function get_options()
		{
			return $this->_options;
		}

		/**
		 * Sets a single option value
		 * @param string $name The name of the option field to set
		 * @param mixed $value The value to save for the named field
		 */
		public function set( $name, $value )
		{
			$this->_options[$name] = $value;
			$this->_dirty = TRUE;
		}

		/**
		 * Saves the options to persistent store
		 */
		public function save()
		{
DS_Database_Archive::_log(__METHOD__.'()');
			if ( $this->_dirty ) {
DS_Database_Archive::_log(__METHOD__.'() data is dirty');
				if ( NULL !== $this->option_name ) {
					set_option( $this->option_name, $this->_options );
				} else if ( NULL !== $this->filename ) {
					// save to filesystem
DS_Database_Archive::_log(__METHOD__.'() saving to file ' . $this->filename);
					$output = json_encode( $this->_options, JSON_PRETTY_PRINT );
DS_Database_Archive::_log(__METHOD__.'() contents: ' . $output);
					file_put_contents( $this->filename, $output );
				}
				$this->_dirty = FALSE;
			}
		}
	}
} // class_exists
