<?php
namespace ByTorsten\Neos\EntityLink\Domain\Service;

use Doctrine\ORM\Event\LifecycleEventArgs;
use TYPO3\TypoScript\Core\Cache\ContentCache;
use TYPO3\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
class EventListener {

    /**
     * @var ContentCache
     * @Flow\Inject
     */
    protected $contentCache;

    /**
     * @var \TYPO3\Flow\Mvc\Routing\ObjectPathMappingRepository
     * @Flow\Inject
     */
    protected $objectPathMappingRepository;
    /**
     * @var \TYPO3\Flow\Mvc\Routing\RouterCachingService
     * @Flow\Inject
     */
    protected $routeCachingService;

    /**
     * @var array
     * @Flow\InjectConfiguration(package="ByTorsten.Neos.EntityLink", path="entities")
     */
    protected $entityConfigurations;

    /**
     * @var bool
     * @Flow\InjectConfiguration(package="ByTorsten.Neos.EntityLink", path="updateObjectPathMapping")
     */
    protected $shouldUpdateObjectPathMapping;

    /**
     * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
     * @Flow\Inject
     */
    protected $persistenceManager;

    /**
     * @param LifecycleEventArgs $eventArgs
     * @return void
     */
    public function postPersist(LifecycleEventArgs $eventArgs) {
        $this->flushContentCacheIfNecessary($eventArgs->getEntity());
    }

    /**
     * @param LifecycleEventArgs $eventArgs
     * @return void
     */
    public function postRemove(LifecycleEventArgs $eventArgs) {
        $this->flushContentCacheIfNecessary($eventArgs->getEntity());
    }

    /**
     * @param LifecycleEventArgs $eventArgs
     * @return void
     */
    public function postUpdate(LifecycleEventArgs $eventArgs) {
        $this->flushContentCacheIfNecessary($eventArgs->getEntity());
    }

    /**
     * @param object $entity
     */
    protected function flushContentCacheIfNecessary($entity) {

        $shouldFlush = FALSE;
        foreach($this->entityConfigurations as $entityName => $entityConfiguration) {
            if ($entity instanceof $entityConfiguration['className']) {
                $shouldFlush = TRUE;
                break;
            }
        }

        if ($shouldFlush) {
            $identifier = $this->persistenceManager->getIdentifierByObject($entity);
            $this->contentCache->flushByTag('NodeType_TYPO3.Neos:Plugin');
            $this->routeCachingService->flushCachesByTag($identifier);

            if ($this->shouldUpdateObjectPathMapping) {
                $query = $this->objectPathMappingRepository->createQuery();

                $entries = $query->matching(
                    $query->logicalAnd(
                        $query->equals('identifier', $identifier),
                        $query->equals('objectType', get_class($entity))
                    )
                )->execute()->toArray();

                foreach ($entries as $entry) {
                    $this->objectPathMappingRepository->remove($entry);
                }
                $this->persistenceManager->persistAll();
            }
        }
    }
}