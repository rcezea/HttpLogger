<?php

namespace Rcezea\HttpLogger;

use Tracy\Debugger;
use Tracy\ILogger;

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

/**
 * HTTPLogger Class
 *
 * Implements ILogger to send error logs to a server endpoint
 * and optionally log to local files.
 */
class HttpLogger implements ILogger {

	// Static properties for endpoint, mode, and platform
	private static string $endpoint = 'http://127.0.0.1:5000/errorhandler';
	private static bool $mode = Debugger::Development; // Development by default
	private static string $platform = 'web'; // Default platform is 'web'

	// Log file paths (optional)
	private ?string $successLogFilePath;
	private ?string $failedLogFilePath;

	// Optional security credentials
	private ?string $appId;
	private ?string $secretKey;

	/**
	 * Constructor
	 *
	 * @param   string|null  $appId      Optional Application ID for API
	 *                                   authentication
	 * @param   string|null  $secretKey  Optional Secret Key for API
	 *                                   authentication
	 * @param   bool         $logToFile  Enable logging to local files
	 * @param   string|null  $logDir     Directory to store log files
	 *
	 * @throws \RuntimeException         If the log directory cannot be created
	 */
	public function __construct(
		?string $appId = NULL,
		?string $secretKey = NULL,
		bool $logToFile = FALSE,
		?string $logDir = NULL,
	) {
		// API security (optional)
		$this->appId     = $appId;
		$this->secretKey = $secretKey;

		// Handle local file logging
		if ( $logToFile ) {
			$logDir = $logDir ?? dirname( __DIR__ ) . '/logs';
			if ( ! is_dir( $logDir ) && ! mkdir( $logDir, 0777, TRUE )
			     && ! is_dir( $logDir ) ) {
				throw new \RuntimeException( "Failed to create log directory: $logDir" );
			}
			$this->successLogFilePath = $logDir . '/success_log.txt';
			$this->failedLogFilePath  = $logDir . '/failed_log.txt';
		} else {
			$this->successLogFilePath = NULL;
			$this->failedLogFilePath  = NULL;
		}

		// Initialize Debugger
		Debugger::enable( self::$mode, $logDir );
		Debugger::setLogger( $this );
	}

	/**
	 * Set the logging server endpoint URL.
	 *
	 * @param   string  $url  Server endpoint URL
	 *
	 * @throws \InvalidArgumentException If the URL is invalid
	 */
	public static function setEndpoint( string $url ): void {
		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			throw new \InvalidArgumentException( "Invalid endpoint URL: $url" );
		}
		self::$endpoint = $url;
	}

	/**
	 * Set the application mode (Development or Production).
	 *
	 * @param   int  $mode  Use Debugger::Development or Debugger::Production
	 *
	 * @throws \InvalidArgumentException If the mode is invalid
	 */
	public static function setMode( int $mode ): void {
		if ( ! in_array( $mode, [ Debugger::Development, Debugger::Production ] ) ) {
			throw new \InvalidArgumentException( 'Invalid mode. Use Debugger::Development or Debugger::Production.' );
		}
		self::$mode = $mode;
	}

	/**
	 * Set the platform type ('web' or 'mobile').
	 *
	 * @param   string  $platform  Platform type
	 *
	 * @throws \InvalidArgumentException If the platform is invalid
	 */
	public static function setPlatform( string $platform ): void {
		if ( ! in_array( $platform, [ 'web', 'mobile' ], TRUE ) ) {
			throw new \InvalidArgumentException( 'Invalid platform. Use "web" or "mobile".' );
		}
		self::$platform = $platform;
	}

	/**
	 * Log method - sends logs to the server and optionally writes to files.
	 *
	 * @param   mixed   $value  The log message or exception
	 * @param   string  $level  Log level (ILogger::INFO, etc.)
	 */
	public function log( mixed $value, string $level = ILogger::INFO ): void {
		// Prepare the message and stack trace
		$message = $value instanceof \Throwable
			? $value->getMessage() . ' in ' . $value->getFile() . ':'
			  . $value->getLine()
			: ( is_scalar( $value ) ? (string) $value
				: json_encode( $value, JSON_PARTIAL_OUTPUT_ON_ERROR ) );

		$stack = $value instanceof \Throwable ? $value->getTraceAsString() : '';

		// Determine environment mode
		$environment = self::$mode === Debugger::Development ? 'development'
			: 'production';

		// Build log data
		$data = [
			'level'       => $level,
			'message'     => $message,
			'stack'       => $stack,
			'platform'    => self::$platform,
			'environment' => $environment,
		];

		// Include optional API credentials
		if ( $this->appId && $this->secretKey ) {
			$data['appId']     = $this->appId;
			$data['secretKey'] = $this->secretKey;
		}

		// Send the log
		$this->sendLogToServer( $data );
	}

	/**
	 * Send log data to the configured server endpoint.
	 *
	 * @param   array  $data  Log data
	 */
	private function sendLogToServer( array $data ): void {
		$payload = json_encode( $data );
		$ch      = curl_init( self::$endpoint );
		curl_setopt_array( $ch, [
			CURLOPT_RETURNTRANSFER => TRUE,
			CURLOPT_POST           => TRUE,
			CURLOPT_POSTFIELDS     => $payload,
			CURLOPT_HTTPHEADER     => [
				'Content-Type: application/json',
				'Content-Length: ' . strlen( $payload ),
			],
		] );

		$response = curl_exec( $ch );
		$httpCode = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		$error    = curl_error( $ch );
		curl_close( $ch );

		// Handle file logging if enabled
		if ( $this->successLogFilePath && $this->failedLogFilePath ) {
			if ( $response !== FALSE && $httpCode < 400 ) {
				$this->logToFile( $this->successLogFilePath, $data, $response );
			} else {
				$data['response']   = $response ?: 'No response';
				$data['curl_error'] = $error;
				$this->logToFile( $this->failedLogFilePath, $data );
			}
		}
	}

	/**
	 * Write log data to a local file.
	 *
	 * @param   string  $filePath  Path to the log file
	 * @param   array   $data      Log data
	 * @param   string  $response  Optional response from the server
	 */
	private function logToFile(
		string $filePath, array $data, string $response = '',
	): void {
		$logMessage = sprintf(
			"[%s] \nLEVEL: %s\nMessage: %s\nStack: %s\nPlatform: %s\nEnvironment: %s\n%s-----------------------------------\n",
			date( 'Y-m-d H:i:s' ),
			$data['level'],
			$data['message'],
			$data['stack'] ?? 'N/A',
			$data['platform'],
			$data['environment'],
			$response ? "Response: $response\n" : '',
		);
		file_put_contents( $filePath, $logMessage, FILE_APPEND );
	}

}
