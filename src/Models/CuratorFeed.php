<?php

namespace NSWDPC\Elemental\Models\Curator;

use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\View\Requirements;
use SilverStripe\ORM\ValidationException;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\Forms\GridField\GridFieldEditButton;
use SilverStripe\Forms\GridField\GridFieldViewButton;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldConfig_Base;
use SilverStripe\Security\PermissionProvider;
use SilverStripe\Security\Permission;
use SilverStripe\Versioned\GridFieldArchiveAction;
use SilverStripe\View\ArrayData;

/**
 * Allows a curator feed to be configured
 * Editors provide a Curator Feed ID and Container ID of a published feed
 * Feed curation occurs in the Curator.io administration area
 * @author James Ellis
 * @property string $Title
 * @property ?string $CuratorFeedDescription
 * @property ?string $CuratorFeedId
 * @property ?string $CuratorContainerId
 * @method \SilverStripe\ORM\HasManyList<\NSWDPC\Elemental\Models\Curator\ElementCuratorFeedWidget> FeedWidgets()
 */
class CuratorFeed extends DataObject implements PermissionProvider {

    /**
     * @inheritdoc
     */
    private static string $table_name = 'CuratorFeed';

    /**
     * @inheritdoc
     */
    private static string $singular_name = 'Curator.io feed';

    /**
     * @inheritdoc
     */
    private static string $plural_name = 'Curator.io feeds';

    /**
     * If you have a free Curator.io account this message must be included
     */
    private static bool $include_powered_by = true;

    /**
     * @inheritdoc
     */
    private static array $db = [
        'Title' => 'Varchar(255)',
        'CuratorFeedDescription' => 'Text',
        'CuratorFeedId' => 'Varchar(255)',
        'CuratorContainerId' => 'Varchar(255)'
    ];

    /**
     * @inheritdoc
     */
    private static array $indexes = [
        'CuratorFeedId' => true,
        'CuratorContainerId' => true,
    ];

    /**
     * @inheritdoc
     */
    private static array $summary_fields = [
        'Title' => 'Title',
        'CuratorFeedId' => 'Curator Feed Public Key',
        'CuratorContainerId' => 'Curator Container ID',
        'CuratorFeedDescription' => 'Description',
        'FeedWidgets.Count' => 'Content blocks'
    ];

    /**
     * @inheritdoc
     */
    private static array $searchable_fields = [
        'Title' => 'PartialMatchFilter',
        'CuratorFeedId' => 'PartialMatchFilter',
        'CuratorContainerId' => 'PartialMatchFilter',
        'CuratorFeedDescription' => 'PartialMatchFilter'
    ];

    /**
     * @inheritdoc
     */
    private static array $has_many = [
        'FeedWidgets' => ElementCuratorFeedWidget::class
    ];

    /**
     * Store whether the feed was rendered in this instance
     */
    private bool $_cache_is_rendered = false;

