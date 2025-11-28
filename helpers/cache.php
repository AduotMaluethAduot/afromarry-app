<?php
/**
 * Enhanced caching system with memory and file-based caching
 */

class EnhancedCache {
    private $cache_dir;
    private $memory_cache = [];
    private $memory_cache_ttl = [];
    
    public function __construct($cache_dir = null) {
        $this->cache_dir = $cache_dir ?? __DIR__ . '/../cache';
        if (!is_dir($this->cache_dir)) {
            mkdir($this->cache_dir, 0755, true);
        }
    }
    
    /**
     * Get cached data
     */
    public function get($key, $ttl = 3600) {
        // First check memory cache
        if (isset($this->memory_cache[$key])) {
            $cache_time = $this->memory_cache_ttl[$key];
            if (time() - $cache_time < $ttl) {
                return $this->memory_cache[$key];
            } else {
                // Expired, remove from memory
                unset($this->memory_cache[$key]);
                unset($this->memory_cache_ttl[$key]);
            }
        }
        
        // Check file cache
        $file = $this->getCacheFile($key);
        
        if (!file_exists($file)) {
            return null;
        }
        
        // Check if cache has expired
        if (time() - filemtime($file) > $ttl) {
            unlink($file);
            return null;
        }
        
        $data = file_get_contents($file);
        $decoded_data = json_decode($data, true);
        
        // Store in memory cache for faster access next time
        $this->memory_cache[$key] = $decoded_data;
        $this->memory_cache_ttl[$key] = filemtime($file);
        
        return $decoded_data;
    }
    
    /**
     * Set cached data
     */
    public function set($key, $data) {
        // Store in memory cache
        $this->memory_cache[$key] = $data;
        $this->memory_cache_ttl[$key] = time();
        
        // Store in file cache
        $file = $this->getCacheFile($key);
        $json_data = json_encode($data);
        file_put_contents($file, $json_data, LOCK_EX);
    }
    
    /**
     * Delete cached data
     */
    public function delete($key) {
        // Remove from memory cache
        unset($this->memory_cache[$key]);
        unset($this->memory_cache_ttl[$key]);
        
        // Remove from file cache
        $file = $this->getCacheFile($key);
        if (file_exists($file)) {
            unlink($file);
        }
    }
    
    /**
     * Clear all cache
     */
    public function clear() {
        // Clear memory cache
        $this->memory_cache = [];
        $this->memory_cache_ttl = [];
        
        // Clear file cache
        $files = glob($this->cache_dir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
    
    /**
     * Get cache statistics
     */
    public function getStats() {
        return [
            'memory_items' => count($this->memory_cache),
            'file_items' => count(glob($this->cache_dir . '/*')),
        ];
    }
    
    /**
     * Get cache file path
     */
    private function getCacheFile($key) {
        $safe_key = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $key);
        return $this->cache_dir . '/' . $safe_key . '.cache';
    }
}

// Global cache instance
$cache = new EnhancedCache();

/**
 * Helper functions
 */
function cache_get($key, $ttl = 3600) {
    global $cache;
    return $cache->get($key, $ttl);
}

function cache_set($key, $data) {
    global $cache;
    return $cache->set($key, $data);
}

function cache_delete($key) {
    global $cache;
    return $cache->delete($key);
}

function cache_clear() {
    global $cache;
    return $cache->clear();
}

function cache_stats() {
    global $cache;
    return $cache->getStats();
}

function cache_reset_counters() {
    global $cache;
    // This is a placeholder - in a real implementation, you might want to reset cache statistics
    return true;
}
?>