<?php

namespace Anboo\ApiBundle\Serializer\Normalizer;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorResolverInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

/**
 * Class IgnoredAttributesNormalizer
 */
class IgnoredAttributesNormalizer extends ObjectNormalizer
{
    protected $ignoredAttributes = [

    ];
}