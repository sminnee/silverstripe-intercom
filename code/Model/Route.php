<?php

namespace SilverStripe\Intercom\Model;

use DataObject;
use Member;
use Permission;
use PermissionProvider;

class Route extends DataObject implements PermissionProvider
{
    /**
     * @var string
     */
    private static $singular_name = "Route";

    /**
     * @var string
     */
    private static $plural_name = "Routes";

    /**
     * @var array
     */
    private static $db = [
        "Pattern" => "Varchar(255)",
        "Priority" => "Int",
    ];

    /**
     * @var array
     */
    private static $summary_fields = [
        "Pattern" => "Pattern",
        "Priority" => "Priority",
        "Tags.Count" => "Tags",
        "Teams.Count" => "Teams",
    ];

    /**
     * @var string
     */
    private static $default_sort = "Priority";

    /**
     * @var array
     */
    private static $many_many = [
        "Tags" => "SilverStripe\\Intercom\\Model\\Tag",
        "Teams" => "SilverStripe\\Intercom\\Model\\Team",
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
        return Permission::check("CREATE_INTERCOM_ROUTES");
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
        return Permission::check("DELETE_INTERCOM_ROUTES");
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
        return Permission::check("EDIT_INTERCOM_ROUTES");
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
        return Permission::check("VIEW_INTERCOM_ROUTES");
    }

    /**
     * @inheritdoc
     *
     * @return array
     */
    public function providePermissions()
    {
        return [
            "CREATE_INTERCOM_ROUTES" => "Create Intercom routes",
            "DELETE_INTERCOM_ROUTES" => "Delete Intercom routes",
            "EDIT_INTERCOM_ROUTES" => "Edit Intercom routes",
            "VIEW_INTERCOM_ROUTES" => "View Intercom routes",
        ];
    }
}
