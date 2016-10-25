<?php

namespace SilverStripe\Intercom\Model;

use SilverStripe\ORM\DataObject;

/**
 * @property int IntercomID
 * @property int LeadID
 */
class Conversation extends DataObject
{
    /**
     * @var string
     */
    private static $singular_name = "Conversation";

    /**
     * @var string
     */
    private static $plural_name = "Conversations";

    /**
     * @var array
     */
    private static $db = [
        "IntercomID" => "Varchar(32)",
    ];

    /**
     * @var array
     */
    private static $has_one = [
        "Lead" => "Lead",
    ];
}
