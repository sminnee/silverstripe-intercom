<?php

if (Config::inst()->get("SilverStripe\\Intercom\\ModelAdmin", "hidden")) {
    CMSMenu::remove_menu_item("SilverStripe-Intercom-ModelAdmin");
}
