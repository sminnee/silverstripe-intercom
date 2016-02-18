<?php

namespace SilverStripe\Addon\Intercom\Model;

use DataObject;

class Team extends DataObject
{
    /**
     * @var array
     */
    private static $db = array(
        "Type" => "Varchar(32)",
        "Name" => "Varchar(255)",
        "Email" => "Varchar(255)",
        "IntercomID" => "Int",
    );
}
