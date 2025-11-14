<?php

namespace NSWDPC\Elemental\Models\Curator;

use DNADesign\Elemental\Models\BaseElement;
use SilverStripe\Forms\DropdownField;
use SilverStripe\View\Requirements;
use SilverStripe\ORM\ValidationException;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\View\ArrayData;

/**
 * Provides a Curator Widget Element
 *
 * Editors select an existing Curator feed record to display
 *
 * Feed curation occurs in the Curator.io administration area at app.curator.io
 *
 * @author James Ellis
 */
class ElementCuratorFeedWidget extends BaseElement {

    /**
     * @inheritdoc
     */
    private static $table_name = 'ElementCuratorFeedWidget';

    /**
     * @inheritdoc
     */
    private static $icon = 'font-icon-block-carousel';

    /**
     * @inheritdoc
     */
    private static $inline_editable = true;

    /**
     * @inheritdoc
     */
    private static $singular_name = 'Curator.io feed widget';

    /**
     * @inheritdoc
     */
    private static $plural_name = 'Curator.io feed widgets';

    /**
     * @inheritdoc
     */
    private static $title = 'Curator.io feed widget';

    /**
     * @inheritdoc
     */
    private static $description = 'Display a published feed from Curator.io';

    /**
     * If you have a free Curator.io account this message must be included
     * @var boolean
     */
    private static $include_powered_by = true;

    /**
     * @inheritdoc
     */
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
        $feed = $this->CuratorFeedRecord();
        return _t(
            __CLASS__ . '.FEED_TITLE',
            "Curator.io feed {feedid}",
            [
                'feedid' => isset($feed->CuratorFeedId) ? $feed->CuratorFeedId : _t(__CLASS__ . '.NO_FEED_ID', '(no feed id)')
            ]
        );
    }

    /**
     * Render this element with the Curator Feed record
     */
    public function forTemplate($holder = true)
    {
        // Ensure the element values are used for rendering
        $feed = $this->CuratorFeedRecord();
        if($feed) {
            $data = ArrayData::create([
                'Title' => $this->Title,
                'ShowTitle' => $this->ShowTitle,
            ]);
            $feed->supplyRequirements();
            return $feed->customise($data)->renderWith( self::class );
        }
        return '';
    }

    /**
     * @inheritdoc
     */
    public function getCMSFields() {
        $fields = parent::getCMSFields();
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
