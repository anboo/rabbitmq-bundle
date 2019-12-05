<?php

namespace Anboo\RabbitmqBundle\Command;

use PhpAmqpLib\Message\AMQPMessage;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Anboo\RabbitmqBundle\AMQP\AMQPConnection;
use Anboo\RabbitmqBundle\AMQP\Packet;

/**
 * Class SendPacketCommand
 */
class SendPacketCommand extends Command
{
    protected static $defaultName = 'webslon:debug:queue:send';

    /** @var AMQPConnection */
    private $amqpConnection;

    /**
     * SendPacketCommand constructor.
     * @param AMQPConnection $amqpConnection
     */
    public function __construct(AMQPConnection $amqpConnection)
    {
        parent::__construct();

        $this->amqpConnection = $amqpConnection;
    }

    protected function configure()
    {
        $this
            ->addOption('exchange', 'x', InputOption::VALUE_REQUIRED, 'Exchange name')
            ->addOption('routing-key', 'r', InputOption::VALUE_REQUIRED, 'Routing key')
            ->addOption('rpc', 'rp', InputOption::VALUE_NONE, 'Is RPC request?')
            ->addArgument('message')
        ;
    }

    protected function execute (InputInterface $input, OutputInterface $output)
    {
        $requestId = Uuid::uuid4()->toString();

        $packet = new Packet(
            $requestId,
            new \DateTime(),
            [$input->getArgument('message')],
            $input->getOption('rpc')
        );

        $ch = $this->amqpConnection->channel();
        $ch->basic_publish(
            new AMQPMessage(json_encode($packet)),
            $input->getOption('exchange') ?? '',
            $input->getOption('routing-key')
        );

        $output->writeln('Successfully pushed to exchange '.$input->getOption('exchange'));
        $output->writeln('Your request id: '.$requestId);
    }
}