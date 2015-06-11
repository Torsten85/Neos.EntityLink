<?php
namespace ByTorsten\Neos\EntityLink\Service;

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Configuration\Exception\InvalidConfigurationException;
use TYPO3\Flow\Mvc\Controller\ControllerContext;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\Neos\Domain\Service\ContentContext;
use TYPO3\Eel\FlowQuery\FlowQuery;
use TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository;
use TYPO3\TYPO3CR\Domain\Factory\NodeFactory;

/**
 * @Flow\Scope("singleton")
 */
class LinkingService {

    /**
     * @var \TYPO3\Neos\Service\PluginService
     * @Flow\Inject
     */
    protected $pluginService;

    /**
     * @var \ByTorsten\Neos\EntityLink\Domain\Service\RepositoryService
     * @Flow\Inject
     */
    protected $repositoryService;

    /**
     * @var NodeDataRepository
     * @Flow\Inject
     */
    protected $nodeDataRepository;

    /**
     * @Flow\Inject
     * @var NodeFactory
     */
    protected $nodeFactory;

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
     * Pattern to match supported URIs.
     *
     * @var string
     */
    const PATTERN_SUPPORTED_URIS = '/entity:\/\/(?P<type>[a-z]+)\/((?P<controller>[^\/ ]*)\/)?((?P<action>[^\/ ]*)\/)?(?P<identifier>([a-f0-9]){8}-([a-f0-9]){4}-([a-f0-9]){4}-([a-f0-9]){4}-([a-f0-9]){12})/';

    /**
     * @param string $pluginName
     * @param string $controllerClassName
     * @param string $action
     * @param string $identifier
     * @param string $entityArgumentName
     *
     * @return string
     * @throws InvalidConfigurationException
     */
    protected function generateArguments($pluginName, $controllerClassName, $action, $identifier, $entityArgumentName) {

        preg_match('/
			^(?P<packageKey>[a-z]+\\\\[a-z]+)
			\\\\
			(
				Controller
			|
				(?P<subpackageKey>.+)\\\\Controller
			)
			\\\\
			(?P<controllerName>[a-z\\\\]+)Controller
			$/ix', strtolower(ltrim($controllerClassName, '\\')), $matches
        );

        if (count($matches) === 0) {
            throw new InvalidConfigurationException(sprintf('%s could not be parsed. Wrong format?', $controllerClassName), 1433931315);
        }

        $namespace = str_replace(':', '-', $pluginName);
        $namespace = str_replace('.', '_', $namespace);
        $namespace = strtolower($namespace);

        $arguments = array(
            '@package' => str_replace('\\', '.', $matches['packageKey']),
            '@controller' => $matches['controllerName'],
            '@action' => $action,
            $entityArgumentName => array('__identity' => $identifier)
        );

        if ($matches['subpackageKey']) {
            $arguments['@subpackage'] = $matches['subpackageKey'];
        }

        return array('--' . $namespace => $arguments);
    }

    /**
     * @param array $info
     * @param array $configuration
     * @return array
     * @throws InvalidConfigurationException
     */
    protected function generatePluginInfo(array $info, array $configuration) {

        if ($info['controller']) {
            $controllerClassName = str_replace(':', '\\', $info['controller']);

            if (!isset($configuration['plugin']['controllerActions'][$controllerClassName])) {
                throw new InvalidConfigurationException(sprintf('No EntityLink configuration for controller %s', $controllerClassName), 1433980165);
            }
        } else {
            $controllerClassName = key($configuration['plugin']['controllerActions']);
        }

        if ($info['action']) {
            $action = $info['action'];
            if ($configuration['plugin']['controllerActions'][$controllerClassName] !== $action &&
                !in_array($action, $configuration['plugin']['controllerActions'][$controllerClassName]) &&
                !isset($configuration['plugin']['controllerActions'][$controllerClassName][$action])) {

                throw new InvalidConfigurationException(sprintf('No EntityLink configuration for action %s of controller %s', $action, $controllerClassName), 1433980187);
            }
        } else {
            $action = current($configuration['plugin']['controllerActions']);

            if (is_array($action)) {
                $actionName = key($action);
                if (is_numeric($actionName)) {
                    $actionName = current($action);
                }
                $action = $actionName;
            }
        }


        if (isset($configuration['plugin']['controllerActions'][$controllerClassName][$action]['argumentName'])) {
            $argumentName = $configuration['plugin']['controllerActions'][$controllerClassName][$action]['argumentName'];
        } else {
            $argumentName = $info['type'];
        }

        return array($controllerClassName, $action, $argumentName);
    }

    /**
     * @param string $identifier
     * @param string $className
     * @return bool
     */
    protected function entityExists($identifier, $className) {
        return $this->persistenceManager->getObjectByIdentifier($identifier, $className) !== NULL;
    }


    /**
     * @param array $info
     * @param NodeInterface $contextNode
     * @param ControllerContext $controllerContext
     * @return string
     * @throws Exception\NoPluginNodeException
     * @throws \TYPO3\TYPO3CR\Exception\NodeConfigurationException
     */
    public function resolveEntityUri($info, NodeInterface $contextNode, ControllerContext $controllerContext) {

        $type = $info['type'];

        if (!isset($this->entityConfigurations[$type])) {
            return NULL;
        }

        $configuration = $this->entityConfigurations[$type];
        if (!$this->entityExists($info['identifier'], $configuration['className'])) {
            return NULL;
        }

        $plugin = $configuration['plugin']['name'];
        $identifier = $info['identifier'];

        try {
            list($controllerClassName, $action, $argumentName) = $this->generatePluginInfo($info, $configuration);
        } catch(InvalidConfigurationException $exception) {
            return NULL;
        }

        /** @var ContentContext $context */
        $context = $contextNode->getContext();

        if (isset($configuration['plugin']['identifier'])) {
            $nodeData = $this->nodeDataRepository->findByIdentifier($configuration['pluginNode']);
        } else {
            $siteNode = $context->getCurrentSiteNode();
            $nodeData = $this->nodeDataRepository->findByParentAndNodeTypeRecursively($siteNode->getPath(), $plugin, $context->getWorkspace());

            if (count($nodeData) === 0) {
                throw new Exception\NoPluginNodeException(sprintf('No node found for plugin %s', $plugin), 1433931217);
            }

            $nodeData = $nodeData[0];
        }

        $arguments = $this->generateArguments($plugin, $controllerClassName, $action, $identifier, $argumentName);

        $pluginNode = $this->nodeFactory->createFromNodeData($nodeData, $context);
        $targetNode = $this->pluginService->getPluginNodeByAction($pluginNode, $controllerClassName, $action);
        $q = new FlowQuery(array($targetNode));
        $pageNode = $q->closest('[instanceof TYPO3.Neos:Document]')->get(0);
        return $this->generateUriForNode($pageNode, $controllerContext, $arguments);
    }

    /**
     * @param NodeInterface $node
     * @param ControllerContext $controllerContext
     * @param array $arguments
     * @return string
     */
    protected function generateUriForNode(NodeInterface $node, ControllerContext $controllerContext, array $arguments = array()) {
        $uriBuilder = clone $controllerContext->getUriBuilder();
        $uriBuilder->setRequest($controllerContext->getRequest());
        return $uriBuilder
            ->setArguments($arguments)
            ->uriFor('show', array('node' => $node), 'Frontend\Node', 'TYPO3.Neos');
    }
}