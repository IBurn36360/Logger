<?php

namespace IBurn36360\Logger;

/**
 * Logger library, used to capture any and all errors and warnings from a scripts execution
 * 
 * Supports method chaining
 * 
 * Final prevents this from being extended
 * 
 * @author Anthony 'IBurn36360' Diaz
 * @final
 * @name Logger
 * @version 2.0.0
 */
final class Logger {
    /**
     * @var string|$file - The filename of the current log file
     * 
     * @access private
     */
    private $file;
    
    /**
     * @var resource|$handle - The file handle used to write to the log file
     * 
     * @access private
     */
    private $handle;
    
    /**
     * @var int|$spacer - The number of spaces to try to keep between the timestamp and the log line
     * 
     * @access private
     */
    private $spacer = 17;
    
    /**
     * @var string|$basePath - The string basepath for exception and fatal logs.  Set during construction
     * 
     * @access private
     */
    private $basePath = null;
    
    /**
     * @var int|$errorLevel - The error logging level.  Defaults to E_ALL
     * 
     * @access private
     */
    private $errorLevel = E_ALL;
    
    /**
     * Creates a new Logger instance
     * 
     * @param string|$logFile - The 
     * 
     * @access public
     * 
     * @return 
     */
    public function __construct($logFile) {
        $this->basePath = realpath($logFile);
        
        $this->createLoggingDirectory($this->basePath);
        
        // does our log file exist?
        if (file_exists($logFile)) {
            $this->handle = fopen($logFile, 'a');
        } else {
            $this->handle = fopen($logFile, 'w');
            fwrite($this->handle, '<?php exit; ?>');
        }
        
        return $this;
    }
    
    /**
     * Releases the file on script execution close so that it doesn't need to be done manually
     * 
     * @access public
     */
    public function __destruct() {
        @fclose($this->handle);
    }
    
    /**
     * Used for interacting with PHPs errors
     * 
     * @access public
     * 
     * @throws \ErrorException on any errors that are not notices so they can be caught and dealt with
     */
    public function siteErrorHandler($errNo, $errStr, $file, $line) {
        if (!(error_reporting() & $errNo)) {
            // This error code is not included in error_reporting
            return;
        }
        
        $this->logErrorHandler($errNo, $errStr, $file, $line);
        
        // Don't throw notices, but still have them logged
        if ($errNo != E_NOTICE) {
            throw new ErrorException($errStr, 0, $errNo, $file, $line);
        }
    }
    
    /**
     * Used for interacting with PHPs Exceptions.  Updates the log file to uncaught_exception_log.php
     * 
     * @access public
     */
    public function siteExceptionHandler($exception) {
        $this->updateLogFile((($this->basePath == null) ? __DIR__ . '/../../logs/' : $this->basePath) . 
            'uncaught_exception_log.php');
        
        $this->logException($exception);
    }
    
    /**
     * Registered as the shutdown function and attempts to capture fatal crashes.  Updates the log file to fatal_log.php
     * 
     * @access public
     */
    public function siteFatalCrashHandler() {
        $this->updateLogFile((($this->basePath == null) ? __DIR__ . '/../../logs/' : $this->basePath) . 
            '/fatal_log.php');
        
        $err = error_get_last();
        
        if (isset($err) && !empty($err)) {
            $this->logErrorHandler($err['type'], $err['message'], $err['file'], $err['line']);
        }
    }
    
    /**
     * Registeres all error handlers.  Captures exceptions, fatal errors and registers the normal error handler to ours
     * 
     * @access public
     */
    public function registerErrorHandlers() {
        // Set error handlers
        set_error_handler(array($this, 'siteErrorHandler'));
        set_exception_handler(array($this, 'siteExceptionHandler'));
        register_shutdown_function(array($this, 'siteFatalCrashHandler'));
        
        // Set error reporting to the specified level, defaults to E_ALL
        error_reporting($this->errorLevel);
    }
    
