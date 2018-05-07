<?php
/**
Plugin Name: Database Archive
Plugin URL: https://serverpress.com/plugins/database-flush
Description: Archive all databases for DesktopServer sites on a daily basis.
Version: 1.0
Author: Dave Jesch
Author URI: http://serverpress.com
Text Domain: database-archive
Domain path: /language
*/

class DS_Database_Archive
{
	private static $_instance = NULL;

	const DAY_IN_SECONDS = 86400;					// 24 hrs
	const DATA_FILE = 'dbarchive.dat';				// data file used to trigger archive operation
	const OPTION_FILE = 'database-archive.json';	// file to write options  to

	const ARCHIVE_DIR_NAME = 'DesktopServer-Archives';	// directory within user dir for DS Archive files

	public $dirs = NULL;					// array of directories used by the plugin

	public $file = NULL;					// full name of self::DATA_FILE used to trigger archive operation
	private $option_file = NULL;			// full name of option file for configuration persistance
	public $options = NULL;					// instance of the DS_Plugin_Options class for config settings

	/**
	 * Constructor
	 */
	private function __construct()
	{
//dbarchive_debug('inside ' . __METHOD__ . '()');
		add_action( 'plugins_loaded', array( __CLASS__, 'check_perform_archive' ));
		$this->option_file = dirname( __FILE__ ) . '/' . self::OPTION_FILE;

		if ( is_admin() ) {
			$this->load_class( 'fieldrender' );
			$this->load_class( 'admin' );

			$this->get_options();
			DS_Database_Archive_Admin::get_instance();
		}
	}

	/**
	 * Obtains a singleton instance of the plugin
	 * @return DS_DatabaseCollationFix instance
	 */
	public static function get_instance()
	{
		if (NULL === self::$_instance)
			self::$_instance = new self();
		return self::$_instance;
	}

	/**
	 * Checks if time interval has expired and archive operation should be executed.
	 */
	public static function check_perform_archive()
	{
		$self = self::get_instance();

dbarchive_debug('inside ' . __METHOD__ . '()');
		global $ds_runtime;
dbarchive_debug('runtime: ' . var_export($ds_runtime, TRUE));
		$self->get_directories();

dbarchive_debug('ds dir=' . $self->dirs['ds_dir']);
		
		$self->file = $self->dirs['ds_temp'] . self::DATA_FILE;
dbarchive_debug('file=' . $self->file);

		if ( !isset( $ds_runtime->preferences->desktop ) ) {
$self->_log('desktop property is missing ' . var_export($ds_runtime->preferences, TRUE));
			return;
		}

		if ( file_exists( $self->file ) ) {
			$self->get_options();
			// file exists, check contents for time signature
			$time = file_get_contents( $self->file );

			// calculate the next time archive operation is to be run
			$days = 1;
			switch ( $self->options->get( 'archive', '1day' ) ) {
			case 'daily':
			default:
				$days = 1;
				break;
			case '2days':
				$days = 2;
				break;
			case '3days':
				$days = 3;
				break;
			case 'weekly':
				$days = 7;
			case 'never':
				return;
				break;
			}
			$hour = abs( $self->options->get( 'time', '00' ) );
			$hour = 60 * 60 * $hour;
$self->_log(__METHOD__.'():' . __LINE__ . ' time=' . $time . '/' . date( 'M-d-Y H:i:s', $time ) );
			$next = strtotime( date( 'm/d/Y 00:00:00', $time) );
$self->_log(__METHOD__.'():' . __LINE__ . ' next=' . $next . '/' . date( 'M-d-Y H:i:s', $next ) );
			$next += ( $days * self::DAY_IN_SECONDS ) + $hour;
$self->_log(__METHOD__.'():' . __LINE__ . ' next=' . $next . '/' . date( 'M-d-Y H:i:s', $next ) );

			// if we're past the time archive is supposed to run, run it
			if ( time() > $next )
				$self->perform_archive();
		} else {
			// file does not exist, just perform the archive
			$self->perform_archive();
		}
	}

	/**
	 * Saves current time in time signature file
	 */
	public function save_time()
	{
		$time = time();
		file_put_contents( $this->file, $time );
	}

