<?php


namespace Vladitot\Nats\Queue;


use Illuminate\Queue\Queue;
use Vladitot\Nats\Broker\Broker;

class NatsQueue extends Queue implements \Illuminate\Contracts\Queue\Queue
{
    /** @var Broker $broker */
    protected $broker;

    /**
     * Get the size of the queue.
     *
     * @param string|null $queue
     * @return int
     * @throws \Exception
     */
    public function size($queue = null)
    {
        throw new \Exception('Size for Nats is not supported yet. We should use json statistics endpoint in future, to collect this info.');
    }

    /**
     * Push a new job onto the queue.
     *
     * @param string|object $job
     * @param mixed $data
     * @param string|null $queue
     * @return mixed
     * @throws \Exception
     */
    public function push($job, $data = '', $queue = null)
    {
        if (is_object($job)) {
            $job = serialize($job);
        }
        $payload = [
            'job'=>$job,
            'data'=>$data
        ];
        $this->broker->publish($queue, $payload);
        return true;
    }

    /**
     * Push a raw payload onto the queue.
     *
     * @param string $payload
     * @param string|null $queue
     * @param array $options
     * @return mixed
     * @throws \Exception
     */
    public function pushRaw($payload, $queue = null, array $options = [])
    {
        $payload = [
            'payload'=>$payload,
            'options'=>$options
        ];
        $this->broker->publish($queue, $payload);
        return true;
    }

    /**
     * Push a new job onto the queue after a delay.
     *
     * @param \DateTimeInterface|\DateInterval|int $delay
     * @param string|object $job
     * @param mixed $data
     * @param string|null $queue
     * @return mixed
     * @throws \Exception
     */
    public function later($delay, $job, $data = '', $queue = null)
    {
        throw new \Exception('Pushing with delays is not supported yet. Really sorry, but no way now.');
    }

    /**
     * Pop the next job off of the queue.
     *
     * @param string $queue
     * @return \Illuminate\Contracts\Queue\Job|null
     * @throws \Exception
     */
    public function pop($queue = null)
    {
        return $this->broker->getRawMessage(1, $queue);
    }

    /**
     * @param Broker $broker
     */
    public function setBroker(Broker $broker): void
    {
        $this->broker = $broker;
    }

    /**
     * @return Broker
     */
    public function getBroker(): Broker
    {
        return $this->broker;
    }
}
