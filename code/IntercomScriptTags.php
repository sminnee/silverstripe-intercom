<?php

namespace Sminnee\SilverStripeIntercom;

use Member;
use ViewableData;

/**
 * Generates the IntercomScriptTags.
 * Place into a template; forTemplate() returns the tag
 */
class IntercomScriptTags extends ViewableData
{	

	private static $enabled = true;

	public function isEnabled(Member $member = null) {
		if(!defined('INTERCOM_APP_ID')) {
			return false;
		}

		if(!$this->config()->enabled) {
			return false;
		}

		if(!$member) {
			$member = Member::currentUserID();
		}

		if(!$this->config()->anonymous_access && !$member) {
			return false;
		}

		return true;
	}

	/**
	 * Return the Intercom settings an array.
	 * Extendable by adding extensions with updateIntercomSettings().
	 *
	 * @param Member $member - if not provided, it will try to Member::currentUser();
	 *
	 * @return array The settings, ready for JSON-encoding
	 */
	public function getIntercomSettings(Member $member = null) {
		if(!$this->isEnabled($member)) {
			return [];
		}

		$settings = [
			'app_id' => INTERCOM_APP_ID,
		];

		if(!$member) {
			$member = Member::currentUser();
		}

		if($member) {
			$settings['name'] = trim($member->FirstName . ' ' . $member->Surname);
			$settings['email'] = $member->Email;
			$settings['created_at'] = trim($member->FirstName . ' ' . $member->Surname);
			$settings['created_at'] = $member->obj('Created')->Format('U');

			foreach((array)$this->config()->user_fields as $intercomKey => $propertyName) {
				$settings[$intercomKey] = $member->$propertyName;
			}

			if(defined('INTERCOM_SECRET_KEY')) {
				$settings['user_hash'] = $this->generateUserHash($member->Email);
			} else {
				$settings['user_id'] = $member->ID;
			}

			if($this->config()->company_property) {
				$prop = $this->config()->company_property;
				$org = $member->$prop;
				if($org) {
					$settings['company']['id'] = $org->ID;
					$settings['company']['created_at'] = $org->obj('Created')->Format('U');

					foreach((array)$this->config()->company_fields as $intercomKey => $propertyName) {
						$settings['company'][$intercomKey] = $org->$propertyName;
					}
				}
			}
		};


		$this->extend('updateIntercomSettings', $settings);
		return $settings;
	}

	public function generateUserHash($identifier) {
		if(defined('INTERCOM_SECRET_KEY')) {
			$secret = INTERCOM_SECRET_KEY;
			return hash_hmac("sha256", $identifier, $secret);
		}
	}

	function IntercomSettingsJSON() {
		return json_encode($this->getIntercomSettings());
	}

	function forTemplate() {
		if(!$this->isEnabled()) {
			return null;
		}

		return $this->renderWith('IntercomScriptTags');
	}

}