<?php

namespace Sminnee\SilverStripeIntercom;

use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\Debug;
use SilverStripe\Control\Email\Email;
use SilverStripe\Control\Director;
use SilverStripe\Dev\BuildTask;

/**
 * Build task to bulk-load all relevant users into Intercom via the API
 */
class IntercomBulkLoadTask extends BuildTask
{
    public function run($request)
    {
        $intercom = Injector::inst()->get('Sminnee\SilverStripeIntercom\Intercom');

        if ($jobID = $request->getVar('JobID')) {
            $job = $intercom->getBulkJob($request->getVar('JobID'));
            Debug::dump($job->getInfo());
            Debug::dump($job->getErrors());
        } else {
            $members = $intercom->getUserList();
            // Intercom has a hard limit of 100 on bulk jobs
            foreach ($this->chunkDataList($members, 100) as $memberchunk) {
                echo "<li>" . implode("</li>\n<li>", $memberchunk->column(Email::class)), "</li>\n";
                $result = $intercom->bulkLoadUsers($memberchunk);
                $jobID = $result->getID();

                if (Director::is_cli()) {
                    echo "Job id $jobID\n";
                    echo "To see status, run: sake dev/tasks/IntercomBulkLoadTask JobID=$jobID\n";
                } else {
                    echo "<p>Job id $jobID</p>\n";

                    echo "<p><a href=\""
                        . Director::absoluteURL('dev/tasks/IntercomBulkLoadTask?JobID=' . urlencode($jobID))
                        . "\">Click here to see job status</a></p>";
                }
                echo "\n";
            }
        }
    }

    /**
     * @param $datalist
     * @param $chunksize
     * @return array
     */
    public function chunkDataList($datalist, $chunksize)
    {
        $count = $datalist->count();

        if ($count < $chunksize) {
            return [$datalist];
        }

        $rounds = round($count / $chunksize);
        $chunks = [];
        $offset = 0;

        for ($i = 0; $i < $rounds; $i++) {
            $chunks[] = $datalist->limit($chunksize, $offset);
            $offset = $offset + $chunksize;
        }

        return $chunks;
    }
}
