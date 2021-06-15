<?php


use Scoutapm\Events\Span\SpanReference;
use Scoutapm\ScoutApmAgent;

class ScoutApmTaskListener
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
        $dispatcher->connect('command.pre_command', [$this, 'onPreCommand']);
        $dispatcher->connect('command.post_command', [$this, 'onPostCommand']);
    }

    public function onPreCommand(sfEvent $event): void
    {
        $this->currentSpan = $this->agent->startSpan(sprintf(
            '%s/%s',
            SpanReference::INSTRUMENT_JOB,
            get_class($event->getSubject())
        ));
        if (!$this->currentSpan) {
            return;
        }

        $this->currentSpan->tag('command.parameters', $event->getParameters());
    }

    public function onPostCommand(sfEvent $event): void
    {
        if ($this->currentSpan !== null) {
            $this->agent->stopSpan();
            $this->currentSpan = null;
        }

        $this->agent->send();
    }
}
