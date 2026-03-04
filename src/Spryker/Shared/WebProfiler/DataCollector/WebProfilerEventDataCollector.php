<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Shared\WebProfiler\DataCollector;

use Symfony\Component\EventDispatcher\Debug\TraceableEventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\HttpKernel\DataCollector\LateDataCollectorInterface;
use Symfony\Component\VarDumper\Caster\ClassStub;
use Symfony\Component\VarDumper\Cloner\Data;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Service\ResetInterface;
use Throwable;

class WebProfilerEventDataCollector extends DataCollector implements LateDataCollectorInterface
{
    protected const string DEFAULT_DISPATCHER = 'event_dispatcher';

    /**
     * @var iterable<\Symfony\Contracts\EventDispatcher\EventDispatcherInterface>
     */
    protected iterable $dispatchers;

    protected ?Request $currentRequest = null;

    protected string $defaultDispatcher;

    /**
     * @param iterable<\Symfony\Contracts\EventDispatcher\EventDispatcherInterface>|\Symfony\Contracts\EventDispatcher\EventDispatcherInterface|null $dispatchers
     */
    public function __construct(
        iterable|EventDispatcherInterface|null $dispatchers = null,
        protected ?RequestStack $requestStack = null,
        string $defaultDispatcher = self::DEFAULT_DISPATCHER,
    ) {
        if ($dispatchers instanceof EventDispatcherInterface) {
            $dispatchers = [$defaultDispatcher => $dispatchers];
        }

        $this->dispatchers = $dispatchers ?? [];
        $this->defaultDispatcher = $defaultDispatcher;
    }

    /**
     * Persists `$defaultDispatcher` alongside `$data` so the collector can correctly
     * key into `$this->data` after deserialization (base `DataCollector::__sleep()` only keeps `$data`).
     *
     * @return array<string>
     */
    public function __sleep(): array
    {
        return ['data', 'defaultDispatcher'];
    }

    public function collect(Request $request, Response $response, ?Throwable $exception = null): void
    {
        $this->currentRequest = $this->requestStack && $this->requestStack->getMainRequest() !== $request ? $request : null;

        $this->data = [];
    }

    public function reset(): void
    {
        parent::reset();

        foreach ($this->dispatchers as $dispatcher) {
            if ($dispatcher instanceof ResetInterface) {
                $dispatcher->reset();
            }
        }
    }

    public function lateCollect(): void
    {
        foreach ($this->dispatchers as $name => $dispatcher) {
            if (!$dispatcher instanceof TraceableEventDispatcher) {
                continue;
            }

            $this->setCalledListeners($dispatcher->getCalledListeners($this->currentRequest), $name);
            $this->setNotCalledListeners($dispatcher->getNotCalledListeners($this->currentRequest), $name);
            $this->setOrphanedEvents($dispatcher->getOrphanedEvents($this->currentRequest), $name);
        }

        $this->data = $this->cloneVar($this->data);
    }

    /**
     * @return \Symfony\Component\VarDumper\Cloner\Data|array<mixed>
     */
    public function getData(): array|Data
    {
        return $this->data;
    }

    /**
     * @param array<int, array<string, mixed>> $listeners
     *
     * @return void
     */
    public function setCalledListeners(array $listeners, ?string $dispatcher = null): void
    {
        $this->data[$dispatcher ?? $this->defaultDispatcher]['called_listeners'] = $this->enrichValueForListeners($listeners);
    }

    /**
     * @return \Symfony\Component\VarDumper\Cloner\Data|array<mixed>
     */
    public function getCalledListeners(?string $dispatcher = null): array|Data
    {
        return $this->data[$dispatcher ?? $this->defaultDispatcher]['called_listeners'] ?? [];
    }

    /**
     * @param array<int, array<string, mixed>> $listeners
     *
     * @return void
     */
    public function setNotCalledListeners(array $listeners, ?string $dispatcher = null): void
    {
        $this->data[$dispatcher ?? $this->defaultDispatcher]['not_called_listeners'] = $this->enrichValueForListeners($listeners);
    }

    /**
     * @return \Symfony\Component\VarDumper\Cloner\Data|array<mixed>
     */
    public function getNotCalledListeners(?string $dispatcher = null): array|Data
    {
        return $this->data[$dispatcher ?? $this->defaultDispatcher]['not_called_listeners'] ?? [];
    }

    /**
     * @param array<int, string> $events
     *
     * @return void
     */
    public function setOrphanedEvents(array $events, ?string $dispatcher = null): void
    {
        $this->data[$dispatcher ?? $this->defaultDispatcher]['orphaned_events'] = $events;
    }

    /**
     * @return \Symfony\Component\VarDumper\Cloner\Data|array<mixed>
     */
    public function getOrphanedEvents(?string $dispatcher = null): array|Data
    {
        return $this->data[$dispatcher ?? $this->defaultDispatcher]['orphaned_events'] ?? [];
    }

    public function getName(): string
    {
        return 'events';
    }

    /**
     * Appends file location to the stub value of anonymous closure listeners
     * so the profiler shows which file each closure is defined in.
     *
     * @param array<int, array<string, mixed>> $listeners
     *
     * @return array<int, array<string, mixed>>
     */
    protected function enrichValueForListeners(array $listeners): array
    {
        foreach (array_keys($listeners) as $key) {
            if (empty($listeners[$key]['event'])) {
                continue;
            }

            $stub = $listeners[$key]['stub'] ?? null;

            if (!$stub instanceof ClassStub || !isset($stub->attr['file'])) {
                continue;
            }

            $file = str_replace(APPLICATION_ROOT_DIR . '/', '', $stub->attr['file']);
            $stub->value = sprintf('%s %s%s:%d', $stub->value, PHP_EOL, $file, $stub->attr['line'] ?? 0);
        }

        return $listeners;
    }
}
