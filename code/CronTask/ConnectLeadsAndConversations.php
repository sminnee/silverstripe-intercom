<?php

namespace SilverStripe\Intercom\CronTask;

use Config;
use CronTask;
use SS_HTTPRequest;
use SilverStripe\Intercom\Task\ConnectLeadsAndConversations as ConnectLeadsAndConversationsTask;

class ConnectLeadsAndConversations implements CronTask
{
    /**
     * How often to run this task.
     *
     * @var string
     *
     * @config
     */
    private static $schedule = "* */1 * * *";

    /**
     * @inheritdoc
     *
     * @return string
     */
    public function getSchedule()
    {
        return Config::inst()->get(static::class, "schedule");
    }

    /**
     * @inheritdoc
     */
    public function process()
    {
        if ($this->getSchedule()) {
            $task = new ConnectLeadsAndConversationsTask();
            $task->run(new SS_HTTPRequest("GET", "/"));
        }
    }
}
