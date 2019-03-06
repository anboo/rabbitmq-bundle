<?php

namespace Anboo\ApiBundle\Controller;

use Anboo\ApiBundle\Util\PropertyAccessor;
use EXSyst\Component\Swagger\Collections\Paths;
use EXSyst\Component\Swagger\Operation;
use EXSyst\Component\Swagger\Parameter;
use EXSyst\Component\Swagger\Swagger;
use Swagger\Annotations\Path;

/**
 * Trait DocumentationControllerTrait
 */
trait DocumentationControllerTrait
{
    /**
     * @param string $baseURL
     * @param Swagger $api
     * @return Swagger
     */
    public function preparePaths($baseURL, $api)
    {
        if ($baseURL !== '/') {
            $paths = [];

            /** @var Path $path */
            foreach ($api->getPaths() as $url => $path) {
                $newUrl = str_replace($baseURL, '', $url);
                
                if (empty($newUrl)) {
                    continue;
                }
                
                if ($newUrl[0] != '/') {
                    $newUrl = '/'.$newUrl;
                }
                $paths[$newUrl] = $path;
            }

            $pathsIterator = new Paths();
            PropertyAccessor::setValueForce($pathsIterator, 'paths', $paths);
            PropertyAccessor::setValueForce($api, 'paths', $pathsIterator);
        }

//        /** @var Path $path */
//        foreach ($api->getPaths() as $path) {
//            /** @var Operation $operation */
//            if ($operations = $path->getOperations()) {
//                foreach ($operations as $operation) {
//                    if ($operation->getParameters()) {
//                        /** @var Parameter $parameter */
//                        foreach ($operation->getParameters() as $parameter) {
//                            if ($parameter->getSchema()) {
//                                $parameter->getSchema()->setRef(null);
//                            }
//                        }
//                    }
//                }
//            }
//        }

        return $api;
    }
}
