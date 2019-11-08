<?php


namespace Vladitot\Nats\Queue;


use Illuminate\Contracts\Queue\Job as JobContract;
use Illuminate\Queue\Jobs\Job;

class NatsJob extends Job implements JobContract
{

    /**
     * Get the job identifier.
     *
     * @return string
     */
    public function getJobId()
    {
        // TODO: Implement getJobId() method.
    }

    /**
     * Get the raw body of the job.
     *
     * @return string
     */
    public function getRawBody()
    {
        // TODO: Implement getRawBody() method.
    }

    /**
     * Get the number of times the job has been attempted.
     *
     * @return int
     */
    public function attempts()
    {
        // TODO: Implement attempts() method.
    }
}
