<?php

namespace NSWDPC\Elemental\Models\Curator;

use DNADesign\Elemental\Models\BaseElement;
use Silverstripe\Forms\DropdownField;
use SilverStripe\View\Requirements;
use SilverStripe\ORM\ValidationException;
use SilverStripe\Forms\RequiredFields;

/**
 * Provides a Curator Widget Element
 * Editors provide a Curator Feed ID and Container ID of a published feed
 * Feed curation occurs in the Curator.io administration area
 * @author James Ellis
 */
class ElementCuratorFeedWidget extends BaseElement {

    private static $table_name = 'ElementCuratorFeedWidget';

    private static $icon = 'font-icon-code';

    private static $inline_editable = true;

    private static $singular_name = 'Curator.io feed widget';
    private static $plural_name = 'Curator.io feed widgets';

    private static $title = 'Curator.io feed widget';
    private static $description = 'Display a published feed from Curator.io';

    private $_cache_is_rendered = false;

    /**
     * If you have a free Curator.io account this message must be included
     * @var boolean
     */
    private static $include_powered_by = true;

    /**
     * These values are deprecated and will be removed
     * in a later update
     * CuratorFeed::requireDefaultRecords migrates them to CuratorFeed records
     */
    private static $db = [
        'CuratorFeedId' => 'Varchar(255)',
        'CuratorContainerId' => 'Varchar(255)',
        'FeedDescription' => 'Text'
    ];

    private static $has_one = [
        'CuratorFeedRecord' => CuratorFeed::class,
    ];

    /**
     * Elemental Type value
      * @return string
     */
    public function getType()
    {
        return _t(__CLASS__ . '.BlockType', 'Curator.io Feed Widget');
    }

    /**
     * Return a nicer anchor title
     * @return string
     */
    public function getAnchorTitle() {
        return _t(
            __CLASS__ . '.FEED_TITLE',
            "Curator.io feed {feedid}",
            [
                'feedid' => $this->CuratorFeedId
            ]
        );
    }

    /**
     * Render with the Curator Feed record
     */
    public function forTemplate($holder = true)
    {
        $feed = $this->CuratorFeedRecord();
        if($feed) {
            return $feed->forTemplate();
        }
        return '';
    }

    /**
     * Apply edit fields for the element administration area
     * @return Fieldlist
     */
    public function getCMSFields() {
        $fields = parent::getCMSFields();

        $fields->removeByName(['CuratorFeedId','CuratorContainerId','FeedDescription']);

        $fields->addFieldsToTab(
            'Root.Main',
            [
                DropdownField::create(
                    'CuratorFeedRecordID',
                    _t(__CLASS__. '.SELECT_CURATOR_FEED', 'Select a Curator.io feed'),
                    CuratorFeed::get()->map('ID', 'Title')
                )->setEmptyString('')
            ]
        );
        return $fields;
    }

}
