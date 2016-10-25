<?php

namespace SilverStripe\Intercom;

use SilverStripe\Admin\ModelAdmin as BaseModelAdmin;

if (!class_exists(BaseModelAdmin::class)) {
    return;
}

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
