<?php
namespace ByTorsten\Neos\EntityLink\Controller\Service;

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Mvc\Controller\ActionController;
use ByTorsten\Neos\EntityLink\Domain\Service\RepositoryService;
use TYPO3\Flow\Reflection\ObjectAccess;

/**
 * @Flow\Scope("singleton")
 */
class InlineController extends ActionController {

    /**
     * @var array
     */
    protected $viewFormatToObjectNameMap = array(
        'html' => 'TYPO3\Fluid\View\TemplateView',
        'json' => 'TYPO3\Neos\View\Service\AssetJsonView'
    );

    /**
     * @Flow\Inject
     * @var RepositoryService
     */
    protected $repositoryService;

    /**
     * @Flow\InjectConfiguration(package="ByTorsten.Neos.EntityLink", path="entities")
     * @var array
     */
    protected $entityConfigurations;

    /**
     * @var array
     */
    protected $supportedMediaTypes = array(
        'text/html',
        'application/json'
    );

    /**
     * @return array
     */
    protected function getPluginEntityInfo() {
        $info = array();
        foreach($this->entityConfigurations as $entityName => $entityConfiguration) {

            foreach($entityConfiguration['plugin']['controllerActions'] as $controllerName => $controllerActions) {
                foreach($controllerActions as $controllerActionKey => $controllerAction) {
                    if (is_numeric($controllerActionKey)) {
                        $info[$entityName]['controllerActions'][$controllerName][$controllerAction] = array('label' => $controllerAction);
                    } else if (is_string($controllerAction)) {
                        $info[$entityName]['controllerActions'][$controllerName][$controllerActionKey] = array('label' => $controllerAction);
                    } else {
                        $info[$entityName]['controllerActions'][$controllerName][$controllerActionKey] = $controllerAction;
                    }
                }
            }

            if (count($info[$entityName]['controllerActions']) === 1 && count(current($info[$entityName]['controllerActions'])) === 1) {
                unset($info[$entityName]['controllerActions']);
            } else if (isset($entityConfiguration['translationPackage'])) {
                $info[$entityName]['translationPackage'] = $entityConfiguration['translationPackage'];
            }
        }

        return $info;
    }

    /**
     * @param string $searchTerm
     * @param bool $includeControllerActions
     * @return string
     */
    public function indexAction($searchTerm = '', $includeControllerActions = FALSE) {

        $entities = $this->repositoryService->findBySearchTerm($searchTerm);

        $this->view->assign('entities', $entities);

        if ($includeControllerActions) {
            $this->view->assign('entityInfo', $this->getPluginEntityInfo());
        }
    }

    /**
     * @param string $type
     * @param string $identifier
     * @param string $controller
     * @param string $action
     * @return string
     */
    public function showAction($type, $identifier, $controller = NULL, $action = NULL) {

        $entity = $this->repositoryService->findByIdentifier($type, $identifier);

        if ($entity === NULL) {
            $this->throwStatus(404);
        }

        $this->view->assign('entity', $entity);
    }

}
