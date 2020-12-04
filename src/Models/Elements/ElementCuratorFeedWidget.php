<?php

namespace NSWDPC\Elemental\Models\Curator;

use DNADesign\Elemental\Models\BaseElement;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\TextareaField;
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

    private static $db = [
        'CuratorFeedId' => 'Varchar(255)',
        'CuratorContainerId' => 'Varchar(255)',
        'FeedDescription' => 'Text'
    ];

    /**
     * Elemental Type value
      * @return string
     */
    public function getType()
    {
        return _t(__CLASS__ . '.BlockType', 'Curator.io Feed Widget');
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        if($this->exists()) {
            if(empty($this->CuratorFeedId)) {
                throw new ValidationException(
                    _t(__CLASS__ . ".NO_FEED_ID","Please provide a Curator.io Feed Id")
                );
            }

            if(empty($this->CuratorContainerId)) {
                throw new ValidationException(
                    _t(__CLASS__ . ".NO_FEED_ID","Please provide a Curator.io Container Id")
                );
            }

        }
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
     * Free accounts require this text in the template.
     * You can turn this off in project conifiguration
     * @return bool
     */
    public function IncludePoweredBy() {
        return $this->config()->get('include_powered_by');
    }

    public function getCustomFeedScript() {
        return $this->renderWith("NSWDPC/Elemental/Models/Curator/FeedScript");
    }

    /**
     * Apply requirements when templating
     */
    public function forTemplate($holder = true)
    {
        // Avoid adding requirements multiple times
        if(!$this->_cache_is_rendered) {
            // add the requirements for this feed
            Requirements::customScript(
                $this->getCustomFeedScript(),
                "curator_feed_{$this->CuratorFeedId}" // uniqueness
            );
        }
        $this->_cache_is_rendered =  true;
        return parent::forTemplate($holder);
    }

    public function getCMSValidator()
    {
        return new RequiredFields([
            'CuratorFeedId','CuratorContainerId'
        ]);
    }

    /**
     * Apply edit fields for the element administration area
     * @return Fieldlist
     */
    public function getCMSFields() {
        $fields = parent::getCMSFields();
        $fields->addFieldsToTab(
            'Root.Main',
            [

                TextField::create(
                    'CuratorFeedId',
                    'Curator.io Feed Id'
                )->setDescription(
                    _t(
                        __CLASS__ . '.CURATOR_FEED_ID_DESCRIPTION',
                        "This is the 'Feed ID' value found in the 'Style' section"
                    )
                )->setAttribute('required','required'),

                TextField::create(
                    'CuratorContainerId',
                    'Curator.io Container Id'
                )->setDescription(
                    _t(
                        __CLASS__ . '.CURATOR_FEED_ID_DESCRIPTION',
                        "This is the 'Container ID' value found in the 'Style &gt; Advanced' section"
                    )
                )->setAttribute('required','required'),

                TextareaField::create(
                    'FeedDescription',
                    'Feed description'
                )

            ]
        );
        return $fields;
    }

}
