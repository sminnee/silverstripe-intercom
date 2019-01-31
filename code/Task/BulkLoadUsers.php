<?php

namespace SilverStripe\Intercom\Task;

use SilverStripe\Dev\BuildTask;
use SilverStripe\Dev\Debug;
use SilverStripe\Control\Director;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Intercom\Intercom;

class BulkLoadUsers extends BuildTask
{
    /**
     * @param SS_HTTPRequest $request
     */
    public function run($request)
    {
        $intercom = Injector::inst()->get(Intercom::class);

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
