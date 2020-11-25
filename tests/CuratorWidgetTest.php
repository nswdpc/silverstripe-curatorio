<?php

namespace  SWDPC\Elemental\Tests\Curator;

use NSWDPC\Elemental\Models\Curator\ElementCuratorFeedWidget;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\View\Requirements;

/**
 * Unit test to verify Curator element handling
 * @author James
 */
class CuratorFeedWidgetTest extends SapphireTest
{

    protected $usesDatabase = true;

    public function setUp() {
        parent::setUp();
    }

    public function tearDown()
    {
        parent::tearDown();
    }

    public function testWidget() {

        $record = [
            'Title' => 'CURATOR_TEST',
            'CuratorFeedId' => 'abcd123',
            'CuratorContainerId' => 'container-test-id'
        ];

        $element = ElementCuratorFeedWidget::create($record);
        $element->write();

        $this->assertEquals($record['Title'], $element->Title);
        $this->assertEquals($record['CuratorFeedId'], $element->CuratorFeedId);
        $this->assertEquals($record['CuratorContainerId'], $element->CuratorContainerId);

        $template = $element->forTemplate();

        $this->assertTrue(strpos($template, "<div id=\"{$record['CuratorContainerId']}\">") !== false, "The div element ID is wrong for the container");

        if($element->config()->get('include_powered_by')) {
            $this->assertTrue( strpos($template, "Powered by Curator.io") !== false, "Powered by value should be present" );
        } else {
            $this->assertFalse( strpos($template, "Powered by Curator.io") !== false, "Powered by value should not be present" );
        }

        $scripts = Requirements::get_custom_scripts();

        $this->assertTrue( is_array($scripts) && array_key_exists("curator_feed_{$record['CuratorFeedId']}", $scripts), "Requirements do not exist" );

    }

}
