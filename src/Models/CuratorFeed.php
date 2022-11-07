<?php

namespace NSWDPC\Elemental\Models\Curator;

use SilverStripe\Forms\CompositeField;
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

    /**
     * @inheritdoc
     */
    private static $table_name = 'CuratorFeed';

    /**
     * @inheritdoc
     */
    private static $singular_name = 'Curator.io feed';

    /**
     * @inheritdoc
     */
    private static $plural_name = 'Curator.io feeds';

    /**
     * If you have a free Curator.io account this message must be included
     * @var boolean
     */
    private static $include_powered_by = true;

    /**
     * @inheritdoc
     */
    private static $db = [
        'Title' => 'Varchar(255)',
        'CuratorFeedDescription' => 'Text',
        'CuratorFeedId' => 'Varchar(255)',
        'CuratorContainerId' => 'Varchar(255)'
    ];

    /**
     * @inheritdoc
     */
    private static $indexes = [
        'CuratorFeedId' => true,
        'CuratorContainerId' => true,
    ];

    /**
     * @inheritdoc
     */
    private static $summary_fields = [
        'Title' => 'Title',
        'CuratorFeedId' => 'Curator Feed Public Key',
        'CuratorContainerId' => 'Curator Container ID',
        'CuratorFeedDescription' => 'Description'
    ];

    /**
     * @inheritdoc
     */
    private static $searchable_fields = [
        'Title' => 'PartialMatchFilter',
        'CuratorFeedId' => 'PartialMatchFilter',
        'CuratorContainerId' => 'PartialMatchFilter',
        'CuratorFeedDescription' => 'PartialMatchFilter'
    ];

    /**
     * @inheritdoc
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        if($this->exists()) {
            if(empty($this->CuratorFeedId)) {
                throw new ValidationException(
                    _t(__CLASS__ . ".NO_FEED_ID","Please provide a Curator.io Feed Public Key")
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

    /**
     * Return this record rendered into the feed script template
     */
    public function getCustomFeedScript() {
        return $this->renderWith("NSWDPC/Elemental/Models/Curator/FeedScript");
    }

    /**
     * Push the custom script for this feed into the Requirements API
     */
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

    /**
     * @inheritdoc
     */
    public function getCMSValidator()
    {
        return new RequiredFields([
            'CuratorFeedId','CuratorContainerId'
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getCMSFields() {
        $fields = parent::getCMSFields();
        $fields->removeByName([
            'Title',
            'CuratorFeedDescription',
            'CuratorFeedId',
            'CuratorContainerId'
        ]);
        $fields->addFieldsToTab(
            'Root.Main',
            [
                CompositeField::create(
                    TextField::create(
                        'Title',
                        _t(__CLASS__. '.CURATOR_TITLE', 'Title'),
                    )->setDescription(
                        _t(
                            __CLASS__ . '.CURATOR_TITLE_DESCRIPTION',
                            "This value identifies the feed on this website"
                        )
                    ),

                    TextareaField::create(
                        'CuratorFeedDescription',
                        _t(
                            __CLASS__ . '.CURATOR_FEED_DESCRIPTION',
                            "Description"
                        )
                    )->setDescription(
                        _t(
                            __CLASS__ . '.CURATOR_FEED_DESCRIPTION_DESCRIPTION',
                            "This value could be displayed on your website"
                        )
                    )
                )->setTitle(
                    _t(
                        __CLASS__ . '.CURATOR_FEED_ADMIN_WEBSITE_INFO',
                        "Describe this feed"
                    )
                ),

                CompositeField::create(

                    TextField::create(
                        'CuratorFeedId',
                        'Feed Public Key'
                    )->setDescription(
                        _t(
                            __CLASS__ . '.CURATOR_FEED_ID_DESCRIPTION',
                            "You can find this value in the 'Publish' section under 'Feed Public Key' at app.curator.io"
                        )
                    )->setAttribute('required','required'),

                    TextField::create(
                        'CuratorContainerId',
                        'Container Id'
                    )->setDescription(
                        _t(
                            __CLASS__ . '.CURATOR_FEED_ID_DESCRIPTION',
                            "You can find this value in the 'Publish' section under 'Advanced' at app.curator.io"
                        )
                    )->setAttribute('required','required')

                )->setTitle(
                    _t(
                        __CLASS__ . '.CURATOR_FEED_ADMIN_CURATOR_INFO',
                        "Settings from app.curator.io"
                    )
                )

            ]
        );
        return $fields;
    }

    /**
     * @inheritdoc
     */
    public function canView($member = null)
    {
        return Permission::checkMember($member, 'CURATOR_FEED_VIEW');
    }

    /**
     * @inheritdoc
     */
    public function canCreate($member = null, $context = [])
    {
        return Permission::checkMember($member, 'CURATOR_FEED_CREATE');
    }

    /**
     * @inheritdoc
     */
    public function canEdit($member = null)
    {
        return Permission::checkMember($member, 'CURATOR_FEED_EDIT');
    }

    /**
     * @inheritdoc
     */
    public function canDelete($member = null)
    {
        return Permission::checkMember($member, 'CURATOR_FEED_DELETE');
    }

    /**
     * @inheritdoc
     */
    public function providePermissions()
    {
        return [
            'CURATOR_FEED_VIEW' => [
                'name' => 'View a Curator feed',
                'category' => 'Curator.io',
            ],
            'CURATOR_FEED_EDIT' => [
                'name' => 'Edit a Curator feed',
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
