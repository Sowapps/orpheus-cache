<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Orpheus\Cache\Controller;

use Orpheus\Cache\ApcCache;
use Orpheus\Cache\Cache;
use Orpheus\Cache\FileSystemCache;
use Orpheus\Cache\Service\CacheService;
use Orpheus\Exception\UserException;
use Orpheus\Initernationalization\TranslationService;
use Orpheus\InputController\CliController\CliController;
use Orpheus\InputController\CliController\CliRequest;
use Orpheus\InputController\CliController\CliResponse;

class CacheClearCliController extends CliController {
	
	/**
	 * @param CliRequest $request The input CLI request
	 */
	public function run($request): CliResponse {
		$help = $request->getOption('help', 'h');
		
		if( $help ) {
			return new CliResponse(0, $this->getHelp());
		}
		
		$caches = CacheService::get()->getAllCaches();
		$count = 0;
		foreach($caches as $cache => $cacheClass) {
			/** @var Cache $cacheClass */
			if( $cacheClass::clearAll() && $request->isVerbose() ) {
				$this->printLine(sprintf('Cleared all cache of "%s".', strtoupper($cache)));
			}
			$count++;
		}
		
		return new CliResponse(0, sprintf('All caches (%d) were erased.', $count));
	}
	
	protected function getHelp(): string {
		return <<<END
php app/console/run.php cache-clear
Clear cache from all sources
Options:
 - -v|vv|vvv : Verbose mode, More v, more verbose.
 - --dry-run : Dry run, do not apply any change.
 - -h|--help : Show this help.
END
			;
	}
	
	
}
