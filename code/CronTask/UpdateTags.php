<?php

namespace SilverStripe\Intercom\CronTask;

use SilverStripe\Core\Config\Config;
use SilverStripe\CronTask\Interfaces\CronTask;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Intercom\Task\UpdateTags as UpdateTagsTask;

if (!interface_exists(CronTask::class)) {
    return;
}

class UpdateTags implements CronTask
{
    /**
     * How often to run this task.
     *
     * @var string
     *
     * @config
     */
    private static $schedule = "*/30 * * * *";

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
            $task = new UpdateTagsTask();
            $task->run(new SS_HTTPRequest("GET", "/"));
        }
    }
}
