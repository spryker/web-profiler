<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\WebProfiler\Communication\DataCollector;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\VarDumper\Cloner\Data;
use Throwable;

class WebProfilerSessionDataCollector extends DataCollector
{
    protected const string NAME = 'session';

    protected const string KEY_SESSION_METADATA = 'session_metadata';

    protected const string KEY_SESSION_ATTRIBUTES = 'session_attributes';

    protected const string KEY_FLASHES = 'flashes';

    protected const string METADATA_KEY_CREATED = 'Created';

    protected const string METADATA_KEY_LAST_USED = 'Last used';

    protected const string METADATA_KEY_LIFETIME = 'Lifetime';

    /**
     * @var array<string, array<string>>
     */
    protected array $incomingFlashes = [];

    /**
     * @param array<string, array<string>> $flashes
     */
    public function setIncomingFlashes(array $flashes): void
    {
        $this->incomingFlashes = $flashes;
    }

    public function collect(Request $request, Response $response, ?Throwable $exception = null): void
    {
        $sessionMetadata = [];
        $sessionAttributes = [];
        $flashes = [];

        if ($request->hasSession() && $request->getSession()->isStarted()) {
            $session = $request->getSession();

            $sessionMetadata = $this->collectSessionMetadata($session);
            $sessionAttributes = $session->all();
            $flashes = $this->collectFlashMessages($session);
        }

        $this->data = [
            static::KEY_SESSION_METADATA => $sessionMetadata,
            static::KEY_SESSION_ATTRIBUTES => $this->cloneVar($sessionAttributes),
            static::KEY_FLASHES => $flashes,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getSessionMetadata(): array
    {
        return $this->data[static::KEY_SESSION_METADATA] ?? [];
    }

    /**
     * @return \Symfony\Component\VarDumper\Cloner\Data|array<string, mixed>
     */
    public function getSessionAttributes(): Data|array
    {
        return $this->data[static::KEY_SESSION_ATTRIBUTES] ?? [];
    }

    /**
     * @return array<string, array<string>>
     */
    public function getFlashes(): array
    {
        return $this->data[static::KEY_FLASHES] ?? [];
    }

    public function getName(): string
    {
        return static::NAME;
    }

    public function reset(): void
    {
        parent::reset();

        $this->data = [];
        $this->incomingFlashes = [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function collectSessionMetadata(SessionInterface $session): array
    {
        return [
            static::METADATA_KEY_CREATED => date(DATE_RFC822, $session->getMetadataBag()->getCreated()),
            static::METADATA_KEY_LAST_USED => date(DATE_RFC822, $session->getMetadataBag()->getLastUsed()),
            static::METADATA_KEY_LIFETIME => $session->getMetadataBag()->getLifetime(),
        ];
    }

    /**
     * @return array<string, array<string>>
     */
    protected function collectFlashMessages(SessionInterface $session): array
    {
        if (!$session instanceof FlashBagAwareSessionInterface) {
            return [];
        }

        $flashes = $this->incomingFlashes;

        foreach ($session->getFlashBag()->peekAll() as $type => $messages) {
            $flashes[$type] = array_unique(array_merge($flashes[$type] ?? [], $messages));
        }

        return $flashes;
    }
}