    /**
     * Updates what log file is currently being used
     * 
     * @param $newLogFile[string] - The fullpath to the new log file
     * 
     * @access public
     */
    public function updateLogFile($newLogFile = '') {
        if ($newLogFile) {
            @fclose($this->handle);
            
            $this->basePath = realpath($logFile);
            
            $this->createLoggingDirectory($this->basePath);
            
            if (file_exists($newLogFile)) {
                $this->handle = fopen($newLogFile, 'a');
            } else {
                $this->handle = fopen($newLogFile, 'w');
                fwrite($this->handle, '<?php exit; ?>');
            }
        }
        
        return $this;
    }
    
    /**
     * Creates the logging directory recursively
     * 
     * @param string|$path - The fullpath to the log file
     * 
     * @access public
     */
    private function createLoggingDirectory($path = '') {
        $parts       = explode(DIRECTORY_SEPARATOR , $path);
        $currentPath = ((PHP_OS == 'WINNT') ? '' : DIRECTORY_SEPARATOR) . $parts[0];
        
        array_shift($parts);
        array_pop($parts);
        
        foreach ($parts as $part) {
            $currentPath .= DIRECTORY_SEPARATOR . $part;
            
            if (!file_exists($currentPath . DIRECTORY_SEPARATOR)) {
                mkdir($currentPath . DIRECTORY_SEPARATOR, 0775, false);
            }
        }
    }
    
    /**
     * Writes a new line to the log as is
     * 
     * @param string|$str - String line to be written
     * 
     * @access public
     */
    private function writeLine($str) {
        @fwrite($this->handle, "\n" . str_replace("\r\n", ' ', $str));
    }
    
    /**
     * Logs a line of output to the log file
     * 
     * @param string|$line - String message to log to the file
     * @param string|$module - String module name for the log entry
     * 
     * @access public
     */
    public function logLine($line, $module = 'undefined') {
        $logTime = date('m-d-y[H:i:s]', time());
        $space   = '';
        
        for ($i = 0; $i <= ($this->spacer - strlen($module)); $i++) {
            $space .= ' ';
        }
        
        $this->writeLine($logTime . $space . '[' . strtoupper($module) . "] $line");
        
        return $this;
    }
    
    public function logCustomLine($line) {
        $logTime = date('m-d-y[H:i:s]', time());
        $space   = '';
        
        for ($i = 0; $i <= $this->spacer; $i++) {
            $space .= ' ';
        }
        
        $this->writeLine($logTime . $space . $line);
        
        return $this;
    }
    
    public function prettyUpVariable($var) {
        // Check the type and pretty the variable up
        if (is_array($var)) {
            return '(Array)';
        } elseif (is_object($var)) {
            return '(Object)';
        } elseif (is_resource($var)) {
            return '(Resource)';
        } elseif (is_null($var)) {
            return '(Null)';
        } elseif (is_bool($var)) {
            return (((bool) $var) ? '(True)' : '(False)');
        } else {
            return '"' . ((strlen($var) < 80) ? strval($var) : substr(strval($var), 0, 80) . '...') . '"';
        }
        
        return $this;
    }
    
    /**
     * Logs an error to the file
     * 
     * @param string|$str - string error to write to the file
     * 
     * @access public
     */
    public function logError($str) {
        $logTime = date('m-d-y[H:i:s]', time());
        $space   = '';
        
        for ($i = 0; $i <= ($this->spacer - 5); $i++) {
            $space .= ' ';
        }
        
        $this->writeLine($logTime . $space . "[ERROR] $str");
        
        return $this;
    }
    
    /**
     * Our error handler, takes control of all trigger_error output
     * 
     * @param int|$errNo - The integer error number
     * @param string|$errStr - The string message of the error
     * @param string|$file - The string filename for the error
     * @param int|$line - The int line number of the error
     * 
     * @access public
     */
    public function logErrorHandler($errNo, $errStr, $file, $line) {
        $logTime = date('m-d-y[H:i:s]', time());
        $space   = '';
        
        switch ($errNo) {
            case E_DEPRECATED:
            case E_USER_DEPRECATED:
                $errLevel = 'DEPRECIATED';
                break;
            
            case E_USER_NOTICE:
            case E_NOTICE:
                $errLevel = 'NOTICE';
                break;
            
            case E_ERROR;
                $errLevel = 'FATAL';
                break;
            
            case E_STRICT:
                $errLevel = 'STRICT';
                break;
            
            case E_USER_ERROR:
                $errLevel = 'USER_ERROR';
                break;
            
            case E_USER_WARNING:
                $errLevel = 'USER_WARNING';
                break;
            
            case E_WARNING:
                $errLevel = 'WARNING';
                break;
            
            default:
                $errLevel = 'ERROR';
                break;
        }
        
        for ($i = 0; $i <= ($this->spacer - strlen($errLevel)); $i++) {
            $space .= ' ';
        }
        
        $this->writeLine($logTime . $space . "[$errLevel] $errStr In [$file:$line]");
        
        $stack = debug_backtrace();
        
        if (is_array($stack) && !empty($stack) && (count($stack) > 1)) {
            array_shift($stack);
            $this->logStackTrace($stack);
        }
        
        return $this;
    }
    
