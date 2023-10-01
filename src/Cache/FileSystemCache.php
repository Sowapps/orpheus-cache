<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Orpheus\Cache;

use Exception;

/**
 * The file system cache class
 *
 * Uses File System to cache data. This class is useful for dated data.
 * This class requires a CACHE_PATH constant containing the path to the cache folder, you can also override getFolderPath() to determine the path by another way.
 */
class FileSystemCache implements Cache {
	
	const VERSION = 1;
	
	/**
	 * The class of the cache
	 *
	 * @var string
	 */
	protected string $class;
	
	/**
	 * The name of the cache
	 *
	 * @var string
	 */
	protected string $name;
	
	/**
	 * The path to the cache
	 *
	 * @var string
	 */
	protected string $path;
	
	/**
	 * The edit time to use
	 *
	 * @var int|null
	 */
	protected ?int $editTime = null;
	
	protected ?array $information = null;
	
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
	 * Constructor
	 *
	 * @param string $class The class of the cache
	 * @param string $name The name of this cache
	 * @param int|null $editTime The last modification time of the cache. Default value is 0 (undefined).
	 * @throws CacheException
	 */
	public function __construct(string $class, string $name, ?int $editTime = null) {
		$this->class = $class;
		$this->name = $name;
		$this->editTime = $editTime;
		$this->path = static::getFilePath($class, $name);
		$folder = static::getClassPath($class);
		if( !is_dir($folder) && !mkdir($folder, 0777, true) ) {
			throw new CacheException('unwritableClassFolder');
		}
	}
	
	/**
	 * Get the cache for the given parameters
	 * This method serializes the data in the file using json_encode().
	 * The type is preserved, even for objects.
	 *
	 * @param mixed $cached The output to get the cache
	 * @return bool True if cache has been retrieved
	 */
	public function get(mixed &$cached): bool {
		try {
			if( !is_readable($this->path) ) {
				return false;
			}
			[$editTime, $data] = explodeList(static::$delim, file_get_contents($this->path), 2);
			$editTime = intval($editTime);
			if( isset($this->editTime) && $editTime !== $this->editTime ) {
				return false;
			}
			$cached = unserialize($data);
		} catch( Exception ) {
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
	public function set(mixed $data): bool {
		try {
			return file_put_contents($this->path, $this->editTime . static::$delim . serialize($data));
		} catch( Exception $e ) {
			throw new CacheException($e->getMessage(), $e->getCode(), $e);
		}
	}
	
	/**
	 * Clear the cache
	 * This method uses unlink() function.
	 */
	public function clear(): bool {
		return unlink($this->path);
	}
	
	public function getClass(): string {
		return $this->class;
	}
	
	public function getName(): string {
		return $this->name;
	}
	
	public function getPath(): string {
		return $this->path;
	}
	
	public function getEditTime(): ?int {
		return $this->editTime;
	}
	
	public function getInformation(): array {
		if( !$this->information ) {
			// https://www.php.net/manual/en/function.stat.php
			$this->information = stat($this->getPath());
		}
		
		return $this->information;
	}
	
	protected function setInformation(array $information): static {
		$this->information = $information;
		
		return $this;
	}
	
	function getSize(): int {
		$information = $this->getInformation();
		return $information['size'];
	}
	
	function getHits(): null {
		return null;
	}
	
	/**
	 * List all Filesystem Caches
	 *
	 * @return static[]
	 * @throws CacheException
	 */
	public static function list(): array {
		$caches = [];
		foreach( scanFolder(CACHE_PATH) as $class ) {
			$classPath = CACHE_PATH . '/' . $class;
			if( !is_dir($classPath) ) {
				continue;
			}
			foreach( scanFolder($classPath) as $cacheFile ) {
				$name = pathinfo($cacheFile, PATHINFO_FILENAME);
				$cache = new static($class, $name);
				$caches[$class . '.' . $name] = $cache;
			}
		}
		
		return $caches;
	}
	
	/**
	 * @throws CacheException
	 */
	public static function clearAll(): bool {
		foreach( static::list() as $cache ) {
			$cache->clear();
			try {
				rmdir(FileSystemCache::getClassPath($cache->getClass()));
			} catch( Exception ) {
				// Ignore
			}
		}
		return true;
	}
	
	/**
	 * Get the fle path of this cache
	 *
	 * @param string $class The class to use
	 * @param string $name The name to use
	 * @return string The path of this cache file.
	 */
	public static function getFilePath(string $class, string $name): string {
		return static::getClassPath($class) . '/' . strtr($name, '/', '_') . static::$ext;
	}
	
	/**
	 * Get the folder path for the cache
	 *
	 * @param string $class The class to use
	 * @return string The path of this cache folder in the global cache folder.
	 */
	public static function getClassPath(string $class): string {
		return CACHE_PATH . '/' . $class;
	}
}
