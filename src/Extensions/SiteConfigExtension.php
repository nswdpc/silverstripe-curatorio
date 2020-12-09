<?php

namespace NSWDPC\Elemental\Extensions\Curator;

use NSWDPC\Elemental\Models\Curator\CuratorFeed;
use Silverstripe\ORM\DataExtension;
use Silverstripe\Forms\FieldList;
use Silverstripe\Forms\DropdownField;

/**
 * Provide content administrators the ability to select a global site social feed
 * from a list of configured feeds
 * @author James
 */
class SiteConfigExtension extends DataExtension
{

    private static $has_one = [
        'CuratorFeedRecord' => CuratorFeed::class
    ];

    public function updateCmsFields(FieldList $fields)
    {
        $fields->addFieldsToTab(
            'Root.Social',
            [
                DropdownField::create(
                    'CuratorFeedRecordID',
                    _t(__CLASS__. '.SELECT_CURATOR_FEED', 'Select a global Curator.io feed'),
                    CuratorFeed::get()->map('ID', 'Title')
                )->setEmptyString('')
            ]
        );
    }

}
