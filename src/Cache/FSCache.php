<?php
/**
 * FSCache
 */

namespace Orpheus\Cache;

use Exception;

/**
 * The file system cache class
 *
 * Uses File System to cache data. This class is useful for dated data.
 * This class requires a CACHE_PATH constant containing the path to the cache folder, you can also override getFolderPath() to determine the path by another way.
 */
class FSCache implements Cache {
	
	/**
	 * The extension of cache files
	 *
	 * @var string
	 */
	protected static string $ext = '.cache';
	
	/**
	 * The delimiter in cache file
	 *
	 * @var string
	 */
	protected static string $delim = '|';
	
	/**
	 * The path to the cache
	 *
	 * @var string
	 */
	protected string $path;
	
	/**
	 * The edit time to use
	 *
	 * @var int
	 */
	protected ?int $editTime = null;
	
	/**
	 * Constructor
	 *
	 * @param string $class The class of the cache
	 * @param string $name The name of this cache
	 * @param int $editTime The last modification time of the cache. Default value is 0 (undefined).
	 * @throws Exception
	 */
	public function __construct($class, $name, $editTime = null) {
		$this->editTime = $editTime;
		$this->path = static::getFilePath($class, $name);
		$folder = static::getFolderPath($class);
		if( !is_dir($folder) && !mkdir($folder, 0777, true) ) {
			throw new Exception('unwritableClassFolder');
		}
	}
	
	/**
	 * Get the fle path of this cache
	 *
	 * @param string $class The class to use
	 * @param string $name The name to use
	 * @return string The path of this cache file.
	 */
	public static function getFilePath($class, $name): string {
		return static::getFolderPath($class) . '/' . strtr($name, '/', '_') . static::$ext;
	}
	
	/**
	 * Get the folder path for the cache
	 *
	 * @param string $class The class to use
	 * @return string The path of this cache folder in the global cache folder.
	 */
	public static function getFolderPath($class): string {
		return CACHE_PATH . '/' . $class;
	}
	
	/**
	 * Get the cache for the given parameters
	 * This method serializes the data in the file using json_encode().
	 * The type is preserved, even for objects.
	 *
	 * @param mixed $cached The output to get the cache
	 * @return bool True if cache has been retrieved
	 */
	public function get(&$cached): bool {
		try {
			if( !is_readable($this->path) ) {
				return false;
			}
			[$editTime, $data] = explodeList(static::$delim, file_get_contents($this->path), 2);
			if( isset($this->editTime) && $editTime != $this->editTime ) {
				return false;
			}
			$cached = unserialize($data);
		} catch( Exception $e ) {
			// If error opening file or un-serializing occurred, it's a fail
			return false;
		}
		return true;
	}
	
	/**
	 * Set the cache for the given parameters
	 * This method un-serializes the data in the file using json_decode().
	 * The type is saved too.
	 *
	 * @param mixed $data The data to put in the cache
	 * @return bool True if cache has been saved
	 * @throws CacheException
	 * @see serialize()
	 */
	public function set($data): bool {
		try {
			return file_put_contents($this->path, $this->editTime . static::$delim . serialize($data));
		} catch( Exception $e ) {
			throw new CacheException($e->getMessage(), $e->getCode(), $e);
		}
	}
	
	/**
	 * Reset the cache
	 * This method uses unlink() function.
	 */
	public function reset() {
		unlink($this->path);
	}
	
	/**
	 * List all FS Cache files
	 *
	 * @return string[] All cache files by class.
	 */
	public static function listAll(): array {
		$list = [];
		foreach( cleanscandir(CACHE_PATH) as $cPath ) {
			$list[$cPath] = [];
			foreach( cleanscandir(CACHE_PATH . '/' . $cPath) as $fPath ) {
				$list[$cPath][pathinfo($fPath, PATHINFO_FILENAME)] = CACHE_PATH . '/' . $cPath . '/' . $fPath;
			}
		}
		
		return $list;
	}
}
