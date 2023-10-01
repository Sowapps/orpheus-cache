<?php
/**
 * @author Florent Hazard <contact@sowapps.com>
 */

namespace Orpheus\Cache; 

/**
 * The interface to use to define a cache class.
 *
 * @package orpheus-cache
 */
interface Cache {
	
	/**
	 * Get the cache for the given parameters
	 * The type of the data should be preserved, even for objects.
	 * 
	 * @param mixed $cached The output to get the cache
	 * @return bool True if cache has been retrieved
	 */
	function get(mixed &$cached): bool;
	
	/**
	 * Set the cache for the given parameters
	 * 
	 * @param mixed $data The data to put in the cache
	 * @return bool True if cache has been saved
	 */
	function set(mixed $data): bool;

	/**
	 * Reset the cache
	 * 
	 * @return bool True in case of success
	 */
	function clear(): bool;
	
	/**
	 * Class of the cache
	 */
	function getClass(): ?string;
	
	/**
	 * Name of the cache's class
	 */
	function getName(): string;
	
	/**
	 * Get size in bytes. 0 if none.
	 */
	function getSize(): int;
	
	/**
	 * Get hit count.
	 * Null if not supported
	 */
	function getHits(): ?int;
	
	/**
	 * List all
	 */
	static function list(): array;
	
	/**
	 * Clear all
	 */
	static function clearAll(): bool;
}
