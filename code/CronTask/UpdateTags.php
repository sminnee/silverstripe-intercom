<?php

namespace SilverStripe\Intercom\CronTask;

use CronTask;
use SS_HTTPRequest;
use SilverStripe\Intercom\Task\UpdateTags as UpdateTagsTask;

class UpdateTags implements CronTask
{
    /**
     * @inheritdoc
     *
     * @return string
     */
    public function getSchedule()
    {
        return "*/1 * * * *";
    }

    /**
     * @inheritdoc
     */
    public function process()
    {
        $task = new UpdateTagsTask();
        $task->run(new SS_HTTPRequest("GET", "/"));
    }
}
