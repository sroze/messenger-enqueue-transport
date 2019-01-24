<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Enqueue\MessengerAdapter\Tests\Bundle\DependencyInjection\Compiler;

use Enqueue\MessengerAdapter\Bundle\DependencyInjection\Compiler\AssertRequirementsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class AssertRequirementsPassTest
{
    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage The default Messenger serializer cannot be enabled as the Serializer support is not available. Try enabling it or running "composer require symfony/serializer-pack".
     */
    public function testWithoutDefaultSymfonySerializer()
    {
        $container = new ContainerBuilder();
        $container->addCompilerPass(new AssertRequirementsPass());

        $container->compile();
    }
}
