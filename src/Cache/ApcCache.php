<?php /** @noinspection PhpComposerExtensionStubsInspection */
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Orpheus\Cache;

/**
 * The APC cache class to use APC or APCu features to cache data.
 * This class is useful for perishable data.
 * So, it requires the APC or APCu lib to be installed on the server.
 * Look for php-apc package for Linux.
 * http://php.net/manual/en/book.apc.php
 */
class ApcCache implements Cache {
	
	const VERSION = 1;
	
	/**
	 * The class of the cache
	 *
	 * @var string|null Class of cache or null if not Orpheus cache
	 */
	protected ?string $class;
	
	/**
	 * The name of the cache
	 *
	 * @var string
	 */
	protected string $name;
	
	/**
	 * the key
	 *
	 * @var string
	 */
	protected string $key;
	
	/**
	 * The time to live
	 *
	 * @var int|null
	 */
	protected ?int $ttl;
	
	protected ?array $information = null;
	
	/**
	 * Is APC Available ?
	 *
	 * @var bool
	 */
	protected static ?bool $supportsApc = null;
	
	/**
	 * Is APCu Available ?
	 *
	 * @var bool
	 */
	protected static ?bool $supportsApcu = null;
	
	/**
	 * Constructor
	 */
	public function __construct(?string $class, string $name, ?int $ttl = null, bool $global = false) {
		$this->class = $class;
		$this->name = $name;
		$this->key = $class . '.' . $name . ($global ? '' : '@' . INSTANCE_ID);
		$this->ttl = $ttl;
	}
	
	/**
	 * Get the cache for the given parameters
	 * This method uses the apc_fetch() function.
	 * The data type is preserved, even for objects.
	 *
	 * @param mixed $cached The output to get the cache
	 * @return boolean True if cache has been retrieved
	 */
	public function get(mixed &$cached): bool {
		if( !static::hasSupport() ) {
			return false;
		}
		$fetched = static::isSupportingApc() ? apc_fetch($this->key, $success) : apcu_fetch($this->key, $success);
		if( $fetched !== false ) {
			$cached = $fetched;
		}
		return $success;
	}
	
	/**
	 * Set the cache for the given parameters
	 * This method uses the apc_store() function.
	 *
	 * @param mixed $data The data to put in the cache
	 * @return boolean True if cache has been saved
	 */
	public function set(mixed $data): bool {
		if( !static::hasSupport() ) {
			return false;
		}
		$ttl = $this->ttl ?? 0;
		
		return static::isSupportingApc() ? apc_store($this->key, $data, $ttl) : apcu_store($this->key, $data, $ttl);
	}
	
	/**
	 * Clear the cache
	 *
	 * @return boolean True in case of success
	 *
	 * This method uses the apc_delete() function.
	 */
	public function clear(): bool {
		if( !static::hasSupport() ) {
			return false;
		}
		
		return static::isSupportingApc() ? apc_delete($this->key) : apcu_delete($this->key);
	}
	
	public function getClass(): ?string {
		return $this->class;
	}
	
	public function getName(): string {
		return $this->name;
	}
	
	/**
	 * @throws CacheException
	 */
	public function getInformation(): array {
		if( !$this->information ) {
			if( !static::isSupportingApcu() ) {
				throw new CacheException('APCu is required to get information about cache');
			}
			$this->information = apcu_key_info($this->key);
		}
		//info = "sqladapter.db_configs@orpheus.local"
		//ttl = {int} 7200
		//num_hits = {float} 2.0
		//mtime = {int} 118422
		//creation_time = {int} 118422
		//deletion_time = {int} 0
		//access_time = {int} 118457
		//ref_count = {int} 0
		//mem_size = {int} 320
		return $this->information;
	}
	
	protected function setInformation(array $information): static {
		$this->information = $information;
		
		return $this;
	}
	
	/**
	 * @throws CacheException
	 */
	function getSize(): int {
		$information = $this->getInformation();
		return $information['mem_size'];
	}
	
	/**
	 * @throws CacheException
	 */
	function getHits(): int {
		$information = $this->getInformation();
		return $information['num_hits'];
	}
	
	/**
	 * List all APC Caches
	 *
	 * @return static[]
	 * @throws CacheException
	 */
	public static function list(): array {
		$caches = [];
		if( !static::isSupportingApcu() ) {
			throw new CacheException('APCu is required to list all caches');
		}
		
		$apcuInformation = apcu_cache_info();
		foreach( $apcuInformation['cache_list'] as $cacheInformation ) {
			preg_match('#^(.+?)(?:@([^@\n]+))?$#', $cacheInformation['info'], $matches);
			$key = $matches[1];
			$instanceId = $matches[2] ?? null;
			if( $instanceId && $instanceId !== INSTANCE_ID ) {
				// Ignore caches from another instance
				continue;
			}
			[$class, $name] = explode('.', $key, 2) + [1 => null];
			if(!$name) {
				$name = $class;
				$class = null;
			}
			$cache = new static($class, $name);
			$cache->setInformation($cacheInformation);
			$caches[$key] = $cache;
		}
		
		return $caches;
	}
	
	public static function clearAll(): bool {
		if( !static::hasSupport() ) {
			return false;
		}
		
		return static::isSupportingApc() ? apc_clear_cache('user') : apcu_clear_cache();
	}
	
	public static function hasSupport(): bool {
		return static::isSupportingApc() || static::isSupportingApcu();
	}
	
	public static function isSupportingApc(): bool {
		if( static::$supportsApc === null ) {
			static::$supportsApc = function_exists('apc_fetch');
		}
		
		return static::$supportsApc;
	}
	
	public static function isSupportingApcu(): bool {
		if( static::$supportsApcu === null ) {
			static::$supportsApcu = function_exists('apcu_fetch');
		}
		
		return static::$supportsApcu;
	}
	
}
