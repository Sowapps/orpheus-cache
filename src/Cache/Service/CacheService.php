<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Orpheus\Cache\Service;

use Orpheus\Cache\ApcCache;
use Orpheus\Cache\FileSystemCache;

class CacheService {
	
	private array $caches;
	
	private static ?CacheService $instance = null;
	
	public function getAllCaches(): array {
		if(!isset($this->caches)) {
			// Calculate supported caches
			$caches = [
				'fs' => FileSystemCache::class,
			];
			if( ApcCache::hasSupport() ) {
				$caches['apc'] = ApcCache::class;
			}
			$this->caches = $caches;
		}
		
		return $this->caches;
	}
	
	public static function get(): CacheService {
		return static::$instance ??= new CacheService();
	}
	
}
