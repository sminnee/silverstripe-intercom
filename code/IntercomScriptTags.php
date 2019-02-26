<?php

namespace SilverStripe\Intercom;

use SilverStripe\Core\Convert;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use SilverStripe\View\ViewableData;

/**
 * Generates the IntercomScriptTags.
 * Place into a template; forTemplate() returns the tag
 */
class IntercomScriptTags extends ViewableData
{
    /**
     * @var bool
     */
    private static $enabled = true;

    /**
     * @param  Member $member
     * @return bool
     */
    public function isEnabled(Member $member = null)
    {
        if (!Intercom::getSetting('INTERCOM_APP_ID')) {
            return false;
        }

        if (!$this->config()->enabled) {
            return false;
        }

        if ($this->config()->always_anonymous) {
            return true;
        }

        if (!$member) {
            $member = Security::getCurrentUser();
        }

        if (!$this->config()->anonymous_access && !$this->config()->always_anonymous && !$member) {
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
    public function getIntercomSettings(Member $member = null)
    {
        if (!$this->isEnabled($member)) {
            return [];
        }

        $settings = [
            'app_id' => Intercom::getSetting('INTERCOM_APP_ID'),
        ];

        // always_anonymous prevents the use of $member
        if ($this->config()->always_anonymous) {
            $member = null;
        } elseif (!$member) {
            $member = Security::getCurrentUser();
        }

        if ($member) {
            $settings['name'] = trim($member->FirstName . ' ' . $member->Surname);
            $settings['email'] = $member->Email;
            $settings['created_at'] = trim($member->FirstName . ' ' . $member->Surname);
            $settings['created_at'] = $member->obj('Created')->Format('U');

            foreach ((array)$this->config()->user_fields as $intercomKey => $propertyName) {
                $settings[$intercomKey] = $member->$propertyName;
            }

            if (Intercom::getSetting('INTERCOM_SECRET_KEY')) {
                $settings['user_hash'] = $this->generateUserHash($member->Email);
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
    public function generateUserHash($identifier)
    {
        if ($secret = Intercom::getSetting('INTERCOM_SECRET_KEY')) {
            return hash_hmac("sha256", $identifier, $secret);
        }
    }

    /**
     * @return string JSON
     */
    public function IntercomSettingsJSON()
    {
        return Convert::raw2json($this->getIntercomSettings());
    }

    public function forTemplate()
    {
        if (!$this->isEnabled()) {
            return null;
        }

        return $this->renderWith(__CLASS__);
    }
}
