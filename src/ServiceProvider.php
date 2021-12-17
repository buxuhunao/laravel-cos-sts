<?php

namespace Buxuhunao\CosSts;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    protected bool $defer = true;

    public function register()
    {
        $this->app->singleton(StsClient::class, fn() => new StsClient(\config('filesystems.disks.cos')));
    }

    public function provides()
    {
        return [StsClient::class];
    }
}
