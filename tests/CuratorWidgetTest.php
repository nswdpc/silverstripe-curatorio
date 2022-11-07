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
    public function testWidget() {

        $element = $this->objFromFixture( ElementCuratorFeedWidget::class, 'testfeedelement1');
        $feedRecord = $element->CuratorFeedRecord();

        $this->assertInstanceOf( CuratorFeed::class, $feedRecord );
        $this->assertTrue( $feedRecord->exists() );

        $template = $element->forTemplate();

        $this->assertTrue(strpos($template, "<div id=\"{$feedRecord->CuratorContainerId}\">") !== false, "The div element ID is wrong for the container");

        if($element->config()->get('include_powered_by')) {
            $this->assertTrue( strpos($template, "Powered by Curator.io") !== false, "Powered by value should be present" );
        } else {
            $this->assertFalse( strpos($template, "Powered by Curator.io") !== false, "Powered by value should not be present" );
        }

        $scripts = Requirements::get_custom_scripts();

        $this->assertTrue( is_array($scripts) && array_key_exists("curator_feed_{$feedRecord->CuratorFeedId}", $scripts), "Requirements do not exist" );

    }

}
