<?php

namespace Vladitot\Nats;

use Bkwld\Cloner\ServiceProvider;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Queue\QueueManager;
use Vladitot\Nats\Queue\NatsConnector;

class NatsServiceProvider extends ServiceProvider implements DeferrableProvider
{

    public function registerNatsConnector(QueueManager $manager)
    {
        $manager->addConnector('nats', function () {
            return new NatsConnector();
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerNatsConnector(app('queue'));
    }
}