<?php
/**
 * Created by PhpStorm.
 * User: anboo
 * Date: 06.12.18
 * Time: 7:49
 */

namespace Anboo\ApiBundle;

use Anboo\ApiBundle\DependencyInjection\AnbooApiExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class AnbooApiBundle extends Bundle
{
    /**
     * @return mixed
     */
    public function getContainerExtension()
    {
        return new AnbooApiExtension();
    }
}