<?php
include_once("objects/data/GTMCommand.php");
include_once("pages/HTMLHead.php");
include_once("utils/Session.php");

/**
 * Events for the next page: an event of a POST that redirects (the cart add, a form) is sent on the page after
 * the redirect - the frames of the public pages flush the queue into their head (SparkSitePage, StorePage), so the
 * navigation does not lose it.
 */
class GTMQueue
{
    const string SESSION_QUEUE = "gtm.queue";

    /**
     * Send '$event' on the next page
     */
    public static function Queue(GTMCommand $event) : void
    {
        $queue = Session::Get(GTMQueue::SESSION_QUEUE, array());
        if (!is_array($queue)) $queue = array();
        //a few at most - a visitor who never reaches a page does not grow the session
        $queue = array_slice(array_merge($queue, array($event->jsonSerialize())), -5);
        Session::Set(GTMQueue::SESSION_QUEUE, $queue);
    }

    /**
     * The queued events into '$head' - once (no session is started for it)
     */
    public static function Flush(HTMLHead $head) : void
    {
        if (!Session::Contains(GTMQueue::SESSION_QUEUE)) return;
        $queue = Session::Get(GTMQueue::SESSION_QUEUE, array());
        Session::Remove(GTMQueue::SESSION_QUEUE);
        if (!is_array($queue)) return;
        foreach ($queue as $data) {
            if (!is_array($data) || empty($data["command"]) || empty($data["type"])) continue;
            $command = new GTMCommand((string)$data["command"]);
            $command->setType((string)$data["type"]);
            if (isset($data["parameters"]) && is_array($data["parameters"])) $command->setParameters($data["parameters"]);
            $head->addScript($command);
        }
    }
}