	/**
	 * Helper method to load the plugin options into the private property '$options'
	 */
	private function get_options()
	{
		if ( !class_exists( 'DS_Plugin_Options', FALSE ) )
			$this->load_class('pluginoptions');
		if ( NULL === $this->options ) {
			$this->get_directories();
			$defaults = array(
				'archive' => 'daily',
				'time' => '00',
				'mode' => 'single',
				'location' => $this->dirs['user_dir'] . self::ARCHIVE_DIR_NAME . DIRECTORY_SEPARATOR
			);
			// $option_file was initialized in __construct()
			$this->options = new DS_Plugin_Options( NULL, $this->option_file, $defaults );
		}
	}

	/**
	 * Return an array containing directories needed by the plugin
	 *	['ds_dir']		=> The DesktopServer directory
	 *	['ds_temp']		=> The DesktopServer temporary file directory
	 *	['mysql_dir']	=> Directory where the MySQL/MariaDB install is
	 *	['user_dir']	=> The current user's directory
	 *	['archive_dir']	=> The user's Archive Directory
	 * @return array Array containing directory references
	 */
	public function get_directories()
	{
		if ( ! defined( 'DS_OS_DARWIN' ) ) {
			// OS-specific defines
			define( 'DS_OS_DARWIN', 'Darwin' === PHP_OS );
			define( 'DS_OS_WINDOWS', !DS_OS_DARWIN && FALSE !== strcasecmp('win', PHP_OS ) );
			define( 'DS_OS_LINUX', FALSE === DS_OS_DARWIN && FALSE === DS_OS_WINDOWS );
		}

		if ( NULL === $this->dirs ) {
			$this->dirs = array();

#	public $user_dir = NULL;				// The user's work directory

			global $ds_runtime;
			$this->dirs['ds_dir'] = getenv( 'DS_INSTALL' );
			if ( empty( $this->dirs['ds_dir'] ) ) {
				if ( DS_OS_DARWIN ) {
					$this->dirs['ds_dir'] = '/Applications/XAMPP/';
					$this->dirs['ds_temp'] = $this->dirs['ds_dir'] . 'xamppfiles/temp/';
					$this->dirs['mysql_dir'] = $this->dirs['ds_dir'] . 'xamppfiles/bin/';
					$this->dirs['user_dir'] = dirname( $ds_runtime->preferences->desktop ) . DIRECTORY_SEPARATOR;
				} else if ( DS_OS_WINDOWS ) {
					$this->dirs['ds_dir'] = 'c:\\xampplite\\';
					$this->dirs['ds_temp'] = $this->dirs['ds_dir'] . 'tmp/';
					$this->dirs['mysql_dir'] = $this->dirs['ds_dir'] . 'mysql/bin/';
					$this->dirs['user_dir'] = dirname( $ds_runtime->preferences->desktop ) . DIRECTORY_SEPARATOR;
					// TODO: use USERPROFILE environment variable?
				} else {
$this->_log('ERROR: Operating System not detected');
					throw new Exception( __('Operating System not detected.', 'database-archive' ) );
					return;
				}
			}
			$this->dirs['archive_dir'] = $this->dirs['user_dir'] . self::ARCHIVE_DIR_NAME . DIRECTORY_SEPARATOR;

		}

		return $this->dirs;
	}

