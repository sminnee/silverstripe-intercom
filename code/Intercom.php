<?php

namespace Sminnee\SilverStripeIntercom;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\Middleware;
use GuzzleHttp\Exception\ConnectException;
use LogicException;
use Intercom\IntercomClient;
use SS_List;
use Member;
use Config;
use Injector;

/**
 * Entry point for interaction with with Intercom.
 */
class Intercom
{

	private $personalAccessToken;
	private $appId;
	private $httpClient;
	private $client;

	function __construct() {
		if(defined('INTERCOM_PERSONAL_ACCESS_TOKEN')) {
			$this->personalAccessToken = INTERCOM_PERSONAL_ACCESS_TOKEN;
		}
		if(defined('INTERCOM_APP_ID')) {
			$this->appId = INTERCOM_APP_ID;
		}
	}

	function getPersonalAccessToken() {
		if(!$this->personalAccessToken) {
			throw new LogicException("Intercom Personal Access Token not set! Define INTERCOM_PERSONAL_ACCESS_TOKEN or use Injector to set Personal Access Token");
		}
		return $this->personalAccessToken;
	}

	function setPersonalAccessToken($token) {
		$this->personalAccessToken = $token;
	}

	function getAppId() {
		if(!$this->appId) {
			throw new LogicException("Intercom App ID not set! Define INTERCOM_APP_ID or use Injector to set AppId");
		}
		return $this->appId;
	}
	function setAppId($appId) {
		$this->appId = $appId;
	}

	/**
	 * Return an HTTP client used by IntercomClient.
	 * By default, we provide one that has some retry/back-off support.
	 *
	 * @return Client
	 */
	public function getHttpClient() {
		if ($this->httpClient) {
			return $this->httpClient;
		}
		$handlerStack = HandlerStack::create(new CurlHandler());
		$handlerStack->push(Middleware::retry(
			function ($retries, $request, $response, $exception) {
				if ($retries > 5) {
					return false;
				}
				if ($exception instanceof ConnectException) {
					return true;
				}
				if ($response && ($response->getStatusCode() >= 500 || $response->getStatusCode() == 429)) {
					// 500s and 429 or greater we should keep retrying. 429 is one example "Too Many Requests"
					// which is the point we have exceeded our rate limit.
					return true;
				}
				return false;
			},
			function ($retries) {
				return 2000 * $retries; // keep increasing the delay each retry
			}
		));
		$client = new Client([
			'handler' => $handlerStack,
			'timeout' => 10.0
		]);
		$this->httpClient = $client;
		return $this->httpClient;
	}

	/**
	 * @param $client Client
	 */
	public function setHttpClient($client) {
		$this->httpClient = $client;
	}

	/**
	 * @return IntercomClient
	 */
	public function getClient() {
		if ($this->client) {
			return $this->client;
		}
		$client = new IntercomClient($this->getPersonalAccessToken(), null);
		$client->setClient($this->getHttpClient());
		$this->client = $client;
		return $this->client;
	}

	public function setClient($client) {
		$this->client = $client;
	}

	/**
	 * Return a list of all users for this application.
	 * Define via the 'user_list' project, which should be '%$' followed by an Injector service anem.
	 * Defaults to all Members
	 * @return DataList
	 */
	public function getUserList() {
		if($userList = Config::inst()->get('Sminnee\SilverStripeIntercom\Intercom', 'user_list')) {
			if(substr($userList,0,2) != '%$') {
				throw new \InvalidArgumentException("Please set user_list to a string of the form %\$ServiceName");
			}
			return Injector::inst()->get(substr($userList, 2));
		}

		// Default all users
		return Member::get();
	}

	/**
	 * Bulk load a set of members using the same meta-data rules as if they were to log in
	 * @param SS_List $members A list of members
	 * @return IntercomBulkJob
	 */
	public function bulkLoadUsers(SS_List $members) {
		$userFields = Config::inst()->get('Intercom','user_fields');
		$companyFields = Config::inst()->get('Intercom','company_fields');

		$scriptTags = new IntercomScriptTags();

		// Build the batch API submission
		foreach($members as $member) {
			$settings = $scriptTags->getIntercomSettings($member);

			unset($settings['app_id']);
			unset($settings['user_hash']);

			foreach($settings as $k => $v) {
				if(!in_array($k, $userFields)) {
					$settings['custom_attributes'][$k] = $v;
					unset($settings[$k]);
				}
			}

			if(isset($settings['company'])) {
				foreach($settings['company'] as $k => $v) {
					if(!in_array($k, $companyFields)) {
						$settings['company']['custom_attributes'][$k] = $v;
						unset($settings['company'][$k]);
					}
				}
			}

			$items[] = [
				'data_type' => 'user',
				'method' => 'post',
				'data' => $settings,
			];
		}

		$result = $this->getClient()->bulk->users(['items' => $items]);

		return $this->getBulkJob($result->id);
	}

	/**
	 * Return an IntercomBulkJob object for the given job
	 * @param string $id The job ID
	 * @return IntercomBulkJob
	 */
	public function getBulkJob($id) {
		return new IntercomBulkJob($this->getClient(), $id);
	}
	/**
	 * Track an event with the current user.
	 *
	 * @param  string $eventName Event name. Passed straight to intercom.
	 * @param  array $eventData A map of event data. Passed straight to intercom.
	 * @param Member $member - if not provided, it will try to use Member::currentUser();
	 */
	function trackEvent($eventName, $eventData = array(), Member $member = null) {
		$payload = array(
			'event_name' => $eventName,
			'created_at' => time(),
		);

		$scriptTags = new IntercomScriptTags();
		$settings = $scriptTags->getIntercomSettings($member);

		if(empty($settings['email']) && empty($settings['user_id'])) {
			throw new LogicException("Can't track event when no user logged in");
		}

		if(!empty($settings['email'])) $payload['email'] = $settings['email'];
		if(!empty($settings['user_id'])) $payload['user_id'] = $settings['user_id'];

		if($eventData) {
			$payload['metadata'] = $eventData;
		}

		$this->getClient()->events->create($payload);
	}
}
