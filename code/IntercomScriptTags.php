<?php

namespace SilverStripe\Intercom;

use SilverStripe\Core\Convert;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;

use SilverStripe\TagManager\SnippetProvider;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms;
use SilverStripe\View\ArrayData;

/**
 * Generates the IntercomScriptTags.
 * Place into a template; forTemplate() returns the tag
 */
class IntercomScriptTags implements SnippetProvider
{

    use Extensible;
    use Configurable;

    public function getTitle()
    {
        return "Intercom chat";
    }

    public function getParamFields()
    {
        return new FieldList(
            new Forms\TextField("AppId", "App ID"),
            new Forms\TextField("SecretKey", "Secret Key"),
            new Forms\DropdownField("AnonymousAccess", "Anonymous Access", [
                'disabled' => 'Disabled (logged-in members only)',
                'allowed' => 'Allowed',
                'forced' => 'Forced (logged-in member will be ignored)',
            ], 'allowed')
        );
    }

    public function getSummary(array $params)
    {
        if (!empty($params['AppId'])) {
            return $this->getTitle() . " -  App ID " . $params['AppId'];
        } else {
            return $this->getTitle();
        }
    }

    public function getSnippets(array $params)
    {
        if (empty($params['AppId'])) {
            throw new \InvalidArgumentException("Please supply App ID");
        }

        if (!$this->isEnabled($params)) {
            return [];
        }


        $snippet = (new ArrayData([
            'IntercomSettingsJSON' => Convert::raw2json($this->getIntercomSettings($params)),
        ]))->renderWith(__CLASS__);

        return [
            'end-body' => $snippet,
        ];
    }

    /**
     * @param  Member $member
     * @return bool
     */
    public function isEnabled(array $params, Member $member = null)
    {
        if (!$member) {
            $member = Security::getCurrentUser();
        }

        // If there's no member and anonymous access isn't allowed, don't let the person in
        if (isset($params['AnonymousAccess']) && $params['AnonymousAccess'] === 'disabled' && !$member) {
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
    protected function getIntercomSettings(array $params, Member $member = null)
    {

        if (!$this->isEnabled($params, $member)) {
            return [];
        }

        $settings = [
            'app_id' => $params['AppId'],
        ];

        // always_anonymous prevents the use of $member
        if (isset($params['AnonymousAccess']) && $params['AnonymousAccess'] === 'forced') {
            $member = null;
        } elseif (!$member) {
            $member = Security::getCurrentUser();
        }

        if ($member) {
            $settings['name'] = trim($member->FirstName . ' ' . $member->Surname);
            $settings['email'] = $member->Email;
            $settings['created_at'] = trim($member->FirstName . ' ' . $member->Surname);
            $settings['created_at'] = $member->obj('Created')->Format('U');

            // TO DO
            // foreach ((array)$this->config()->user_fields as $intercomKey => $propertyName) {
            //     $settings[$intercomKey] = $member->$propertyName;
            // }

            if (!empty($params['SecretKey'])) {
                $settings['user_hash'] = $this->generateUserHash($member->Email, $params['SecretKey']);
            } else {
                $settings['user_id'] = $member->ID;
            }

            if ($this->config()->company_property) {
                $prop = $this->config()->company_property;
                $org = $member->$prop;
                if ($org) {
                    $settings['company']['id'] = $org->ID;
                    $settings['company']['created_at'] = $org->obj('Created')->Format('U');

                    foreach ((array)$this->config()->company_fields as $intercomKey => $propertyName) {
                        $settings['company'][$intercomKey] = $org->$propertyName;
                    }
                }
            }
        };

        $this->extend('updateIntercomSettings', $settings);
        return $settings;
    }

    /**
     * @todo Needs to use the token instead now, will that affect existing hashes?
     *
     * @param  string $identifier
     * @return string|null
     */
    public function generateUserHash($identifier, $secret)
    {
        return hash_hmac("sha256", $identifier, $secret);
    }

}
