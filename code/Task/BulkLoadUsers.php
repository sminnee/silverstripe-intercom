<?php

namespace SilverStripe\Intercom\Task;

use BuildTask;
use Debug;
use Director;
use Injector;
use SS_HTTPRequest;

class BulkLoadUsers extends BuildTask
{
    /**
     * @param SS_HTTPRequest $request
     */
    public function run($request)
    {
        $intercom = Injector::inst()->get("SilverStripe\\Intercom\\Client");

        if ($jobID = $request->getVar("JobID")) {
            $job = $intercom->getBulkJob($request->getVar("JobID"));

            Debug::dump($job->getInfo());
            Debug::dump($job->getErrors());

            return;
        }

        $members = $intercom->getUserList();
        $this->line("<li>" . implode("</li><li>", $members->column("Email")), "</li>");

        $result = $intercom->bulkLoadUsers($members);
        $jobID = $result->getID();

        if (Director::is_cli()) {
            $this->line("Job id " . $jobID);
            $this->line("To see status, run: sake dev/tasks/SilverStripe-Intercom-Task-BulkLoadUsers JobID=" . $jobID);

            return;
        }

        $url = Director::absoluteURL("dev/tasks/SilverStripe-Intercom-Task-BulkLoadUsers?JobID=" . urlencode($jobID));

        $this->line("<p>Job id " . $jobID . "</p>");
        $this->line("<p><a href='" . $url . "'>Click here to see job status</a></p>");
    }

    /**
     * @param string $message
     */
    private function line($message)
    {
        print $message . "\n";
        flush();
    }
}
