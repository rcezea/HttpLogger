<?php

namespace Rcezea\HttpLogger;

use Tracy\Debugger;
use Tracy\ILogger;

require_once dirname(__DIR__) . '/vendor/autoload.php';

class httplogger implements ILogger
{
    private string $endpoint;
    private ?string $successLogFilePath;
    private ?string $failedLogFilePath;
    private ?string $appId;
    private ?string $secretKey;

    // Static property for mode
    private static bool $mode = Debugger::DEVELOPMENT;

    /**
     * Constructor
     */
    public function __construct(
        string $endpoint,
        ?string $appId = null,
        ?string $secretKey = null,
        bool $logToFile = false,
        string $logDir = null
    ) {
        $this->endpoint = $endpoint;

        // API security - optional
        $this->appId = $appId;
        $this->secretKey = $secretKey;

        // Validate URL
        if (!filter_var($endpoint, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException("Invalid endpoint URL: $endpoint");
        }

        // Handle logging to files
        if ($logToFile) {
            $logDir = $logDir ?? dirname(__DIR__) . '/logs';
            if (!is_dir($logDir) && !mkdir($logDir, 0777, true) && !is_dir($logDir)) {
                throw new \RuntimeException("Failed to create log directory: $logDir");
            }
            $this->successLogFilePath = $logDir . '/success_log.txt';
            $this->failedLogFilePath = $logDir . '/failed_log.txt';
        } else {
            $this->successLogFilePath = null;
            $this->failedLogFilePath = null;
        }

        // Apply the static mode to Debugger
        Debugger::enable(self::$mode, $logDir);
    }

    /**
     * Static method to set PRODUCTION or DEVELOPMENT mode
     */
    public static function setMode(int $mode): void
    {
        if (!in_array($mode, [Debugger::DEVELOPMENT, Debugger::PRODUCTION])) {
            throw new \InvalidArgumentException('Invalid mode. Use Debugger::DEVELOPMENT or Debugger::PRODUCTION.');
        }
        self::$mode = $mode;
    }

    /**
     * Log method (implements ILogger)
     */
    public function log($value, $level = ILogger::INFO): void
    {
        $message = $value instanceof \Throwable
            ? $value->getMessage() . ' in ' . $value->getFile() . ':' . $value->getLine()
            : (is_scalar($value) ? (string)$value : json_encode($value, JSON_PARTIAL_OUTPUT_ON_ERROR));

        $data = [
            'level' => $level,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        // Add optional API keys
        if ($this->appId && $this->secretKey) {
            $data['app_id'] = $this->appId;
            $data['secret_key'] = $this->secretKey;
        }

        $this->sendLogToServer($data);
    }

    /**
     * Send log data to the server
     */
    private function sendLogToServer(array $data): void
    {
        $ch = curl_init($this->endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Content-Length: ' . strlen(json_encode($data)),
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($this->successLogFilePath && $this->failedLogFilePath) {
            if ($response !== false && $httpCode < 400) {
                $this->logToFile($this->successLogFilePath, $data, $response);
            } else {
                $data['response'] = $response ?: 'No response';
                $data['http_code'] = $httpCode;
                $data['curl_error'] = $error;
                $this->logToFile($this->failedLogFilePath, $data);
            }
        }
    }

    /**
     * Log to a file
     */
    private function logToFile(string $filePath, array $data, string $response = ''): void
    {
        $logMessage = sprintf(
            "[%s] LEVEL: %s\nMessage: %s\n%s%s-----------------------------------\n",
            date('Y-m-d H:i:s'),
            $data['level'],
            $data['message'],
            !empty($response) ? "Response: $response\n" : '',
            !empty($data['curl_error']) ? "CURL Error: {$data['curl_error']}\nHTTP Code: {$data['http_code']}\n" : ''
        );

        file_put_contents($filePath, $logMessage, FILE_APPEND);
    }
}
