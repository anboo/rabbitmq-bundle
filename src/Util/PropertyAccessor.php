<?php

namespace Anboo\ApiBundle\Util;

/**
 * Class PropertyAccessor
 */
class PropertyAccessor
{
    public static function setValueForce($obj, $propertyName, $value)
    {
        $reflectionClass = new \ReflectionClass($obj);
        $reflectionProperty = $reflectionClass->getProperty($propertyName);

        if (!$reflectionProperty->isPublic()) {
            $reflectionProperty->setAccessible(true);
        }

        $reflectionProperty->setValue($obj, $value);
    }

    public static function getValueForce($obj, $propertyName)
    {
        $reflectionClass = new \ReflectionClass($obj);
        $reflectionProperty = $reflectionClass->getProperty($propertyName);

        if (!$reflectionProperty->isPublic()) {
            $reflectionProperty->setAccessible(true);
        }

        return $reflectionProperty->getValue($obj);
    }
}
