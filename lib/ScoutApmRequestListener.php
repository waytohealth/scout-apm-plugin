<?php

declare(strict_types=1);

use Scoutapm\Events\Span\SpanReference;
use Scoutapm\ScoutApmAgent;

class ScoutApmRequestListener
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
        $dispatcher->connect('controller.change_action', [$this, 'onStartRequest']);
        $dispatcher->connect('response.filter_content', [$this, 'onSendContent']);
    }

    /**
     * Handler for start of request.
     *
     * Per sf1 docs:
     * "The controller.change_action is notified just before an action is executed."
     *
     * @link https://symfony.com/legacy/doc/reference/1_4/en/15-Events#chapter_15_sub_controller_change_action
     *
     * Based on symfony bundle version
     * @see  \Scoutapm\ScoutApmBundle\EventListener\InstrumentationListener::onKernelController()
     */
    public function onStartRequest(sfEvent $event): void
    {
        $sfRequest = sfContext::getInstance()->getRequest();
        assert($sfRequest instanceof sfWebRequest);

        $this->currentSpan = $this->agent->startSpan(sprintf(
            '%s/%s::%s',
            SpanReference::INSTRUMENT_CONTROLLER,
            $sfRequest->getParameter('module'), // could also be $event['module']
            $sfRequest->getParameter('action')  // could also be $event['action']
        ));
    }

    /**
     * Handler for end of request.
     *
     * Per sf1 docs:
     * "The response.filter_content event is notified before a response is sent. By listening to this event,
     * you can manipulate the content of the response before it is sent."
     *
     * @link https://symfony.com/legacy/doc/reference/1_4/en/15-Events#chapter_15_sub_response_filter_content
     *
     * We don't actually modify the response, we just want to hook into that.
     * Based on symfony bundle version
     * @see  \Scoutapm\ScoutApmBundle\EventListener\InstrumentationListener::onKernelResponse()
     */
    public function onSendContent(sfEvent $event, string $content): string
    {
        $subject = $event->getSubject();
        assert($subject instanceof sfWebResponse);

        if ($this->currentSpan !== null) {
            $this->agent->stopSpan();
            $this->currentSpan = null;
        }

        // Nothing is sent to Scout until you call this - so call this at the end of your request
        $this->agent->send();

        return $content;
    }
}
