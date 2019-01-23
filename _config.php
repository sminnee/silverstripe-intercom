<?php

use SilverStripe\Core\Config\Config;
use SilverStripe\Admin\CMSMenu;

if (Config::inst()->get("SilverStripe\\Intercom\\ModelAdmin", "hidden")) {
    CMSMenu::remove_menu_item("SilverStripe-Intercom-ModelAdmin");
}
