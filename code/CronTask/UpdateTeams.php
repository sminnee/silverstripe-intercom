<?php

namespace SilverStripe\Intercom\CronTask;

use CronTask;
use SS_HTTPRequest;
use SilverStripe\Intercom\Task\UpdateTeams as UpdateTeamsTask;

class UpdateTeams implements CronTask
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
        $task = new UpdateTeamsTask();
        $task->run(new SS_HTTPRequest("GET", "/"));
    }
}
