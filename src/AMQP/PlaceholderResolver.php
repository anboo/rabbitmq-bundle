<?php

namespace Anboo\RabbitmqBundle\AMQP;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class PlaceholderResolver
 */
class PlaceholderResolver
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @required
     * @param ContainerInterface $container
     * @return $this
     */
    public function setContainer(ContainerInterface $container): self
    {
        $this->container = $container;

        return $this;
    }


    public function handlePlaceholdersParametersFromContainer(ContainerInterface $container, ...$strings)
    {
        $result = [];

        foreach ($strings as $string) {
            preg_match('/\%(.*?)\%/', $string, $matches);
            if (isset($matches[1])) {
                $parameter = $matches[1];
                if (!$container->hasParameter($parameter)) {
                    throw new \RuntimeException(sprintf('Parameter %s for %s not found', $parameter, $string));
                }
                $result[] = str_replace($matches[0], $container->getParameter($parameter), $string);
            } else {
                $result[] = $string;
            }
        }

        return $result;
    }

    /**
     * @param string ...$string
     * @return string[]
     */
    public function handlePlaceholdersParameters(...$string)
    {
        return $this->handlePlaceholdersParametersFromContainer($this->container, ...$string);
    }
}