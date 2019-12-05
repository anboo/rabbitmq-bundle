<?php


namespace Anboo\ApiBundle\Command;

use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Anboo\ApiBundle\AMQP\AMQPConnection;
use Anboo\ApiBundle\AMQP\Producer;

/**
 * Class MoveMessageToQueueCommand
 */
class MoveMessageToQueueCommand extends Command
{
    protected static $defaultName = 'webslon:amqp-msg:move-to-queue';
    /**
     * @var AMQPConnection
     */
    private $amqpConnection;
    /**
     * @var Producer
     */
    private $producer;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * MoveMessageToQueueCommand constructor.
     *
     * @param string|null     $name
     * @param AMQPConnection  $connection
     * @param Producer        $producer
     * @param LoggerInterface $logger
     */
    public function __construct(string $name = null, AMQPConnection $amqpConnection, Producer $producer, LoggerInterface $logger)
    {
        $this->amqpConnection = $amqpConnection;
        $this->producer = $producer;
        $this->logger = $logger;

        parent::__construct($name);
    }

    protected function configure()
    {
        $this
            ->setDescription('Move messsage to queue')
            ->addArgument('type', InputArgument::REQUIRED,'Type exception')
            ->addArgument('from', InputArgument::REQUIRED, 'Queue from')
            ->addArgument('to', InputArgument::REQUIRED, 'Queue to')
        ;

        parent::configure();
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|void|null
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $io->success('# Run '.__CLASS__.', args: '. implode(', ', $input->getArguments()));

        static $cnt = 0;
        $results = [];
        while (true){
            $message = $this->amqpConnection->channel()->basic_consume($input->getArgument('from'), '',false,true, false,false, function() {

            });
            if($message){
                $dataMessage = json_decode($message->getBody(), true);
                if(isset($dataMessage['errors'])) {
                    foreach ($dataMessage['errors'] as $error) {
                        $results[$error['code']][] = $error['message'];
                    }

                    file_put_contents('/var/www/LRA/services/lm-orders/var/log_amqp_message.json', json_encode($results));
                }
            }
        }

        echo var_export($results, true);

        foreach ($results as $result) {
            echo var_export($result, true);

            sleep(15);
        }

        $io->success('# End '.__CLASS__.' process');
    }
}