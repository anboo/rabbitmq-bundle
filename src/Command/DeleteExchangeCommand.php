<?php


namespace Anboo\RabbitmqBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Anboo\RabbitmqBundle\AMQP\AMQPConnection;

/**
 * Class DeleteConsumer
 */
class DeleteExchangeCommand extends Command
{
    protected static $defaultName = 'anboo:exchange:delete';

    private $AMQPConnection;

    /**
     * DeleteExchangeCommand constructor.
     *
     * @param string|null    $name
     * @param AMQPConnection $AMQPConnection
     */
    public function __construct(string $name = null, AMQPConnection $AMQPConnection)
    {
        $this->AMQPConnection = $AMQPConnection;
        parent::__construct($name);
    }

    protected function configure()
    {
        $this
            ->setDescription('Delete exchange by name')
            ->addArgument('name', InputArgument::REQUIRED, 'Name exchange')
        ;
    }

    protected function execute (InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $exchangeName = $input->getArgument('name');
        $io->success('Run Delete exchange: ' . $exchangeName);
        if (empty($exchangeName)) {
            $io->warning('Enter exchane name. Exchange name can not by empty');
            die();
        }
        $exchangeNames = [
            'amq.directs',
            'amq.direct',
            'amq.headers',
            'amq.match',
            'amq.rabbitmq.trace',
            'amq.topic',
        ];
        if (true === in_array($exchangeName, $exchangeNames, true)) {
            $io->warning('Exchange name: "'.$exchangeName.'" is not allowed.');
            die();
        }

        $this->AMQPConnection->channel()->exchange_delete($exchangeName);
    }
}