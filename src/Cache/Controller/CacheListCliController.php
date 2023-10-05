<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Orpheus\Cache\Controller;

use InvalidArgumentException;
use Orpheus\Cache\ApcCache;
use Orpheus\Cache\Cache;
use Orpheus\Cache\Service\CacheService;
use Orpheus\InputController\CliController\CliController;
use Orpheus\InputController\CliController\CliRequest;
use Orpheus\InputController\CliController\CliResponse;

class CacheListCliController extends CliController {
	
	/**
	 * @param CliRequest $request The input CLI request
	 */
	public function run($request): CliResponse {
		$cache = $request->getOption('cache');
		$showUnknown = !!$request->getOption('show-unknown');
		$help = $request->getOption('help', 'h');
		
		if( $help ) {
			return new CliResponse(0, $this->getHelp());
		}
		
		$output = $cache ? $this->getCacheOutput(strtolower($cache), $showUnknown) : $this->getCacheListOutput();
		
		return new CliResponse(0, $output);
	}
	
	protected function getCacheListOutput(): string {
		// Working with APC is not a full success, it's running in the fpm process but do we care ?!
		$caches = CacheService::get()->getAllCaches();
		$output = <<<EOF

All caches supported by the current environment:
EOF;
		foreach( $caches as $cache => $cacheClass ) {
			/** @var Cache $cacheClass */
			$items = count($cacheClass::list());
			$output .= <<<EOF

 * $cache with $items items using class "$cacheClass"
EOF;
		}
		$output .= <<<EOF

To detail one cache items, use the option "--cache".

EOF;
		
		return $output;
	}
	
	protected function getCacheOutput(string $key, bool $showUnknown): string {
		$caches = CacheService::get()->getAllCaches();
		$cacheClass = $caches[$key] ?? null;
		if( !$cacheClass ) {
			throw new InvalidArgumentException(sprintf('Non-supported cache "%s" provided', $key));
		}
		/** @var Cache $cacheClass */
		$output = <<<EOF

For cache "$key", here is all stored items:
EOF;
		foreach( $cacheClass::list() as $cache ) {
			/** @var Cache $cache */
			$isUnknownCache = !$cache->getClass();
			if( !$showUnknown && $isUnknownCache ) {
				continue;
			}
			// Hits count is not available on FileSystemCache and APCu (using CLI)
			$output .= sprintf(<<<'END'

 * %sCache "%s" with name "%s" has a size of %d.
END, $isUnknownCache ? '[*] ' : '',$cache->getClass(), $cache->getName(), $cache->getSize());
		}
		if($showUnknown) {
			$output .= <<<EOF

Unknown cache items are prepended with [*].

EOF;
		} else {
			$output .= <<<EOF

To show all cache items, event from non-orpheus applications, use the option "--show-unknown".

EOF;
		}
		return $output;
	}
	
	protected function getHelp(): string {
		return <<<END
php app/console/run.php cache-list
List all available caches. List all entries in cache if cache option is provided.
Options:
 - --cache=[fs|apc] : List entries for this cache
 - -v|vv|vvv : Verbose mode, More v, more verbose.
 - --dry-run : Dry run, do not apply any change.
 - -h|--help : Show this help.
END
			;
	}
	
	
}
