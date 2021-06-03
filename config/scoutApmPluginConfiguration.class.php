<?php

use Psr\Log\NullLogger;
use Scoutapm\Agent;
use Scoutapm\Config;
use Scoutapm\Events\Span\SpanReference;
use Scoutapm\ScoutApmAgent;


/**
 * Class scoutApmPluginConfiguration
 *
 * This listens to various framework events and allows scout APM to hook into those as appropriate.
 * @link  https://symfony.com/legacy/doc/reference/1_4/en/15-Events#chapter_15_sub_context_load_factories
 * has details on the specific events to hook into
 */
class scoutApmPluginConfiguration extends sfPluginConfiguration
{
  /** @var ScoutApmAgent */
  private $agent;

  private function createAgent()
  {
    $agent = Agent::fromConfig(
      new Config(), // use environment variables
      new NullLogger() // TODO figure out a PSR-3 logger other than this
    );
    // If the core agent is not already running, this will download and run it (from /tmp by default)
    $agent->connect();

    return $agent;
  }

  public function initialize()
  {
    $this->agent = $this->createAgent();

    $this->dispatcher->connect(
      'doctrine.configure_connection',
      function (sfEvent $event) {
        /** @var Doctrine_Connection $connection */
        $connection = $event['connection'];

        $profiler = new ScoutApmDoctrineListener($this->agent);

        $connection->addListener($profiler, 'scout_apm_profiler');
      }
    );
    $this->dispatcher->connect(
      'command.pre_command',
      function (sfEvent $event) {
        $span = $this->agent->startSpan(
          sprintf(
            "%s/%s",
            SpanReference::INSTRUMENT_JOB,
            get_class($event->getSubject())
          )
        );
        if ($span) {
          $span->tag('command.parameters', $event->getParameters());
        }
      }
    );
    $this->dispatcher->connect(
      'command.post_command',
      function (sfEvent $event) {
        $this->agent->stopSpan();
        $this->agent->send();
      }
    );

    $requestListener = new ScoutApmRequestListener($this->agent);
    $requestListener->connect($this->dispatcher);
  }
}
