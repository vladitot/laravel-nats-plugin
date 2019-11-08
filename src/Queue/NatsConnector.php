<?php


namespace Vladitot\Nats\Queue;


use Illuminate\Queue\Connectors\ConnectorInterface;
use Vladitot\Nats\Broker\BrokerFactory;

class NatsConnector implements ConnectorInterface
{

    /**
     * Establish a queue connection.
     *
     * @param array $config
     * @return \Illuminate\Contracts\Queue\Queue
     * @throws \Exception
     */
    public function connect(array $config)
    {
        $connectionConfig = config('database.nats.'.$config['connection']);
        $queue = new NatsQueue();
        $queue->setBroker(
            BrokerFactory::make(
                $connectionConfig['host'],
                $connectionConfig['user'],
                $connectionConfig['password'],
                $connectionConfig['token']
            )
        );
        $queue->getBroker()->queueSubscribe($config['queue'], function($message) {});
        return $queue;
    }
}
