<?php

namespace SilverStripe\Intercom\CronTask;

use SilverStripe\Core\Config\Config;
use SilverStripe\CronTask\Interfaces\CronTask;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Intercom\Task\ConnectLeadsAndConversations as ConnectLeadsAndConversationsTask;

if (!interface_exists(CronTask::class)) {
    return;
}

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
