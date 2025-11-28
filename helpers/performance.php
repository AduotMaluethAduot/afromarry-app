<?php
/**
 * Performance monitoring helper functions
 */

class PerformanceMonitor {
    private static $timers = [];
    private static $counters = [];
    
    /**
     * Start a timer
     */
    public static function startTimer($name) {
        self::$timers[$name] = microtime(true);
    }
    
    /**
     * Stop a timer and return elapsed time
     */
    public static function stopTimer($name) {
        if (!isset(self::$timers[$name])) {
            return null;
        }
        
        $elapsed = microtime(true) - self::$timers[$name];
        unset(self::$timers[$name]);
        return $elapsed;
    }
    
    /**
     * Increment a counter
     */
    public static function incrementCounter($name, $value = 1) {
        if (!isset(self::$counters[$name])) {
            self::$counters[$name] = 0;
        }
        self::$counters[$name] += $value;
    }
    
    /**
     * Get counter value
     */
    public static function getCounter($name) {
        return self::$counters[$name] ?? 0;
    }
    
    /**
     * Get all counters
     */
    public static function getCounters() {
        return self::$counters;
    }
    
    /**
     * Reset all counters
     */
    public static function resetCounters() {
        self::$counters = [];
    }
    
    /**
     * Log performance metrics
     */
    public static function logMetrics($message = '') {
        $metrics = [
            'timestamp' => date('Y-m-d H:i:s'),
            'message' => $message,
            'counters' => self::$counters,
            'memory_usage' => memory_get_usage(true),
            'peak_memory_usage' => memory_get_peak_usage(true)
        ];
        
        $log_file = __DIR__ . '/../logs/performance.log';
        $log_dir = dirname($log_file);
        
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        
        file_put_contents($log_file, json_encode($metrics) . "\n", FILE_APPEND | LOCK_EX);
    }
}

/**
 * Helper functions
 */
function perf_start_timer($name) {
    PerformanceMonitor::startTimer($name);
}

function perf_stop_timer($name) {
    return PerformanceMonitor::stopTimer($name);
}

function perf_increment_counter($name, $value = 1) {
    PerformanceMonitor::incrementCounter($name, $value);
}

function perf_get_counter($name) {
    return PerformanceMonitor::getCounter($name);
}

function perf_get_counters() {
    return PerformanceMonitor::getCounters();
}

function perf_reset_counters() {
    PerformanceMonitor::resetCounters();
}

function perf_log_metrics($message = '') {
    PerformanceMonitor::logMetrics($message);
}
?>