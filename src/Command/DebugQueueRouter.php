<?php

namespace Anboo\ApiBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Anboo\ApiBundle\AMQP\Router\RouterCollection;

class DebugQueueRouter extends Command
{
    protected static $defaultName = 'webslon:debug:queue:router';

    /**
     * @var RouterCollection
     */
    private $routeCollection;


    /**
     * ConsumerCommand constructor.
     *
     * @param RouterCollection $routeCollection
     */
    public function __construct (RouterCollection $routeCollection)
    {
        parent::__construct();

        $this->routeCollection = $routeCollection;
    }

    protected function execute (InputInterface $input, OutputInterface $output)
    {
        $table = new Table($output);
        $table->setHeaders(['queue name', 'consumer', 'action', 'exchange', 'exchange bind key']);

        foreach ($this->routeCollection->all() as $route) {
            $table->addRow([
                $route->getQueue(),
                $route->getConsumer(),
                $route->getAction(),
                $route->getExchangeName(),
                $route->getExchangeBindKey(),
            ]);
        }

        $table->render();
    }
}