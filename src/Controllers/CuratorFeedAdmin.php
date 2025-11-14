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
    /**
     * @inheritdoc
     */
    private static array $managed_models = [
        CuratorFeed::class
    ];

    /**
     * @inheritdoc
     */
    private static string $menu_title = 'Curator.io';

    /**
     * @inheritdoc
     */
    private static string $menu_icon_class = 'font-icon-block-carousel';

    /**
     * @inheritdoc
     */
    private static string $url_segment = 'curatorio';
}
