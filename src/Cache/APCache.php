<?php
/**
 * APCache
 */

namespace Orpheus\Cache;

/**
 * The APC cache class
 * Uses APC feature to cache data.
 * This class is useful for perishable data.
 * So, it requires the APC lib to be installed on the server.
 * Look for php-apc package for Linux.
 * http://php.net/manual/en/book.apc.php
 */
class APCache implements Cache {
	
	/**
	 * Is APC Available ?
	 *
	 * @var bool
	 */
	protected static $APCavailable;
	
	/**
	 * the key
	 *
	 * @var string
	 */
	protected $key;
	/**
	 * The time to live
	 *
	 * @var int
	 */
	protected $ttl;
	
	/**
	 * Constructor
	 *
	 * @param string $class The class of the cache
	 * @param string $name The name of this cache
	 * @param int $ttl The time to live in seconds, the delay the cache expires for. Default value is 0 (manual delete only).
	 */
	public function __construct($class, $name, $ttl = 0, $global = false) {
		$this->ttl = $ttl;
		$this->key = $class . '.' . $name . ($global ? '' : '@' . INSTANCE_ID);
		if( static::$APCavailable === null ) {
			static::$APCavailable = function_exists('apc_fetch');
		}
	}
	
	/**
	 * Get the cache for the given parameters
	 *
	 * @param mixed $cached The output to get the cache
	 * @return boolean True if cache has been retrieved
	 *
	 * This method uses the apc_fetch() function.
	 * The type is preserved, even for objects.
	 */
	public function get(&$cached) {
		if( static::$APCavailable ) {
			$fc = apc_fetch($this->key, $success);
		} else {
			$fc = false;
			$success = false;
		}
		if( $fc !== false ) {
			$cached = $fc;
		}
		return $success;
	}
	
	/**
	 * Set the cache for the given parameters
	 *
	 * @param mixed $data The data to put in the cache
	 * @return boolean True if cache has been saved
	 *
	 * This method uses the apc_store() function.
	 */
	public function set($data) {
		return static::$APCavailable ? apc_store($this->key, $data, $this->ttl) : false;
	}
	
	/**
	 * Reset the cache
	 *
	 * @return boolean True in case of success
	 * @deprecated Use clear()
	 *
	 * This method uses the apc_delete() function.
	 */
	public function reset() {
		return $this->clear();
	}
	
	/**
	 * Clear the cache
	 *
	 * @return boolean True in case of success
	 *
	 * This method uses the apc_delete() function.
	 */
	public function clear() {
		return static::$APCavailable ? apc_delete($this->key) : false;
	}
}
