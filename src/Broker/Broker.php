<?php


namespace Vladitot\Nats\Broker;


use Nats\Message;
use Vladitot\Nats\Client\Client;
use Vladitot\Nats\Client\ConnectionOptions;

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
        $this->client = new Client($options);
    }

    /**
     * @param int $messagesCountToProcess
     * @throws \Exception
     */
    public function waitForNewMessages($messagesCountToProcess=0, $priorityChannel=null) {
        $this->client->wait($messagesCountToProcess, $priorityChannel);
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
     * Simply unsubscribe from a event given a subject.
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

    /**
     * Publish publishes the data argument to the given subject.
     *
     * @param string $subject Message topic.
     * @param string $payload Message data.
     * @param string $inbox Message inbox.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function publish($subject, $payload = null, $inbox = null){
        $payload = json_encode($payload);
        $this->client->publish($subject, $payload, $inbox);
    }

    /**
     * @param int $messagesCountToProcess
     * @param null $priorityChannel
     * @return Client|Message
     * @throws \Exception
     */
    public function getRawMessage($messagesCountToProcess=0, $priorityChannel=null) {
        $message = $this->client->wait($messagesCountToProcess, $priorityChannel, true);
        return $message;
    }

    /**
     * @throws \Exception
     */
    public function connect($debug = true) {
        $this->client->setDebug($debug);
        $this->client->connect();
    }


    /**
     * @throws \Exception
     */
    public function ping()
    {
        $this->client->ping();
    }


    /**
     * @param $queue
     * @return bool
     */
    public function isSubscribed($queue)
    {
        return isset($this->subscriptions[$queue]);
    }
}
