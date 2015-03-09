<?php

namespace SimplePHPAdapters;

interface ProxiesProviderInterface
{
    public function provideProxy();

    public function releaseProxy();

    public function nextProxy();
}