    /**
     * Prints out the stacktrace for an error
     * 
     * @param - array|$stack     - The full stacktrace for the error
     * @param - bool|$customData - Prints extra spacer for custom output lines preceeding the trace
     * 
     * @access public
     */
    public function logStackTrace($stack, $customData = false) {
        foreach ($stack as $row) {
            // The extra checks here stop our custom error handlers from being part of the stack
            if (empty($row) || (isset($row['function']) && (stristr($row['function'], 'siteFatalCrashHandler') || 
                stristr($row['function'], 'siteExceptionHandler') || stristr($row['function'], 'siteErrorHandler')))) {
                continue;
            }
            
            $str = '';
            
            for ($i = 0; $i <= ($this->spacer + 27); $i++) {
                $str .= ' ';
            }
            
            $str .= (($customData) ? '    ' : '') . 'From: ' . ((isset($row['class']) && isset($row['type'])) ? 
                $row['class'] . $row['type'] : '') . $row['function'] . '(';
            
            foreach ($row['args'] as $arg) {
                $str .= $this->prettyUpVariable($arg) . ', ';
            }
            
            $str = rtrim(rtrim($str, ' '), ',') . ')' .
                ((isset($row['file']) && isset($row['line'])) ? ' Called at ' . '[' . $row['file'] . ':' . $row['line'] . 
                    ']' : '');
            
            $this->writeLine($str);
        }
        
        return $this;
    }
    
    /**
     * Logs exceptions if they are thrown
     * 
     * @param object|$exception - The exception object tossed when an exception is thrown
     * 
     * @access public
     */
    public function logException($exception) {
        $logTime    = date('m-d-y[H:i:s]', time());
        $space      = '';
        $customData = false;
        
        for ($i = 0; $i <= ($this->spacer - 9); $i++) {
            $space .= ' ';
        }
        
        // We have some custom handling for special exceptions
        if (is_a($exception, 'IBurn36360\DB\Exception\ExceptionContainer') || 
            is_a($exception, 'IBurn36360\Route\Exception\ExceptionContainer')) {
            
            $this->writeLine($logTime . $space . "[EXCEPTION] " . $exception->getMessage() . '. In file "' . 
                $exception->getFile() . '" on line ' . $exception->getLine());
            $space .= '                                   ';
            $info   = $exception->getInfo();
            
            if (count($info)) {
                foreach ($info as $key => $value) {
                    $this->writeLine("{$space}$key: " . $this->prettyUpVariable($value));
                }
            } else {
                $this->writeLine("{$space}No data provided for this exception");
            }
            
            $customData = true;
        } else {
            $this->writeLine("$logTime{$space}[EXCEPTION] " . $exception->getMessage() . '. In file "' . $exception->getFile() . '" on line ' . $exception->getLine());
        }
        
        // Check for an exception trace and print the backtrace
        $this->logStackTrace($exception->getTrace(), $customData);
        
        return $this;
    }
    
    /**
     * Attempts to log a fatal error.  This is the shutdown function
     * 
     * @access public
     */
    public function logFatal() {
        $str     = error_get_last();
        $logTime = date('m-d-y[H:i:s]', time());
        $space   = '';
        
        for ($i = 0; $i <= ($this->spacer - 5); $i++) {
            $space .= ' ';
        }
        
        $this->writeLine($logTime . $space . "[FATAL] $str");
        
        return $this;
    }
}
