<?php

namespace SilverStripe\Intercom\Admin;

use ModelAdmin as BaseModelAdmin;

class ModelAdmin extends BaseModelAdmin
{
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
        "SilverStripe\\Intercom\\Model\\Route",
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
