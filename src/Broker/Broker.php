<?php


namespace Vladitot\Nats\Broker;


use Vladitot\Nats\Client;
use Vladitot\Nats\ConnectionOptions;

class Broker
{

    protected $subscriptions;

    /**
     * @var Client
     */
    protected $client;

    /**
     * Broker constructor.
     * @param ConnectionOptions $options
     * @throws \Exception
     */
    public function __construct(ConnectionOptions $options)
    {
        new ConnectionOptions(
            [
                'user' => getenv('USER'),
                'pass' => getenv('PASS'),
                'host' => getenv('HOST'),
                'token' => getenv('TOKEN')
            ]
        );

        $this->client = new Client($options);
    }

    public function waitForNewMessages($messagesCountToProcess=0) {
        $this->client->wait($messagesCountToProcess);
    }

    /**
     * Subscribes to an specific event given a subject.
     *
     * @param string $subject Message topic.
     * @param \Closure $callback Closure to be executed as callback.
     *
     * @throws \Exception
     */
    public function subscribe($subject, \Closure $callback)
    {
        $sid = $this->client->subscribe($subject, $callback);
        $this->subscriptions[$subject] = $sid;
    }

    /**
     * @param $subject
     * @param \Closure $callback
     * @throws \Exception
     */
    public function queueSubscribe($subject, \Closure $callback){
        $sid = $this->client->queueSubscribe($subject, $subject, $callback);
        $this->subscriptions[$subject] = $sid;
    }

    /**
     * Unsubscribe from a event given a subject.
     *
     * @param $subject
     * @param integer $quantity Quantity of messages.
     *
     * @return void
     * @throws \Exception
     */
    public function unsubscribe($subject, $quantity = null) {
        $this->client->unsubscribe($this->subscriptions[$subject], $quantity);
        unset($this->subscriptions[$subject]);
    }

    /**
     * Request does a request and executes a callback with the response.
     *
     * @param string $subject Message topic.
     * @param string $payload Message data.
     * @param \Closure $callback Closure to be executed as callback.
     *
     * @return void
     * @throws \Exception
     */
    public function request($subject, $payload, \Closure $callback)
    {
        $this->client->request($subject, $payload, $callback);
    }
}
