<?php
namespace ByTorsten\Neos\EntityLink\Controller\Service;

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Mvc\Controller\ActionController;
use TYPO3\Flow\Reflection\ObjectAccess;

/**
 * @Flow\Scope("singleton")
 */
class EditorController extends ActionController {

    /**
     * @var array
     */
    protected $viewFormatToObjectNameMap = array(
        'html' => 'TYPO3\Fluid\View\TemplateView',
        'json' => 'TYPO3\Neos\View\Service\AssetJsonView'
    );

    /**
     * @var array
     */
    protected $supportedMediaTypes = array(
        'text/html',
        'application/json'
    );

    /**
     * @param string $searchTerm
     * @param string $entityClassName
     * @param array $searchProperties
     * @param string $labelProperty
     */
    public function indexAction($searchTerm, $entityClassName, array $searchProperties, $labelProperty = NULL) {

        $query = $this->persistenceManager->createQueryForType($entityClassName);

        if ($labelProperty === NULL) {
            $labelProperty = $searchProperties[0];
        }

        $constrains = [];
        foreach($searchProperties as $searchProperty) {
            $constrains[] = $query->like($searchProperty, '%' . $searchTerm . '%');
        }

        $entities = $query->matching(
            $query->logicalOr($constrains)
        )->execute();

        $persistenceManager = $this->persistenceManager;
        $entities = array_map(function ($entity) use ($persistenceManager, $labelProperty) {
            return array (
                'identifier' => $persistenceManager->getIdentifierByObject($entity),
                'label' => ObjectAccess::getProperty($entity, $labelProperty)
            );
        }, $entities->toArray());

        $this->view->assign('entities', $entities);
    }

    /**
     * @param string $identifier
     * @param string $entityClassName
     * @param string $labelProperty
     * @return string
     */
    public function showAction($identifier, $entityClassName, $labelProperty) {

        $entity = $this->persistenceManager->getObjectByIdentifier($identifier, $entityClassName);

        if ($entity === NULL) {
            $this->throwStatus(404);
        }

        $this->view->assign('entity', array(
            'identifier' => $identifier,
            'label' => ObjectAccess::getProperty($entity, $labelProperty)
        ));
    }

}
