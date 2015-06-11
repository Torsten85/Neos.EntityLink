<?php
namespace ByTorsten\Neos\EntityLink\TypoScript;

use TYPO3\Flow\Annotations as Flow;
use TYPO3\TypoScript\TypoScriptObjects\AbstractTypoScriptObject;

/**
 *
 */
class ConvertEntityImplementation extends AbstractTypoScriptObject {

    /**
     * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
     * @Flow\Inject
     */
	protected $persistenceManager;

    /**
     * @return object
     */
	public function evaluate() {
		$identifier = $this->tsValue('value');
        $entityClassName = $this->tsValue('entityClassName');

        if ($identifier[0] === '[') {
            $persistenceManager = $this->persistenceManager;
            return array_map(function ($identifier) use($persistenceManager, $entityClassName) {
                return $persistenceManager->getObjectByIdentifier($identifier, $entityClassName);
            }, json_decode($identifier));
        }
        return $this->persistenceManager->getObjectByIdentifier($identifier, $entityClassName);
	}
}
