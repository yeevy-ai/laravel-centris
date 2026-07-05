<?php

declare(strict_types=1);

namespace Yeevy\LaravelCentris\Events;

use Illuminate\Contracts\Events\Dispatcher;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Bridges the core package's PSR-14 events into Laravel's event
 * system, so ListingCreated / ListingUpdated / ListingRemoved can be
 * handled with ordinary Laravel listeners.
 */
final class LaravelEventDispatcher implements EventDispatcherInterface
{
    public function __construct(
        private readonly Dispatcher $events,
    ) {}

    public function dispatch(object $event): object
    {
        $this->events->dispatch($event);

        return $event;
    }
}
