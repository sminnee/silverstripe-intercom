<?php

namespace SilverStripe\Intercom;

use Config;
use DataList;
use Injector;
use Intercom\IntercomBasicAuthClient;
use InvalidArgumentException;
use LogicException;
use Member;
use SS_List;
use stdClass;

class Client
{
    /**
     * @var string
     */
    private $apiKey;

    /**
     * @var string
     */
    private $appId;

    /**
     * @var IntercomBasicAuthClient
     */
    private $client;

    public function __construct()
    {
        if (defined("INTERCOM_API_KEY")) {
            $this->apiKey = INTERCOM_API_KEY;
        }
        if (defined("INTERCOM_APP_ID")) {
            $this->appId = INTERCOM_APP_ID;
        }
    }

    /**
     * @return string
     */
    public function getApiKey()
    {
        if (!$this->apiKey) {
            throw new LogicException("Intercom API key not set! Define INTERCOM_API_KEY or use Injector to set ApiKey");
        }

        return $this->apiKey;
    }

    /**
     * @param string $apiKey
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * @return string
     */
    public function getAppId()
    {
        if (!$this->appId) {
            throw new LogicException("Intercom App ID not set! Define INTERCOM_APP_ID or use Injector to set AppId");
        }

        return $this->appId;
    }

    /**
     * @param string $appId
     */
    public function setAppId($appId)
    {
        $this->appId = $appId;
    }


    /**
     * @return IntercomBasicAuthClient
     */
    public function getClient()
    {
        if (!$this->client) {
            $this->client = IntercomBasicAuthClient::factory([
                "app_id" => $this->getAppId(),
                "api_key" => $this->getApiKey(),
            ]);
        }

        return $this->client;
    }


    /**
     * Return a list of all users for this application. Defined via the "user_list" project, which
     * should be "%$" followed by an Injector service name.
     *
     * @return DataList
     */
    public function getUserList()
    {
        /** @var stdClass $config */
        $config = $this->config();

        if ($list = $config->user_list) {
            if (substr($list, 0, 2) != "%\$") {
                throw new InvalidArgumentException("Please set user_list to a string of the form %\$ServiceName");
            }

            return Injector::inst()->get(substr($list, 2));
        }

        return Member::get();
    }

    /**
     * @return stdClass
     */
    private function config()
    {
        return Config::inst()->forClass(get_class($this));
    }

    /**
     * Bulk load a set of members using the same meta-data rules as if they were to log in.
     *
     * @param SS_List $members
     *
     * @return BulkJob
     */
    public function bulkLoadUsers(SS_List $members)
    {
        $items = [];

        /** @var stdClass $config */
        $config = $this->config();

        $userFields = $config->user_fields;
        $companyFields = $config->company_fields;

        $scriptTags = new ScriptTags();

        foreach ($members as $member) {
            $settings = $scriptTags->getIntercomSettings($member);

            unset($settings["app_id"]);
            unset($settings["user_hash"]);

            foreach ($settings as $key => $value) {
                if (!in_array($key, $userFields)) {
                    $settings["custom_attributes"][$key] = $value;
                    unset($settings[$key]);
                }
            }

            if (isset($settings["company"])) {
                foreach ($settings["company"] as $key => $value) {
                    if (!in_array($key, $companyFields)) {
                        $settings["company"]["custom_attributes"][$key] = $value;
                        unset($settings["company"][$key]);
                    }
                }
            }

            $items[] = [
                "data_type" => "user",
                "method" => "post",
                "data" => $settings,
            ];
        }

        $result = $this->getClient()->bulkUsers(["items" => $items]);

        return $this->getBulkJob($result->get("id"));
    }


    /**
     * Return an IntercomBulkJob object for the given job.
     *
     * @param string $id
     *
     * @return BulkJob
     */
    public function getBulkJob($id)
    {
        return new BulkJob($this->getClient(), $id);
    }

    /**
     * Track an event with the current user.
     *
     * @param string $eventName
     * @param array $eventData
     * @param null|Member $member
     */
    public function trackEvent($eventName, $eventData = [], Member $member = null)
    {
        $payload = [
            "event_name" => $eventName,
            "created_at" => time(),
        ];

        $scriptTags = new ScriptTags();
        $settings = $scriptTags->getIntercomSettings($member);

        if (empty($settings["email"]) && empty($settings["user_id"])) {
            throw new LogicException("Can't track event when no user logged in");
        }

        if (!empty($settings["email"])) {
            $payload["email"] = $settings["email"];
        }

        if (!empty($settings["user_id"])) {
            $payload["user_id"] = $settings["user_id"];
        }

        if ($eventData) {
            $payload["metadata"] = $eventData;
        }

        $this->getClient()->createEvent($payload);
    }
}
