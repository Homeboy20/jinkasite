<?php
/**
 * Cache Manager for JINKA Plotter Website
 * 
 * Provides centralized cache management functionality that respects
 * admin settings for cache enable/disable and duration preferences.
 * 
 * @author ProCut Solutions
 * @version 1.0
 * @created 2025-11-19
 */

if (!defined('JINKA_ACCESS')) {
    die('Direct access not permitted');
}

class CacheManager {
    private $db;
    private $cacheDir;
    private $isEnabled;
    private $duration;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->cacheDir = defined('CACHE_PATH') ? CACHE_PATH : (__DIR__ . '/../cache/');
        $this->loadSettings();
        
        // Ensure cache directory exists
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0755, true);
        }
    }
    
    /**
     * Load cache settings from database, fallback to config constants
     */
    private function loadSettings() {
        $this->isEnabled = $this->getSetting('cache_enabled', defined('CACHE_ENABLED') ? CACHE_ENABLED : true);
        $duration = $this->getSetting('cache_duration', defined('CACHE_DURATION') ? CACHE_DURATION : 3600);
        $this->duration = (int)$duration;
        
        if ($this->duration <= 0) {
            $this->duration = 3600; // Default 1 hour
        }
    }
    
    /**
     * Get setting from database with fallback
     */
    private function getSetting($key, $default) {
        try {
            $stmt = $this->db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
            if (!$stmt) {
                return $default;
            }
            
            $stmt->bind_param('s', $key);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                $value = $row['setting_value'];
                // Convert string booleans to actual booleans
                if ($value === '1' || $value === 'true') {
                    return true;
                } elseif ($value === '0' || $value === 'false') {
                    return false;
                }
                return $value;
            }
            
            return $default;
        } catch (Exception $e) {
            return $default;
        }
    }
    
    /**
     * Generate cache key from identifier
     */
    private function getCacheKey($key) {
        return preg_replace('/[^a-zA-Z0-9_-]/', '_', $key);
    }
    
    /**
     * Get cache file path for key
     */
    private function getCacheFilePath($key) {
        $safeKey = $this->getCacheKey($key);
        return $this->cacheDir . $safeKey . '.cache';
    }
    
    /**
     * Check if caching is enabled
     */
    public function isEnabled() {
        return (bool)$this->isEnabled;
    }
    
    /**
     * Get cache duration in seconds
     */
    public function getDuration() {
        return $this->duration;
    }
    
    /**
     * Store data in cache
     */
    public function set($key, $data, $customDuration = null) {
        if (!$this->isEnabled()) {
            return false;
        }
        
        $filePath = $this->getCacheFilePath($key);
        $duration = $customDuration !== null ? (int)$customDuration : $this->duration;
        
        $cacheData = [
            'data' => $data,
            'created' => time(),
            'expires' => time() + $duration
        ];
        
        $serialized = serialize($cacheData);
        return @file_put_contents($filePath, $serialized, LOCK_EX) !== false;
    }
    
    /**
     * Retrieve data from cache
     */
    public function get($key) {
        if (!$this->isEnabled()) {
            return null;
        }
        
        $filePath = $this->getCacheFilePath($key);
        
        if (!file_exists($filePath)) {
            return null;
        }
        
        $contents = @file_get_contents($filePath);
        if ($contents === false) {
            return null;
        }
        
        $cacheData = @unserialize($contents);
        if ($cacheData === false || !is_array($cacheData)) {
            // Invalid cache file, remove it
            @unlink($filePath);
            return null;
        }
        
        // Check if expired
        if (isset($cacheData['expires']) && time() > $cacheData['expires']) {
            @unlink($filePath);
            return null;
        }
        
        return $cacheData['data'] ?? null;
    }
    
    /**
     * Delete specific cache entry
     */
    public function delete($key) {
        $filePath = $this->getCacheFilePath($key);
        if (file_exists($filePath)) {
            return @unlink($filePath);
        }
        return true;
    }
    
    /**
     * Check if cache entry exists and is valid
     */
    public function has($key) {
        return $this->get($key) !== null;
    }
    
    /**
     * Clear all cache files
     */
    public function clear() {
        $count = 0;
        $size = 0;
        
        if (!is_dir($this->cacheDir)) {
            return ['files' => 0, 'size' => 0];
        }
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->cacheDir, FilesystemIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $fileSize = $file->getSize();
                if (@unlink($file->getPathname())) {
                    $count++;
                    $size += $fileSize;
                }
            }
        }
        
        return ['files' => $count, 'size' => $size];
    }
    
    /**
     * Get cache statistics
     */
    public function getStats() {
        $stats = ['files' => 0, 'size' => 0, 'expired' => 0];
        
        if (!is_dir($this->cacheDir)) {
            return $stats;
        }
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->cacheDir, FilesystemIterator::SKIP_DOTS)
        );
        
        $now = time();
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $stats['files']++;
                $stats['size'] += $file->getSize();
                
                // Check if expired
                $contents = @file_get_contents($file->getPathname());
                if ($contents !== false) {
                    $cacheData = @unserialize($contents);
                    if (is_array($cacheData) && isset($cacheData['expires']) && $now > $cacheData['expires']) {
                        $stats['expired']++;
                    }
                }
            }
        }
        
        return $stats;
    }
    
    /**
     * Clean expired cache entries
     */
    public function cleanExpired() {
        $count = 0;
        $size = 0;
        
        if (!is_dir($this->cacheDir)) {
            return ['files' => 0, 'size' => 0];
        }
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->cacheDir, FilesystemIterator::SKIP_DOTS)
        );
        
        $now = time();
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $filePath = $file->getPathname();
                $contents = @file_get_contents($filePath);
                
                if ($contents !== false) {
                    $cacheData = @unserialize($contents);
                    if (is_array($cacheData) && isset($cacheData['expires']) && $now > $cacheData['expires']) {
                        $fileSize = $file->getSize();
                        if (@unlink($filePath)) {
                            $count++;
                            $size += $fileSize;
                        }
                    }
                }
            }
        }
        
        return ['files' => $count, 'size' => $size];
    }
    
    /**
     * Cache a function result
     */
    public function remember($key, $callback, $duration = null) {
        $cached = $this->get($key);
        if ($cached !== null) {
            return $cached;
        }
        
        $result = $callback();
        $this->set($key, $result, $duration);
        
        return $result;
    }
    
    /**
     * Format bytes to human readable size
     */
    public static function formatBytes($bytes) {
        $bytes = (int)$bytes;
        if ($bytes <= 0) {
            return '0 B';
        }
        
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = (int)floor(log($bytes, 1024));
        $power = max(0, min($power, count($units) - 1));
        $value = $bytes / pow(1024, $power);
        
        return ($power >= 2 ? number_format($value, 2) : number_format($value, 0)) . ' ' . $units[$power];
    }
}