<?php


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

  public function preFetch(Doctrine_Event $event)
  {
    $this->startQuery('SQL/query', $event->getQuery(), $event->getParams());
  }

  public function preStmtExecute(Doctrine_Event $event)
  {
    $this->startQuery('SQL/query', $event->getQuery(), $event->getParams());
  }

  public function postFetch(Doctrine_Event $event)
  {
    $this->endQuery();
  }

  public function postStmtExecute(Doctrine_Event $event)
  {
    $this->endQuery();
  }

  private function startQuery($eventName, $query, $params)
  {
    $this->currentSpan = $this->agent->startSpan($eventName);

    if ($this->currentSpan === null) {
      return;
    }

    $this->currentSpan->tag('db.statement', $query);
  }

  private function endQuery()
  {
    if ($this->currentSpan === null) {
      return;
    }

    $this->agent->stopSpan();
    $this->currentSpan = null;
  }
}
