<?php

namespace Sminnee\SilverStripeIntercom;

use LogicException;
use Intercom\IntercomBasicAuthClient;
use Member;

/**
 * Entry point for interaction with with Intercom.
 */
class Intercom
{

	private $apiKey;
	private $appId;
	private $client;

	function __construct() {
		if(defined('INTERCOM_API_KEY')) {
			$this->apiKey = INTERCOM_API_KEY;
		}
		if(defined('INTERCOM_APP_ID')) {
			$this->appId = INTERCOM_APP_ID;
		}
	}

	function getApiKey() {
		if(!$this->apiKey) {
			throw new LogicException("Intercom API key not set! Define INTERCOM_API_KEY or use Injector to set ApiKey");
		}
		return $this->apiKey;
	}

	function setApiKey($apiKey) {
		$this->apiKey = $apiKey;
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


	public function getClient() {
		if(!$this->client) {
			$this->client = IntercomBasicAuthClient::factory(array(
			    'app_id' => $this->getAppId(),
			    'api_key' => $this->getApiKey()
			));
		}
		return $this->client;
	}


	/**
	 * Return a list of all users for this application.
	 * Define via the 'user_list' project, which should be '%$' followed by an Injector service anem.
	 * Defaults to all Members
	 * @return DataList
	 */
	public function getUserList() {
		if($userList = \Config::inst()->get('Intercom', 'user_list')) {
			if(substr($userList,0,2) != '%$') {
				throw new \InvalidArgumentException("Please set user_list to a string of the form %\$ServiceName");
				return Injector::inst()->get(substr($userList, 2));
			}
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
		$userFields = ['type', 'user_id', 'email', 'id', 'signed_up_at',  'created_at','name', 'last_seen_ip', 'custom_attributes', 'last_seen_user_agent', 'last_request_at', 'unsubscribed_from_emails', 'update_last_request_at', 'new_session', 'company'];
		$companyFields = ['type', 'id', 'created_at', 'remote_created_at', 'updated_at', 'company_id', 'name', 'custom_attributes', 'session_count', 'monthly_spend', 'user_count', 'plan'];

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

		$result = $this->getClient()->bulkUsers(['items' => $items]);

		return $this->getBulkJob($result->get('id'));
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

		$this->getClient()->createEvent($payload);
	}
}
