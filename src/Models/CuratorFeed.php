<?php

namespace NSWDPC\Elemental\Models\Curator;

use SilverStripe\Forms\TextField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\View\Requirements;
use SilverStripe\ORM\ValidationException;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\Security\PermissionProvider;
use SilverStripe\Security\Permission;
use SilverStripe\View\ArrayData;

/**
 * Allows a curator feed to be configured
 * Editors provide a Curator Feed ID and Container ID of a published feed
 * Feed curation occurs in the Curator.io administration area
 * @author James Ellis
 */
class CuratorFeed extends DataObject implements PermissionProvider {

    private static $table_name = 'CuratorFeed';
    private static $singular_name = 'Curator.io feed';
    private static $plural_name = 'Curator.io feeds';

    /**
     * If you have a free Curator.io account this message must be included
     * @var boolean
     */
    private static $include_powered_by = true;

    private static $db = [
        'Title' => 'Varchar(255)',
        'CuratorFeedId' => 'Varchar(255)',
        'CuratorContainerId' => 'Varchar(255)',
        'CuratorFeedDescription' => 'Text'
    ];

    private static $indexes = [
        'CuratorFeedId' => true,
        'CuratorContainerId' => true,
    ];

    private static $summary_fields = [
        'Title' => 'Title',
        'CuratorFeedId' => 'Curator Feed ID',
        'CuratorContainerId' => 'Curator Container ID',
        'CuratorFeedDescription' => 'Description'
    ];

    private static $searchable_fields = [
        'Title' => 'PartialMatchFilter',
        'CuratorFeedId' => 'PartialMatchFilter',
        'CuratorContainerId' => 'PartialMatchFilter',
        'CuratorFeedDescription' => 'PartialMatchFilter'
    ];

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
     * Upgrade elements to use Curator feed
     */
    public function requireDefaultRecords() {
        $elements = ElementCuratorFeedWidget::get();
        foreach($elements as $element) {
            if($element->CuratorFeedId && $element->CuratorContainerId) {
                $record = [
                    'CuratorFeedId' => $element->CuratorFeedId,
                    'CuratorContainerId' => $element->CuratorContainerId
                ];
                $feed = CuratorFeed::get()->filter($record)->first();
                if(empty($feed->ID)) {
                    // create a feed
                    $feed = CuratorFeed::create( $record );
                    $feed->CuratorFeedDescription = $element->FeedDescription;
                    $feed_id = $feed->write();
                    if($feed_id) {
                        $element->CuratorFeedRecordID = $feed_id;
                        $element->CuratorFeedId = '';
                        $element->CuratorContainerId = '';
                        $element->FeedDescription = '';
                        $element->write();
                        DB::alteration_message("Moved element #{$element->ID} to Curator Feed #{$feed_id}","changed");
                    }
                }
            }
        }
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

    public function supplyRequirements() {
        // Avoid adding requirements multiple times
        if(!$this->_cache_is_rendered) {
            // add the requirements for this feed
            Requirements::customScript(
                $this->getCustomFeedScript(),
                "curator_feed_{$this->CuratorFeedId}" // uniqueness
            );
        }
        $this->_cache_is_rendered =  true;
    }

    /**
     * Apply requirements when templating
     */
    public function forTemplate($holder = true)
    {
        $this->supplyRequirements();
        return $this->renderWith(ElementCuratorFeedWidget::class);
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
                    'Title',
                    _t(__CLASS__. '.CURATOR_TITLE', 'Title'),
                )->setDescription(
                    _t(
                        __CLASS__ . '.CURATOR_TITLE_DESCRIPTION',
                        "A title used to describe the feed"
                    )
                ),

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
                    'CuratorFeedDescription',
                    _t(
                        __CLASS__ . '.CURATOR_FEED_DESCRIPTION',
                        "Feed description"
                    )
                )

            ]
        );
        return $fields;
    }

    public function canView($member = null)
    {
        return Permission::checkMember($member, 'CURATOR_FEED_VIEW');
    }

    public function canCreate($member = null, $context = [])
    {
        return Permission::checkMember($member, 'CURATOR_FEED_CREATE');
    }

    public function canEdit($member = null)
    {
        return Permission::checkMember($member, 'CURATOR_FEED_EDIT');
    }

    public function canDelete($member = null)
    {
        return Permission::checkMember($member, 'CURATOR_FEED_DELETE');
    }

    public function providePermissions()
    {
        return [
            'CURATOR_FEED_VIEW' => [
                'name' => 'View a Curator feed',
                'category' => 'Curator.io',
            ],
            'CURATOR_FEED_EDIT' => [
                'name' => 'Edit a Curator feedn',
                'category' => 'Curator.io',
            ],
            'CURATOR_FEED_CREATE' => [
                'name' => 'Create a Curator feed',
                'category' => 'Curator.io',
            ],
            'CURATOR_FEED_DELETE' => [
                'name' => 'Delete a Curator feed',
                'category' => 'Curator.io',
            ]
        ];
    }

}
