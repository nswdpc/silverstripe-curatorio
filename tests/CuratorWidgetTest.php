<?php

namespace NSWDPC\Elemental\Tests\Curator;

use NSWDPC\Elemental\Models\Curator\CuratorFeed;
use NSWDPC\Elemental\Models\Curator\ElementCuratorFeedWidget;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\View\Requirements;

/**
 * Unit test to verify Curator element handling
 * @author James
 */
class CuratorWidgetTest extends SapphireTest
{

    /**
     * @inheritdoc
     */
    protected $usesDatabase = true;

    /**
     * @inheritdoc
     */
    protected static $fixture_file = './CuratorWidgetTest.yml';

    /**
     * Test the widget template
     */
    public function testWidget(): void {

        $element = $this->objFromFixture( ElementCuratorFeedWidget::class, 'testfeedelement1');
        $feedRecord = $element->CuratorFeedRecord();

        $this->assertInstanceOf( CuratorFeed::class, $feedRecord );
        $this->assertTrue( $feedRecord->exists() );

        $template = $element->forTemplate();

        $this->assertTrue(str_contains((string) $template, "<div id=\"{$feedRecord->CuratorContainerId}\">"), "The div element ID is wrong for the container");

        if($element->config()->get('include_powered_by')) {
            $this->assertTrue( str_contains((string) $template, "Powered by Curator.io"), "Powered by value should be present" );
        } else {
            $this->assertFalse( str_contains((string) $template, "Powered by Curator.io"), "Powered by value should not be present" );
        }

        $scripts = Requirements::get_custom_scripts();

        $this->assertTrue( is_array($scripts) && array_key_exists("curator_feed_{$feedRecord->CuratorFeedId}", $scripts), "Requirements do not exist" );

    }

}