    /**
     * @inheritdoc
     */
    #[\Override]
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        if($this->exists()) {
            if(empty($this->CuratorFeedId)) {
                throw \SilverStripe\ORM\ValidationException::create(_t(self::class . ".NO_FEED_ID","Please provide a Curator.io Feed Public Key"));
            }

            if(empty($this->CuratorContainerId)) {
                throw \SilverStripe\ORM\ValidationException::create(_t(self::class . ".NO_FEED_ID","Please provide a Curator.io Container Id"));
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
    public function getCMSValidator(): \SilverStripe\Forms\RequiredFields
    {
        return \SilverStripe\Forms\RequiredFields::create([
            'CuratorFeedId','CuratorContainerId'
        ]);
    }

    /**
     * @inheritdoc
     */
    #[\Override]
    public function getCMSFields() {
        $fields = parent::getCMSFields();
        $fields->removeByName([
            'Title',
            'CuratorFeedDescription',
            'CuratorFeedId',
            'CuratorContainerId'
        ]);

        $feedWidgetsField = $fields->dataFieldByName('FeedWidgets');
        if($feedWidgetsField instanceof GridField) {
            $fieldConfig = $feedWidgetsField->getConfig();
            $fieldConfig->removeComponentsByType( [
                GridFieldAddNewButton::class,
                GridFieldAddExistingAutoCompleter::class,
                GridFieldEditButton::class,
                GridFieldArchiveAction::class,
                GridFieldDeleteAction::class,
                GridFieldViewButton::class
            ] );
            $dataColumns = $fieldConfig->getComponentByType( GridFieldDataColumns::class );
            $displayFields = [];
            $displayFields["Title"] = [
                'title' => _t(self::class . ".ELEMENT_TITLE", "Title"),
                'callback' => fn($record, $column, $grid) => $record->Title
            ];
            $displayFields["PageTitle"] = [
                'title' => _t(self::class . ".ELEMENT_LOCATION", "Location"),
                'callback' => function ($record, $column, $grid) {
                    $owner = $record->getPage();
                    if($owner) {
                        if($owner->hasMethod('CMSEditLink')) {
                            $html = '<a href="' . htmlspecialchars((string) $owner->CMSEditLink()) . '">' . htmlspecialchars((string) $owner->Title) . "</a>";
                        } else {
                            $html = htmlspecialchars((string) $owner->Title);
                        }

                        return LiteralField::create(
                            "LinkToLocation_Record{$record->ID}",
                            $html
                        );
                    } else {
                        return _t(self::class . ".ELEMENT_LOCATION_NONE", "Not linked to any location");
                    }
                }
            ];
            $dataColumns->setDisplayFields( $displayFields );
        }

        $fields->addFieldsToTab(
            'Root.Main',
            [
                CompositeField::create(
                    TextField::create(
                        'Title',
                        _t(self::class. '.CURATOR_TITLE', 'Title'),
                    )->setDescription(
                        _t(
                            self::class . '.CURATOR_TITLE_DESCRIPTION',
                            "This value identifies the feed on this website"
                        )
                    ),

                    TextareaField::create(
                        'CuratorFeedDescription',
                        _t(
                            self::class . '.CURATOR_FEED_DESCRIPTION',
                            "Description"
                        )
                    )->setDescription(
                        _t(
                            self::class . '.CURATOR_FEED_DESCRIPTION_DESCRIPTION',
                            "This value could be displayed on your website"
                        )
                    )
                )->setTitle(
                    _t(
                        self::class . '.CURATOR_FEED_ADMIN_WEBSITE_INFO',
                        "Describe this feed"
                    )
                ),

                CompositeField::create(

                    TextField::create(
                        'CuratorFeedId',
                        'Feed Public Key'
                    )->setDescription(
                        _t(
                            self::class . '.CURATOR_FEED_ID_DESCRIPTION',
                            "You can find this value in the 'Publish' section under 'Feed Public Key' at app.curator.io"
                        )
                    )->setAttribute('required','required'),

                    TextField::create(
                        'CuratorContainerId',
                        'Container Id'
                    )->setDescription(
                        _t(
                            self::class . '.CURATOR_FEED_ID_DESCRIPTION',
                            "You can find this value in the 'Publish' section under 'Advanced' at app.curator.io"
                        )
                    )->setAttribute('required','required')

                )->setTitle(
                    _t(
                        self::class . '.CURATOR_FEED_ADMIN_CURATOR_INFO',
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
    #[\Override]
    public function canView($member = null)
    {
        return Permission::checkMember($member, 'CURATOR_FEED_VIEW');
    }

    /**
     * @inheritdoc
     */
    #[\Override]
    public function canCreate($member = null, $context = [])
    {
        return Permission::checkMember($member, 'CURATOR_FEED_CREATE');
    }

    /**
     * @inheritdoc
     */
    #[\Override]
    public function canEdit($member = null)
    {
        return Permission::checkMember($member, 'CURATOR_FEED_EDIT');
    }

    /**
     * @inheritdoc
     */
    #[\Override]
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