	/**
	 * Performs database archive operation by executing mysqldump command
	 */
	public function perform_archive()
	{
		$this->save_time();		// do this first to avoid re-entry
		$start = microtime( TRUE );
$this->_log(__METHOD__.'()');

		global $ds_runtime;

		// use default archive directory, or the user-specified directory if supplied
		$archive_dir = $this->dirs['archive_dir'];
		$settings_dir = $this->options->get( 'location' );
		if ( !empty( $settings_dir ) )
			$archive_dir = $settings_dir;

		// build string for single or sequential files
		$seq = '';
		if ( 'sequential' === $this->options->get( 'mode', 'single' ) ) {
			$seq = date( '_Ymd' );
		}

		// make sure the User's archive directory exists
		@mkdir( $archive_dir, 0755, TRUE);
		if ( DS_OS_DARWIN ) {
			// set permissions so current user can read/write directory #3
			$user = $ds_runtime->preferences->webOwner;
			if ( empty( $user ) )
				$user = basename( dirname( $ds_runtime->preferences->desktop ) );
			$cmd = "chown {$user} {$archive_dir}";
$this->_log('exec: ' . $cmd);
			shell_exec( $cmd );
		}

		// copy DesktopServer's preferences file
		if ( DS_OS_DARWIN ) {
			$pref = '/Users/Shared/.com.serverpress.desktopserver.json';
		} else if ( DS_OS_WINDOWS ) {
			$pref = 'C:\\Documents and Settings\\All Users\\DesktopServer\\com.serverpress.desktopserver.json';
		}

		if ( file_exists( $pref ) ) {
			copy( $pref, $archive_dir . 'com.serverpress.desktopserver' . $seq . '.json');
		} else {
$this->_log('Cannot find preferences file: ' . $pref);
		}

		// construct the mysqldump command
		$cmd = $this->dirs['mysql_dir'] . 'mysqldump -u ' . $ds_runtime->preferences->dbUser . ' ';
		if ( ! empty( $ds_runtime->preferences->dbPass ) )
			$cmd .= ' -p' . $ds_runtime->preferences->dbPass . ' ';

$this->_log('runtime: ' . var_export($ds_runtime, TRUE));
$this->_log('sites: ' . var_export($ds_runtime->preferences->sites, TRUE));
		// iterate through all the sites found in config file
		foreach ( $ds_runtime->preferences->sites as $site => $info ) {
			$run = $cmd . ' ' . $info->dbName . ' >' . $archive_dir . $info->siteName . $seq . '.sql';
$this->_log(__METHOD__.'(): ' . $run);
			$res = shell_exec( $run );
$this->_log(__METHOD__.'()>' . $res);

			// output credentials file
			$cred_file = $archive_dir . $info->siteName . '-credentials' . $seq . '.sql';
			$cred_data = 'CREATE USER `' . $info->dbUser . '`@`localhost` IDENTIFIED BY \'' . $info->dbPass . '\';' . PHP_EOL;
			$cred_data .= 'SET PASSWORD FOR `' . $info->dbUser . '`@`localhost` = PASSWORD(\'' . $info->dbPass . '\');';
$this->_log(__METHOD__.'() writing to ' . $cred_file);
			file_put_contents($cred_file, $cred_data);
		}
		$end = microtime( TRUE );

$this->_log(__METHOD__.'() archive complete ' . ($end - $start) . ' microseconds');
	}

	/**
	 * Callback for 'init' action. Used to set up cron and check for trigger set by DesktopServer prepend.php actions
	 */
	public function init()
	{
$this->_log(__METHOD__.'() starting');
	}

	/*
	 * loads a class from the plugin's inc/ directory
	 * @param string $name The name of the class file to be loaded
	 */
	private function load_class( $name )
	{
		if ( file_exists( $file = dirname(__FILE__) . '/inc/class-' . $name . '.php' ) ) {
			require_once( $file );
		} else {
			throw new Exception( sprintf( __( 'Unable to load class file %1$s.', 'database-archive'), $file ) );
		}
	}

	/**
	 * Performs logging for debugging purposes
	 * @param string $msg The data to be logged
	 * @param boolean $backtrace TRUE to also log backtrace information
	 */
	public function _log($msg, $backtrace = FALSE)
	{
#return;
		$file = dirname(__FILE__) . '/~log.txt';
		$fh = @fopen($file, 'a+');
		if (FALSE !== $fh) {
			if (NULL === $msg)
				fwrite($fh, date('Y-m-d H:i:s'));
			else
				fwrite($fh, date('Y-m-d H:i:s - ') . $msg . "\r\n");

			if ($backtrace) {
				$callers = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
				array_shift($callers);
				$path = dirname(dirname(dirname(plugin_dir_path(__FILE__)))) . DIRECTORY_SEPARATOR;

				$n = 1;
				foreach ($callers as $caller) {
					$func = $caller['function'] . '()';
					if (isset($caller['class']) && !empty($caller['class'])) {
						$type = '->';
						if (isset($caller['type']) && !empty($caller['type']))
							$type = $caller['type'];
						$func = $caller['class'] . $type . $func;
					}
					$file = isset($caller['file']) ? $caller['file'] : '';
					$file = str_replace('\\', '/', str_replace($path, '', $file));
					if (isset($caller['line']) && !empty($caller['line']))
						$file .= ':' . $caller['line'];
					$frame = $func . ' - ' . $file;
					$out = '    #' . ($n++) . ': ' . $frame . PHP_EOL;
					fwrite($fh, $out);
					if (self::$_debug_output)
						echo $out;
				}
			}

			fclose($fh);
		}
	}
}

DS_Database_Archive::get_instance();
