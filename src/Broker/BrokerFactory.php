<?php


namespace Vladitot\Nats\Broker;


use Vladitot\Nats\Client\ConnectionOptions;

class BrokerFactory
{
    /**
     * @param string $host
     * @param string $user
     * @param string $pass
     * @param string $token
     * @return Broker
     * @throws \Exception
     */
    public static function make($host = 'localhost', $user = 'user', $pass = 'pass', $token = '')
    {
        $options = new ConnectionOptions([
            'user' => $user,
            'pass' => $pass,
            'host' => $host,
            'token' => $token
        ]);
        $broker = new Broker($options);
        $broker->connect();
        return $broker;
    }
}
