<?php

namespace SilverStripe\Intercom\Model;

use DataObject;

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
        "IntercomID" => "Int",
        "IsAssigned" => "Boolean(0)",
    ];
}
