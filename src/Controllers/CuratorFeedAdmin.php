<?php

namespace NSWDPC\Elemental\Controllers\Curator;

use NSWDPC\Elemental\Models\Curator\CuratorFeed;
use SilverStripe\Admin\ModelAdmin;

/**
 * Curator Feed Admin, for editing/creating/deleting feeds
 *
 * @author james.ellis@dpc.nsw.gov.au
 */
class CuratorFeedAdmin extends ModelAdmin
{
    private static $managed_models = [
        CuratorFeed::class
    ];

    private static $menu_title = 'Curator.io';

    private static $menu_icon_class = 'font-icon-block-carousel';

    private static $url_segment = 'curatorio';
}
