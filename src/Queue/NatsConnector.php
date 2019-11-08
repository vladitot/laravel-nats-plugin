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
     */
    public function connect(array $config)
    {
        return BrokerFactory::make($config['host'], $config['user'], $config['password'], $config['token']);
    }
}
