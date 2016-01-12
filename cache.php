<?php

class CacheEntry {
	public $data;
	public $etag;
	public $time;
}

class Cache {
	static protected function getFile($name) {
		return __DIR__ . '/cache/' . urlencode($name);
	}
	
	static protected function getTime($name) {
		return @filemtime(static::getFile($name));
	}

	/**
	 * @return CacheEntry
	 */
	static public function getOrGenerateContent($name, $usedFiles, $callback) {
		$cachedFile = static::getFile($name);
		
		$cachedFileTime = @filemtime($cachedFile);
		$maxTime = max(array_map('filemtime', $usedFiles));
		
		if ($cachedFileTime < $maxTime) {
			$content = $callback();
			file_put_contents($cachedFile, $content);
			file_put_contents("{$cachedFile}.etag", md5($content));
			touch($cachedFile, $maxTime, $maxTime);
			$cachedFileTime = $maxTime;
		}
		
		$cacheEntry = new CacheEntry();
		$cacheEntry->data = file_get_contents($cachedFile);
		$cacheEntry->etag = file_get_contents("{$cachedFile}.etag");
		$cacheEntry->time = $cachedFileTime; 
		return $cacheEntry;
	}	
}
