<?php

namespace SilverStripe\Intercom\CronTask;

use CronTask;
use SS_HTTPRequest;
use SilverStripe\Intercom\Task\UpdateTeams as UpdateTeamsTask;

class UpdateTeams implements CronTask
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
            $task = new UpdateTeamsTask();
            $task->run(new SS_HTTPRequest("GET", "/"));
        }
    }
}
