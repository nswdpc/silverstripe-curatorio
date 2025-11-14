<?php

namespace NSWDPC\Elemental\Extensions\Curator;

use NSWDPC\Elemental\Models\Curator\CuratorFeed;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\DropdownField;

/**
 * Provide content administrators the ability to select a global site social feed
 * from a list of configured feeds
 * @author James
 * @property int $CuratorFeedRecordID
 * @method \NSWDPC\Elemental\Models\Curator\CuratorFeed CuratorFeedRecord()
 * @extends \SilverStripe\Core\Extension<(\SilverStripe\SiteConfig\SiteConfig & static)>
 */
class SiteConfigExtension extends Extension
{
    /**
     * @inheritdoc
     */
    private static array $has_one = [
        'CuratorFeedRecord' => CuratorFeed::class
    ];

    /**
     * @inheritdoc
     */
    public function updateCmsFields(FieldList $fields)
    {
        $fields->addFieldsToTab(
            'Root.Social',
            [
                DropdownField::create(
                    'CuratorFeedRecordID',
                    _t(self::class. '.SELECT_CURATOR_FEED', 'Select a global Curator.io feed'),
                    CuratorFeed::get()->map('ID', 'Title')
                )->setEmptyString('')
            ]
        );
    }

    /**
     * To add the field to your own SiteConfig extension, call this method
     * e.g $this->owner->getSocialFeedSelector()
     */
    public function getSocialFeedSelector(): DropdownField
    {
        return DropdownField::create(
            'CuratorFeedRecordID',
            _t(self::class. '.SELECT_CURATOR_FEED', 'Select a global Curator.io feed'),
            CuratorFeed::get()->map('ID', 'Title')
        )->setEmptyString('');
    }

    /**
     * @return CuratorFeed|null
     */
    public function getSocialFeedRecord()
    {
        return $this->getOwner()->CuratorFeedRecord();
    }

}
