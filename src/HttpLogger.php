<?php

namespace Rcezea\HttpLogger;
use Tracy\Debugger;

use Tracy\ILogger;

require_once dirname(__DIR__) . '/vendor/autoload.php';

class HttpLogger implements ILogger
{
    private string $endpoint;
    private ?string $successLogFilePath;
    private ?string $failedLogFilePath;
    private string $appId;
    private string $secretKey;

    public function __construct(
        string $endpoint,
        string $appId,
        string $secretKey,
        bool $logToFile = false,
        string $logDir = null
    ) {
        if (empty($appId) || empty($secretKey)) {
            throw new \InvalidArgumentException('Both appId and secretKey are required.');
        }
        $this->appId = $appId;
        $this->secretKey = $secretKey;

        if (!filter_var($endpoint, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException("Invalid endpoint URL: $endpoint");
        }
        $this->endpoint = $endpoint;

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

        Debugger::enable(Debugger::PRODUCTION, $logDir);
    }

    public function log($value, $level = ILogger::INFO): void
    {
        $message = $value instanceof \Throwable
            ? $value->getMessage() . ' in ' . $value->getFile() . ':' . $value->getLine()
            : (is_scalar($value) ? (string)$value : json_encode($value, JSON_PARTIAL_OUTPUT_ON_ERROR));

        $data = [
            'level' => $level,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s'),
            'app_id' => $this->appId,
            'secret_key' => $this->secretKey,
        ];

        $this->sendLogToServer($data);
    }

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

//Debugger::setLogger(new HttpLogger('http://127.0.0.1:5000/errorhandler', 'asrdtdtdt', 'xffgxxcxgccgfy', true));
