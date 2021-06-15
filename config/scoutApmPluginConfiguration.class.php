<?php

declare(strict_types=1);

use Psr\Log\NullLogger;
use Scoutapm\Agent;
use Scoutapm\Config;
use Scoutapm\ScoutApmAgent;

/**
 * This listens to various framework events and allows scout APM to hook into those as appropriate.
 *
 * @link  https://symfony.com/legacy/doc/reference/1_4/en/15-Events#chapter_15_sub_context_load_factories
 * has details on the specific events to hook into
 */
class scoutApmPluginConfiguration extends sfPluginConfiguration
{
    /** @var ScoutApmAgent|null */
    private static $agent;

    private function getAgentInstance(): ScoutApmAgent
    {
        if (self::$agent) {
            return self::$agent;
        }

        self::$agent = Agent::fromConfig(
            new Config(), // use environment variables
            new NullLogger() // TODO figure out a PSR-3 logger other than this
        );
        // If the core agent is not already running, this will download and run it (from /tmp by default)
        self::$agent->connect();

        return self::$agent;
    }

    public function initialize()
    {
        $agent = $this->getAgentInstance();

        $doctrineListener = new ScoutApmDoctrineListener($agent);
        $doctrineListener->connect($this->dispatcher);

        $taskListener = new ScoutApmTaskListener($agent);
        $taskListener->connect($this->dispatcher);

        $requestListener = new ScoutApmRequestListener($agent);
        $requestListener->connect($this->dispatcher);

        return true;
    }
}
