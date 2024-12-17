
### **HTTPLogger Class Documentation**

**Class Name:** `HttpLogger`  
**Namespace:** `Rcezea\HttpLogger`  
**Implements:** `Tracy\ILogger`

---

### **Overview**
The `HttpLogger` class implements the `ILogger` interface to send error logs to a server endpoint while providing optional local file logging. It supports both web and mobile platforms and enables environment-based logging modes (Development or Production).

---

### **Features**
1. **Server-Side Logging:** Sends logs to a configurable server endpoint.
2. **Optional File Logging:** Logs success and failed attempts to local files for debugging.
3. **API Authentication:** Supports optional credentials (`appId` and `secretKey`) for secure logging.
4. **Platform-Specific Logging:** Configurable for `web` or `mobile`.
5. **Environment Modes:** Differentiates between Development and Production modes.

---

### **Static Properties**

| Property    | Type     | Default Value                          | Description                              |
|-------------|----------|----------------------------------------|------------------------------------------|
| `$endpoint` | `string` | `'http://127.0.0.1:5000/errorhandler'` | Server endpoint URL for logs.            |
| `$mode`     | `bool`   | `Debugger::Development`                | Logging mode: Development or Production. |
| `$platform` | `string` | `'web'`                                | Specifies platform: `web` or `mobile`.   |

---

### **Constructor**

**Method Signature:**
```php
public function __construct(
    ?string $appId = NULL,
    ?string $secretKey = NULL,
    bool $logToFile = FALSE,
    ?string $logDir = NULL
)
```

**Parameters:**
- `$appId` (`string|null`): Optional API authentication ID.
- `$secretKey` (`string|null`): Optional API secret key.
- `$logToFile` (`bool`): Enables or disables file logging.
- `$logDir` (`string|null`): Directory path for storing log files. Defaults to `../logs` if not provided.

**Throws:**
- `\RuntimeException` if the log directory cannot be created.

---

### **Static Methods**

1. **`setEndpoint(string $url): void`**  
   Sets the logging server endpoint.  
   **Throws:** `\InvalidArgumentException` if the URL is invalid.

2. **`setMode(int $mode): void`**  
   Sets the environment mode: `Debugger::Development` or `Debugger::Production`.  
   **Throws:** `\InvalidArgumentException` for invalid modes.

3. **`setPlatform(string $platform): void`**  
   Sets the platform type (`web` or `mobile`).  
   **Throws:** `\InvalidArgumentException` for invalid platforms.

---

### **Instance Methods**

1. **`log(mixed $value, string $level = ILogger::INFO): void`**  
   Logs messages or exceptions to the server and optionally to local files.
    - `$value` (`mixed`): The message or exception to log.
    - `$level` (`string`): Log level, e.g., `ILogger::INFO`.

   **Behavior:**
    - Sends logs to the configured endpoint using `cURL`.
    - Includes optional API credentials.
    - Determines the mode (`development` or `production`) based on the static property.
    - Writes to local files if file logging is enabled.

2. **`sendLogToServer(array $data): void`**  
   Sends the prepared log data to the server endpoint using `cURL`.

3. **`logToFile(string $filePath, array $data, string $response = ''): void`**  
   Writes logs to the specified file path.

---

### **Logging to Files**

If `$logToFile` is enabled during object instantiation, logs will be written to:
- **Success Logs:** `success_log.txt`
- **Failed Logs:** `failed_log.txt`

**Format of File Logs:**
```
[2024-06-17 10:45:00] 
LEVEL: info
Message: Sample log message
Stack: N/A
Platform: web
Environment: development
-----------------------------------
```

For failed attempts, `curl_error` and server responses will be included.

---

### **Usage Example**

```php
use Rcezea\HttpLogger\HttpLogger;
use Tracy\Debugger;

// Initialize Logger
$logger = new HttpLogger(
    appId: 'myAppId',
    secretKey: 'mySecretKey',
    logToFile: true
);

// Set optional configurations
HttpLogger::setEndpoint('https://api.example.com/logs');
HttpLogger::setPlatform('mobile');
HttpLogger::setMode(Debugger::Production);

// Log an exception
try {
    throw new \Exception('Something went wrong!');
} catch (\Throwable $e) {
    $logger->log($e, ILogger::ERROR);
}

// Log a message
$logger->log('This is an informational message.', ILogger::INFO);
```

---

### **Requirements**
- PHP 8.0+
- Composer dependencies: `tracy/tracy`

---

### **Changelog**
- Added platform configuration (`setPlatform`).
- Enhanced file logging to include success and failure logs.
- Integrated API authentication.


---

### License
This package is licensed under the MIT License.

---

### Contributing
Contributions are welcome! Feel free to submit pull requests or open issues for improvements or bug fixes.

---

### Author
* **Richard E** - Github: [rcezea](https://github.com/rcezea)

---

### Support
For questions, issues, or support, please contact [rclancing@gmail.com](mailto:rclancing@gmail.com).
