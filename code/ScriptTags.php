<?php

namespace SilverStripe\Addon\Intercom;

use HTMLText;
use Member;
use stdClass;
use ViewableData;

class ScriptTags extends ViewableData
{
    /**
     * @var bool
     */
    private static $enabled = true;

    /**
     * @param null|Member $member
     *
     * @return bool
     */
    public function isEnabled(Member $member = null)
    {
        if (!defined("INTERCOM_APP_ID")) {
            return false;
        }

        /** @var stdClass $config */
        $config = $this->config();

        if (!$config->enabled) {
            return false;
        }

        if (!$member) {
            $member = Member::currentUserID();
        }

        if (!$config->anonymous_access && !$member) {
            return false;
        }

        return true;
    }

    /**
     * Return the Intercom settings an array. Extensions can define updateIntercomSettings.
     *
     * @param null|Member $member
     *
     * @return array
     */
    public function getIntercomSettings(Member $member = null)
    {
        if (!$this->isEnabled($member)) {
            return [];
        }

        $settings = [
            "app_id" => INTERCOM_APP_ID,
        ];

        if (!$member) {
            $member = Member::currentUser();
        }

        if ($member) {
            $settings += [
                "name" => trim($member->FirstName . " " . $member->Surname),
                "email" => $member->Email,
                "created_at" => $member->obj("Created")->Format("U"),
            ];

            /** @var stdClass $config */
            $config = $this->config();

            foreach ((array)$config->user_fields as $intercomKey => $propertyName) {
                $settings[$intercomKey] = $member->$propertyName;
            }

            if (defined("INTERCOM_SECRET_KEY")) {
                $settings["user_hash"] = $this->generateUserHash($member->Email);
            } else {
                $settings["user_id"] = $member->ID;
            }

            if ($config->company_property) {
                $company = $member->{$config->company_property};

                if ($company) {
                    $settings["company"]["id"] = $company->ID;
                    $settings["company"]["created_at"] = $company->obj("Created")->Format("U");

                    foreach ((array)$config->company_fields as $intercomKey => $propertyName) {
                        $settings["company"][$intercomKey] = $company->$propertyName;
                    }
                }
            }
        };

        $this->extend("updateIntercomSettings", $settings);

        return $settings;
    }

    /**
     * @param string $identifier
     *
     * @return null|string
     */
    public function generateUserHash($identifier)
    {
        if (defined("INTERCOM_SECRET_KEY")) {
            return hash_hmac("sha256", $identifier, INTERCOM_SECRET_KEY);
        }
    }

    /**
     * @return string
     */
    public function IntercomSettingsJSON()
    {
        return json_encode($this->getIntercomSettings());
    }

    /**
     * @return null|HTMLText
     */
    public function forTemplate()
    {
        if (!$this->isEnabled()) {
            return null;
        }

        return $this->renderWith("IntercomScriptTags");
    }
}
