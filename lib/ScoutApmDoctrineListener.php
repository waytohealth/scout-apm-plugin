<?php

declare(strict_types=1);

use Scoutapm\Events\Span\SpanReference;
use Scoutapm\ScoutApmAgent;

class ScoutApmDoctrineListener extends Doctrine_EventListener
{
    /** @var ScoutApmAgent */
    private $agent;
    /** @var SpanReference|null */
    private $currentSpan;

    public function __construct(ScoutApmAgent $agent)
    {
        $this->agent = $agent;
    }

    public function connect(sfEventDispatcher $dispatcher): void
    {
        $dispatcher->connect('doctrine.configure_connection', [$this, 'onConfigureConnection']);
    }

    public function onConfigureConnection(sfEvent $event): void
    {
        $connection = $event['connection'];
        assert($connection instanceof Doctrine_Connection);

        $connection->addListener($this, 'scout_apm_profiler');
    }

    public function preFetch(Doctrine_Event $event): void
    {
        $this->startQuery('SQL/Query', $event->getQuery());
    }

    public function preStmtExecute(Doctrine_Event $event): void
    {
        $this->startQuery('SQL/Query', $event->getQuery());
    }

    public function postFetch(Doctrine_Event $event): void
    {
        $this->endQuery();
    }

    public function postStmtExecute(Doctrine_Event $event): void
    {
        $this->endQuery();
    }

    private function startQuery(string $eventName, string $query): void
    {
        $this->currentSpan = $this->agent->startSpan($eventName);

        if ($this->currentSpan === null) {
            return;
        }

        $this->currentSpan->tag('db.statement', $query);
    }

    private function endQuery(): void
    {
        if ($this->currentSpan === null) {
            return;
        }

        $this->agent->stopSpan();
        $this->currentSpan = null;
    }
}
