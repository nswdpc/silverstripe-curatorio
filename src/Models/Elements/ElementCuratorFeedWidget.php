<?php

namespace NSWDPC\Elemental\Models\Curator;

use DNADesign\Elemental\Models\BaseElement;
use SilverStripe\Forms\DropdownField;

/**
 * Provides a Curator Widget Element
 *
 * Editors select an existing Curator feed record to display
 *
 * Feed curation occurs in the Curator.io administration area at app.curator.io
 *
 * @author James Ellis
 * @property int $CuratorFeedRecordID
 * @method \NSWDPC\Elemental\Models\Curator\CuratorFeed CuratorFeedRecord()
 */
class ElementCuratorFeedWidget extends BaseElement
{
    /**
     * @inheritdoc
     */
    private static string $table_name = 'ElementCuratorFeedWidget';

    /**
     * @inheritdoc
     */
    private static string $icon = 'font-icon-block-carousel';

    /**
     * @inheritdoc
     */
    private static bool $inline_editable = true;

    /**
     * @inheritdoc
     */
    private static string $singular_name = 'Curator.io feed widget';

    /**
     * @inheritdoc
     */
    private static string $plural_name = 'Curator.io feed widgets';

    /**
     * @inheritdoc
     */
    private static string $title = 'Curator.io feed widget';

    /**
     * @inheritdoc
     */
    private static string $class_description = 'Display a published feed from Curator.io';

    /**
     * If you have a free Curator.io account this message must be included
     */
    private static bool $include_powered_by = true;

    /**
     * @inheritdoc
     */
    private static array $has_one = [
        'CuratorFeedRecord' => CuratorFeed::class,
    ];

    /**
     * Elemental Type value
      * @return string
     */
    #[\Override]
    public function getType()
    {
        return _t(self::class . '.BlockType', 'Curator.io Feed Widget');
    }

    /**
     * Return a nicer anchor title
     * @return string
     */
    public function getAnchorTitle()
    {
        $feed = $this->CuratorFeedRecord();
        return _t(
            self::class . '.FEED_TITLE',
            "Curator.io feed {feedid}",
            [
                'feedid' => $feed->CuratorFeedId ?? _t(self::class . '.NO_FEED_ID', '(no feed id)')
            ]
        );
    }

    /**
     * Render this element with the Curator Feed record
     */
    #[\Override]
    public function forTemplate($holder = true): string
    {
        // Ensure the element values are used for rendering
        $feed = $this->CuratorFeedRecord();
        if ($feed) {
            $data = \SilverStripe\Model\ArrayData::create([
                'Title' => $this->Title,
                'ShowTitle' => $this->ShowTitle,
            ]);
            $feed->supplyRequirements();
            return $feed->customise($data)->renderWith(self::class);
        }

        return '';
    }

    /**
     * @inheritdoc
     */
    #[\Override]
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->addFieldsToTab(
            'Root.Main',
            [
                DropdownField::create(
                    'CuratorFeedRecordID',
                    _t(self::class. '.SELECT_CURATOR_FEED', 'Select a Curator.io feed'),
                    CuratorFeed::get()->map('ID', 'Title')
                )->setEmptyString('')
            ]
        );
        return $fields;
    }

}
