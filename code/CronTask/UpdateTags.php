<?php

namespace SilverStripe\Intercom\CronTask;

use CronTask;
use SS_HTTPRequest;
use SilverStripe\Intercom\Task\UpdateTags as UpdateTagsTask;

class UpdateTags implements CronTask
{
    /**
     * How often to run this task.
     *
     * @var string
     *
     * @config
     */
    private static $schedule = "*/1 * * * *";

    /**
     * @inheritdoc
     *
     * @return string
     */
    public function getSchedule()
    {
        return $this->config()->schedule;
    }

    /**
     * @inheritdoc
     */
    public function process()
    {
        if ($this->config()->schedule) {
            $task = new UpdateTagsTask();
            $task->run(new SS_HTTPRequest("GET", "/"));
        }
    }
}
