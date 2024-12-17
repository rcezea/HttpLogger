# HTTP Logger for PHP

`Rcezea\HttpLogger` is a lightweight and flexible logging library for PHP applications. It enables developers to send log data to a remote server, log to local files, and switch between **DEVELOPMENT** and **PRODUCTION** modes dynamically.

Built with **Tracy Debugger** support, this logger is suitable for error monitoring, real-time bug tracking, and logging critical information in web applications.

---

## Features
- Send logs to a remote API endpoint
- Optional API security with `app_id` and `secret_key`
- Log errors and messages to local files
- Configurable logging modes: **DEVELOPMENT** and **PRODUCTION**
- Fully compatible with Tracy Debugger

---

## Installation
Install the package via Composer:

```bash
composer require rcezea/http-logger
```

---

## Initialization

### Basic Example (No API Security)

```php
use Rcezea\HttpLogger\httplogger;
use Tracy\Debugger;

require 'vendor/autoload.php';

// Set mode to DEVELOPMENT or PRODUCTION
httplogger::setMode(Debugger::DEVELOPMENT);

// Initialize logger without API keys
$logger = new httplogger(
    'https://example.com/log' // Endpoint URL
);

// Log a simple message
$logger->log('This is a basic test log message.');
```

### Example with API Security
If your endpoint requires API keys:

```php
use Rcezea\HttpLogger\httplogger;
use Tracy\Debugger;

require 'vendor/autoload.php';

// Set mode to PRODUCTION
httplogger::setMode(Debugger::PRODUCTION);

// Initialize logger with API keys
$logger = new httplogger(
    'https://example.com/log', // Endpoint URL
    'your_app_id',             // App ID
    'your_secret_key',         // Secret Key
    true,                      // Enable log to file
    __DIR__ . '/logs'          // Log directory
);

// Log an INFO message
$logger->log('Application initialized successfully.');
```

---

## Logging Exceptions and Errors
You can log exceptions or errors directly:

```php
try {
    // Simulate an exception
    throw new \Exception('Something went wrong!');
} catch (\Throwable $e) {
    $logger->log($e, \Tracy\ILogger::ERROR);
}
```

---

## Logging Modes

### DEVELOPMENT Mode
- Used for local testing and debugging.
- Detailed errors and log data are available for developers.

### PRODUCTION Mode
- Used in live applications.
- Suppresses detailed errors and only logs critical information.

You can dynamically set the mode using the `httplogger::setMode()` method:

```php
use Tracy\Debugger;
use Rcezea\HttpLogger\httplogger;

// Switch to PRODUCTION mode
httplogger::setMode(Debugger::PRODUCTION);
```

---

## Local File Logging
To enable local file logging, pass `true` as the `logToFile` parameter and specify the log directory:

```php
$logger = new httplogger(
    'https://example.com/log',
    null, // No App ID
    null, // No Secret Key
    true, // Enable file logging
    __DIR__ . '/logs' // Log directory
);
```
- Successful requests will be logged in `success_log.txt`.
- Failed requests will be logged in `failed_log.txt`.

---

## API Log Data
The following data is sent to the endpoint:

| Field          | Description                        |
|----------------|------------------------------------|
| `level`        | Log level (INFO, WARNING, ERROR)   |
| `message`      | The log message or error details   |
| `timestamp`    | Timestamp of the log event         |
| `app_id`       | (Optional) Your application ID     |
| `secret_key`   | (Optional) Your secret key         |

---

## Example Log Payload

```json
{
  "level": "ERROR",
  "message": "Something went wrong! in index.php:42",
  "timestamp": "2024-06-17 14:45:00",
  "app_id": "your_app_id",
  "secret_key": "your_secret_key"
}
```

---

## Error Handling
If the request to the endpoint fails:
- The error response, HTTP status code, and CURL error will be logged locally in the `failed_log.txt` file.

---

## Requirements
- PHP 8.0+
- Composer
- Tracy Debugger (included as a dependency)

---

## License
This package is licensed under the MIT License.

---

## Contributing
Contributions are welcome! Feel free to submit pull requests or open issues for improvements or bug fixes.

---

## Author
**Rcezea**  
A lightweight logging solution for modern PHP applications.

---

## Support
For questions, issues, or support, please contact [support@example.com](mailto:support@example.com).
