<?php

namespace SilverStripe\Intercom\Model;

use DataObject;

/**
 * @property int $IntercomID
 * @property bool $IsAssigned
 * @method Conversation[] Conversations()
 */
class Lead extends DataObject
{
    /**
     * @var string
     */
    private static $singular_name = "Lead";

    /**
     * @var string
     */
    private static $plural_name = "Leads";

    /**
     * @var array
     */
    private static $db = [
        "IntercomID" => "Varchar(32)",
        "IsAssigned" => "Boolean(0)",
    ];

    /**
     * @var array
     */
    private static $has_many = [
        "Conversations" => "Conversation",
    ];
}
