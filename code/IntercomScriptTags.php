<?php

namespace Sminnee\SilverStripeIntercom;

use SilverStripe\Security\Member;
use SilverStripe\View\ViewableData;
use SilverStripe\TagManager\SnippetProvider;
use SilverStripe\Forms;
use SilverStripe\Security\Security;
use SilverStripe\View\ArrayData;
use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\FieldType\DBField;

/**
 * Generates the IntercomScriptTags.
 * Place into a template; forTemplate() returns the tag
 */
class IntercomScriptTags implements SnippetProvider
{

	public function getTitle()
	{
		return "Intercom chat";
	}

	public function getSummary(array $params)
	{
		return "Intercom chat";
	}

	public function getParamFields()
	{
		if ($appID = getenv('INTERCOM_APP_ID')) {
			$appIDField = new Forms\ReadonlyField('AppID', 'Intercom App ID', $appID);

		} else {
			$appIDField = new Forms\TextField('AppID', 'Intercom App ID');
		}

		if (getenv('INTERCOM_SECRET_KEY')) {
			$secretKeyField = new Forms\LiteralField('SecretKey', 'Intercom Secret Key', '(hidden)');

		} else {
			$secretKeyField = new Forms\TextField('SecretKey', 'Intercom Secret Key');
		}

		return new Forms\FieldList(
			$appIDField,
			$secretKeyField,
			new Forms\CheckboxField('AnonymousAccess', 'Allow anonymous access?')
		);
	}

	public function getSnippets(array $params) {
		$member = Security::getCurrentUser();

		if (!$this->isEnabled($member, $params)) {
			return [];
		}

		$data = new ArrayData([
			'IntercomSettingsJSON' => DBField::create_field(
				'HTMLFragment',
				json_encode($this->getIntercomSettings($member, $params))
			)
		]);

		return [
			'end-body' => $data->renderWith('IntercomScriptTags'),
		];
	}

	/**
	 * Return the Intercom settings an array.
	 * Extendable by adding extensions with updateIntercomSettings().
	 *
	 * @param Member $member - if not provided, it will try to Member::currentUser();
	 *
	 * @return array The settings, ready for JSON-encoding
	 */
	public function getIntercomSettings(Member $member = null, $params = []) {
		if (!$this->isEnabled($member, $params)) {
			return [];
		}

		$settings = [
			'app_id' => getenv('INTERCOM_APP_ID') ?: $params['AppID'],
		];

		if (!$member) {
			$member = Member::currentUser();
		}

		if ($member) {
			$settings['name'] = trim($member->FirstName . ' ' . $member->Surname);
			$settings['email'] = $member->Email;
			$settings['created_at'] = trim($member->FirstName . ' ' . $member->Surname);
			$settings['created_at'] = $member->obj('Created')->Format('U');

			foreach((array)Config::inst()->get('Intercom', 'user_fields') as $intercomKey => $propertyName) {
				$settings[$intercomKey] = $member->$propertyName;
			}

			$secret = getenv('INTERCOM_SECRET_KEY') ?: $params['SecretKey'];

			if($secret) {
				$settings['user_hash'] = $this->generateUserHash($secret, $member->Email);
			} else {
				$settings['user_id'] = $member->ID;
			}

			if($prop = Config::inst()->get('Intercom', 'company_property')) {
				$org = $member->$prop;
				if($org) {
					$settings['company']['id'] = $org->ID;
					$settings['company']['created_at'] = $org->obj('Created')->Format('U');

					foreach((array)Config::inst()->get('Intercom', 'company_fields') as $intercomKey => $propertyName) {
						$settings['company'][$intercomKey] = $org->$propertyName;
					}
				}
			}
		};

		return $settings;
	}

	protected function isEnabled(Member $member = null, array $params) {
		return $member || !empty($params['AnonymousAccess']);
	}


	protected function generateUserHash($secret, $identifier) {
		return hash_hmac("sha256", $identifier, $secret);
	}

}
