<?php
namespace ByTorsten\Neos\EntityLink\TypoScript;

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Neos\Domain\Exception;
use ByTorsten\Neos\EntityLink\Service\LinkingService;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TypoScript\TypoScriptObjects\AbstractTypoScriptObject;

/**
 *
 */
class ConvertUrisImplementation extends AbstractTypoScriptObject {

	/**
	 * @Flow\Inject
	 * @var LinkingService
	 */
	protected $linkingService;

	/**
	 * Convert URIs matching a supported scheme with generated URIs
	 *
	 * If the workspace of the current node context is not live, no replacement will be done unless forceConversion is
	 * set. This is needed to show the editable links with metadata in the content module.
	 *
	 * @return string
	 * @throws Exception
	 */
	public function evaluate() {
		$text = $this->tsValue('value');

		if ($text === '' || $text === NULL) {
			return '';
		}

		if (!is_string($text)) {
			throw new Exception(sprintf('Only strings can be processed by this TypoScript object, given: "%s".', gettype($text)), 1382624080);
		}

		$node = $this->tsValue('node');

		if (!$node instanceof NodeInterface) {
			throw new Exception(sprintf('The current node must be an instance of NodeInterface, given: "%s".', gettype($text)), 1382624087);
		}

		if ($node->getContext()->getWorkspace()->getName() !== 'live' && !($this->tsValue('forceConversion'))) {
			return $text;
		}

		$unresolvedUris = array();
		$linkingService = $this->linkingService;
		$controllerContext = $this->tsRuntime->getControllerContext();

		$processedContent = preg_replace_callback(LinkingService::PATTERN_SUPPORTED_URIS, function(array $matches) use ($node, $linkingService, $controllerContext, &$unresolvedUris) {
            $resolvedUri = $linkingService->resolveEntityUri($matches, $node, $controllerContext);

			if ($resolvedUri === NULL) {
				$unresolvedUris[] = $matches[0];
				return $matches[0];
			}

			return $resolvedUri;
		}, $text);

		if ($unresolvedUris !== array()) {
			$processedContent = preg_replace('/<a[^>]* href="entity:\/\/[^"]+"[^>]*>(.*?)<\/a>/', '$1', $processedContent);
            $processedContent = preg_replace(LinkingService::PATTERN_SUPPORTED_URIS, '', $processedContent);
		}

		return $processedContent;
	}
}
