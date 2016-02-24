<?php

namespace SilverStripe\Intercom\Model;

use DataObject;
use Member;
use Permission;
use PermissionProvider;

class Team extends DataObject implements PermissionProvider
{
    /**
     * @var string
     */
    private static $singular_name = "Team";

    /**
     * @var string
     */
    private static $plural_name = "Teams";

    /**
     * @var array
     */
    private static $db = [
        "Type" => "Varchar(32)",
        "Name" => "Varchar(255)",
        "Email" => "Varchar(255)",
        "IntercomID" => "Int",
    ];

    /**
     * @var array
     */
    private static $belongs_many_many = [
        "Routes" => "Route.Teams",
    ];

    /**
     * @inheritdoc
     *
     * @param null|Member $member
     *
     * @return bool
     */
    public function canCreate($member = null)
    {
        return false;
    }

    /**
     * @inheritdoc
     *
     * @param null|Member $member
     *
     * @return bool
     */
    public function canDelete($member = null)
    {
        return false;
    }

    /**
     * @inheritdoc
     *
     * @param null|Member $member
     *
     * @return bool
     */
    public function canEdit($member = null)
    {
        return Permission::check("EDIT_INTERCOM_TEAMS");
    }

    /**
     * @inheritdoc
     *
     * @param null|Member $member
     *
     * @return bool
     */
    public function canView($member = null)
    {
        return Permission::check("VIEW_INTERCOM_TEAMS");
    }

    /**
     * @inheritdoc
     *
     * @return array
     */
    public function providePermissions()
    {
        return [
            "EDIT_INTERCOM_TEAMS" => "Edit Intercom teams",
            "VIEW_INTERCOM_TEAMS" => "View Intercom teams",
        ];
    }
}
