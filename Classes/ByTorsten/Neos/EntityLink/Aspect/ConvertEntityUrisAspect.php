<?php
namespace ByTorsten\Neos\EntityLink\Aspect;

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Aop\JoinPointInterface;

/**
 * @Flow\Aspect
 */
class ConvertEntityUrisAspect {

    /**
     * @param JoinPointInterface $joinPoint
     * @return string
     * @Flow\Around("method(TYPO3\Neos\Domain\Service\TypoScriptService->generateTypoScriptForNodeType())")
     */
    public function addUriProcessor (JoinPointInterface $joinPoint) {
        $output = $joinPoint->getAdviceChain()->proceed($joinPoint);

        if ($output === '') {
            return $output;
        }

        /** @var \TYPO3\TYPO3CR\Domain\Model\NodeType $nodeType */
        $nodeType = $joinPoint->getMethodArgument('nodeType');
        $output = substr($output, 0, -2);

        foreach($nodeType->getProperties() as $propertyName => $propertyConfiguration) {
            if (isset($propertyName[0]) && $propertyName[0] !== '_') {
                if (isset($propertyConfiguration['type']) && isset($propertyConfiguration['ui']['inlineEditable']) && $propertyConfiguration['type'] === 'string' && $propertyConfiguration['ui']['inlineEditable'] === TRUE) {

                    $output .= "\t" . $propertyName . '.@process.convertEntityUris = ByTorsten.Neos.EntityLink:ConvertUris' . chr(10);
                    $output .= "\t" . $propertyName . ".@process.convertEntityUris.@position = 'start'" . chr(10);
                }
            }
        }

        $output .= '}' . chr(10);
        return $output;
    }

}