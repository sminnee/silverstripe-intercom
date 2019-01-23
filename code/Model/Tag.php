<?php

namespace SilverStripe\Intercom\Model;

use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\Security\PermissionProvider;

class Tag extends DataObject implements PermissionProvider
{
    /**
     * @var string
     */
    private static $singular_name = "Tag";

    /**
     * @var string
     */
    private static $plural_name = "Tags";

    /**
     * @var array
     */
    private static $db = [
        "Type" => "Varchar(32)",
        "Name" => "Varchar(255)",
        "IntercomID" => "Int",
    ];

    /**
     * @inheritdoc
     *
     * @param null|Member $member
     *
     * @return bool
     */
    public function canCreate($member = null, $context = [])
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
        return Permission::check("EDIT_INTERCOM_TAGS");
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
        return Permission::check("VIEW_INTERCOM_TAGS");
    }

    /**
     * @inheritdoc
     *
     * @return array
     */
    public function providePermissions()
    {
        return [
            "EDIT_INTERCOM_TAGS" => "Edit Intercom tags",
            "VIEW_INTERCOM_TAGS" => "View Intercom tags",
        ];
    }
}
