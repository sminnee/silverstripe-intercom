<?php
/**
 * Build task to delete users in Intercom who do not exist in the local database.
 */
class IntercomCleanupTask extends \BuildTask {

	/**
	 * @var GuzzleHttp\Client
	 */
	protected $client;

	/**
	 * @var \DataList
	 */
	protected $members;

	/**
	 * @var int
	 */
	protected $deleted = 0;

	/**
	 * @var bool
	 */
	protected $dryRun = false;

	public function __construct() {
		parent::__construct();

		$this->client = \Injector::inst()->get('Sminnee\SilverStripeIntercom\Intercom')->getClient();
		$this->members = \Member::get();
	}

	/**
	 * @param \SS_HTTPRequest $request
	 */
	public function run($request) {
		if ($request && $request->requestVar('dryrun')) {
			$this->dryRun = true;
		}

		if ($this->dryRun) {
			$this->log('Running in dry-run mode. No users will be deleted');
		}

		$response = $this->client->users->getUsers(['page' => '1']);
		$users = $response->users;
		if ($response->pages->total_pages > 1) {
			for ($i = 2; $i <= $response->pages->total_pages; $i++) {
				$response = $this->client->users->getUsers(['page' => $i]);
				$users = array_merge($users, $response->users);
			}
		}
		foreach ($users as $user) {
			$this->processUser($user);
		}

		$this->log(sprintf('Finished. %s users deleted', $this->deleted));
	}

	/**
	 * Process Intercom user data from response to see if they need to be deleted or not.
	 * @param stdClass $user
	 */
	protected function processUser(stdClass $user) {
		if (!$this->members->find('Email', $user->email)) {
			$this->log(sprintf('Deleting user %s (email: %s) from Intercom...', $user->id, $user->email));
			if (!$this->dryRun) {
				$this->client->users->deleteUser($user->id);
				$this->deleted++;
			}
		}
	}

	protected function log($message) {
		echo \Director::is_cli() ? ($message . PHP_EOL) : ($message . '<br>');
	}

}
