<?php

/**
 * Build task to bulk-load all relevant users into Intercom via the API
 */
class IntercomBulkLoadTask extends BuildTask
{
	function run($request) {
		$intercom = Injector::inst()->get('Sminnee\SilverStripeIntercom\Intercom');

		if($jobID = $request->getVar('JobID')) {
			$job = $intercom->getBulkJob($request->getVar('JobID'));
			Debug::dump($job->getInfo());
			Debug::dump($job->getErrors());

		} else {
			$members = $intercom->getUserList();
			echo "<li>" . implode("</li>\n<li>", $members->column('Email')), "</li>\n";
			$result = $intercom->bulkLoadUsers($members);
			$jobID = $result->getID();

			if(Director::is_cli()) {
	 			echo "Job id $jobID\n";
	 			echo "To see status, run: sake dev/tasks/IntercomBulkLoadTask JobID=$jobID\n";

			} else {
	 			echo "<p>Job id $jobID</p>\n";

				echo "<p><a href=\"" 
					. Director::absoluteURL('dev/tasks/IntercomBulkLoadTask?JobID=' . urlencode($jobID))
					. "\">Click here to see job status</a></p>";
			}
		}
	}
}