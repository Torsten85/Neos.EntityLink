<?php
namespace ByTorsten\Neos\EntityLink\Domain\Service;

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Reflection\ObjectAccess;

/**
 * @Flow\Scope("singleton")
 */
class RepositoryService {

    /**
     * @Flow\InjectConfiguration(package="ByTorsten.Neos.EntityLink", path="entities")
     * @var array
     */
    protected $entityConfigurations;

    /**
     * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
     * @Flow\Inject
     */
    protected $persistenceManager;

    /**
     * @var \TYPO3\Flow\Object\ObjectManagerInterface
     * @Flow\Inject
     */
    protected $objectManager;

    /**
     * @param string $searchTerm
     * @param string $name
     * @param array $configuration
     * @return array
     */
    protected function findEntities($searchTerm, $name, array $configuration) {

        $searchProperties = $configuration['searchProperties'];
        $labelProperty = isset($configuration['labelProperty']) ? $configuration['labelProperty'] : $configuration['searchProperties'][0];

        $query = $this->persistenceManager->createQueryForType($configuration['className']);
        $constrains = array();
        foreach($searchProperties as $searchProperty) {
            $constrains[] = $query->like($searchProperty, '%' . $searchTerm . '%');
        }

        $result = $query->matching($query->logicalOr($constrains))->execute()->toArray();
        $persistenceManager = $this->persistenceManager;

        $icon = isset($configuration['icon']) ? $configuration['icon'] : NULL;
        return array_map(function ($entity) use ($name, $labelProperty, $persistenceManager, $icon) {
            return array(
                'icon' => $icon,
                'type' => $name,
                'label' => ObjectAccess::getProperty($entity, $labelProperty),
                'identifier' => $persistenceManager->getIdentifierByObject($entity)
            );
        }, $result);

    }

    /**
     * @param string $identifier
     * @param string $name
     * @param array $configuration
     * @return array
     * @throws Exception\InvalidConfigurationException
     * @throws \TYPO3\Flow\Reflection\Exception\PropertyNotAccessibleException
     */
    protected function findEntity($identifier, $name, array $configuration) {

        $entity = $this->persistenceManager->getObjectByIdentifier($identifier, $configuration['className']);

        if ($entity) {
            $labelProperty = isset($configuration['labelProperty']) ? $configuration['labelProperty'] : $configuration['searchProperties'][0];

            return array(
                'type' => $name,
                'label' => ObjectAccess::getProperty($entity, $labelProperty),
                'identifier' => $identifier
            );
        }

        return array();
    }

    /**
     * @param string $searchTerm
     * @return array
     */
    public function findBySearchTerm($searchTerm = '') {
        $entityData = array();

        foreach($this->entityConfigurations as $entityName => $entityConfiguration) {
            $entityData = array_merge($entityData, $this->findEntities($searchTerm, $entityName, $entityConfiguration));
        }

        return $entityData;
    }

    /**
     * @param string $type
     * @param string $identifier
     * @return array
     */
    public function findByIdentifier($type, $identifier) {

        if (!isset($this->entityConfigurations[$type])) {
            return NULL;
        }

        return $this->findEntity($identifier, $type, $this->entityConfigurations[$type]);
    }
}