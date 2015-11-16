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
