```yaml
anboo_rabbitmq:
  rabbitmq:
    host: '127.0.0.1'
    port: 5672
    username: 'guest'
    password: 'guest'
```

```yaml
# config/services.yaml
services:
    App\Consumer\:
        resource: '../src/Consumer/*'
        public: true
        tags: ['anboo.queue_consumer']
```

```php
namespace App\AMQP\Consumer;

use Webslon\Bundle\ApiBundle\AMQP\Packet;
use Webslon\Bundle\ApiBundle\Annotation\Enqueue\Consume;
use Webslon\Bundle\ApiBundle\Consumer\Consumer;

class DemoConsumer extends Consumer
{
    /**
     * @param Packet $packet
     *
     * @Consume(
     *     queue="%kernel.environment%.my_service.calculator_multiple",
     *     exchangeBindKey="app.demo.calculator_multiple_222",
     *     exchangeName="app.demo.calculator_multiple"
     * )
     */
    public function demoAction(Packet $packet)
    {
        dump(
            $packet->getField('message'), // аналогично вызову $packet->getData()['message']
            $packet->getData(), //массив данных, ['message' => 'hello']
            $packet->getId(), //ID запроса
            $packet->getDate()->format('d.m.Y H:i:s') //Время, когда запрос был инициирован клиентом
        );
        $this->ack(); // Сообщаем RabbitMQ, что приняли сообщение
    }
}
```

```php
 /**
     * @Consume(
     *     queue="demo_queue",
     *     exchangeName="demo_exchange",
     *     exchangeBindKey="demo_exchange_binding_key",
     *     queueParameters=@QueueParameters(
     *         durable=true,
     *         passive=false,
     *         exclusive=false,
     *         autoDelete=false,
     *         arguments={
     *             "x-expires": 10000
     *         }
     *     ),
     *     exchangeParameters=@ExchangeParameters(
     *         durable=true,
     *         passive=false,
     *         autoDelete= false,
     *         internal=false,
     *         type="topic",
     *         arguments={
     *             "x-expires": 10000
     *         }
     *     ),
     *     consumerParameters=@ConsumerParameters(
     *         noAck=false,
     *         exclusive=false,
     *         noLocal=false,
     *         noWait=false,
     *         arguments={
     *             "x-expires": 10000
     *         }
     *     )
     * )
     */
```

`bin/console anboo:api:consumer --setup-broker --max-messages=5000`
