<?php

namespace SilverStripe\Intercom;

use ModelAdmin as BaseModelAdmin;

class ModelAdmin extends BaseModelAdmin
{
    /**
     * Whether or not to hide this admin menu item.
     *
     * @var bool
     *
     * @config
     */
    private static $hidden = true;

    /**
     * @var string
     */
    private static $menu_icon = "silverstripe-intercom/img/menu-icon.png";

    /**
     * @var array
     */
    private static $managed_models = array(
        "SilverStripe\\Intercom\\Model\\Tag",
        "SilverStripe\\Intercom\\Model\\Team",
    );

    /**
     * @var string
     */
    private static $url_segment = "intercom";

    /**
     * @var string
     */
    private static $menu_title = "Intercom";

    /**
     * @var bool
     */
    public $showImportForm = false;
}
