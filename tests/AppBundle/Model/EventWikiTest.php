<?php
/**
 * This file contains only the EventWikiTest class.
 */

namespace Tests\AppBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit_Framework_TestCase;
use AppBundle\Model\EventWiki;
use AppBundle\Model\Program;
use AppBundle\Model\Event;
use AppBundle\Model\EventWikiStat;
use AppBundle\Model\Organizer;

/**
 * Tests for the EventWiki class.
 */
class EventWikiTest extends PHPUnit_Framework_TestCase
{
    /** @var Event The Event that the EventWiki is part of. */
    protected $event;

    public function setUp()
    {
        date_default_timezone_set('UTC');

        $organizer = new Organizer(50);
        $program = new Program($organizer);
        $this->event = new Event(
            $program,
            '  My program  ',
            '2017-01-01',
            new \DateTime('2017-03-01'),
            'America/New_York'
        );
    }

    /**
     * Tests constructor and basic getters.
     */
    public function testConstructor()
    {
        $wiki = new EventWiki($this->event, 'test.wikipedia');

        // Basic getters.
        static::assertEquals($this->event, $wiki->getEvent());
        static::assertEquals('test.wikipedia', $wiki->getDomain());

        static::assertInstanceOf(
            'Doctrine\Common\Collections\ArrayCollection',
            $wiki->getStatistics()
        );

        // Make sure the association was made on the Event object, too.
        static::assertEquals($wiki, $this->event->getWikis()[0]);
    }

    /**
     * Test adding and removing statistics.
     */
    public function testAddRemoveStatistics()
    {
        $wiki = new EventWiki($this->event, 'test.wikipedia');

        static::assertEquals(0, count($wiki->getStatistics()));

        // Add an EventWikiStat.
        $ews = new EventWikiStat($wiki, 'pages-improved', 50);
        $wiki->addStatistic($ews);

        static::assertEquals($ews, $wiki->getStatistics()[0]);
        static::assertEquals($ews, $wiki->getStatistic('pages-improved'));

        // Try adding the same one, which shouldn't duplicate.
        $wiki->addStatistic($ews);
        static::assertEquals(1, count($wiki->getStatistics()));

        // Removing the statistic.
        $wiki->removeStatistic($ews);
        static::assertEquals(0, count($wiki->getStatistics()));

        // Double-remove shouldn't error out.
        $wiki->removeStatistic($ews);

        // Clearing statistics.
        $wiki->addStatistic($ews);
        static::assertEquals(1, $wiki->getStatistics()->count());
        $wiki->clearStatistics();
        static::assertEquals(0, $wiki->getStatistics()->count());
    }

    /**
     * Test methods involving wiki families.
     */
    public function testWikiFamilies()
    {
        // Not a wiki family.
        $wiki = new EventWiki($this->event, 'test.wikipedia');
        static::assertFalse($wiki->isFamilyWiki());
        static::assertEquals('wikipedia', $wiki->getFamilyName());
        static::assertEquals(new ArrayCollection([]), $wiki->getChildWikis());
        static::assertFalse($wiki->isChildWiki());

        // Create a *.wikipedia EventWiki, making the above EventWiki a child.
        $wikiFam = new EventWiki($this->event, '*.wikipedia');
        static::assertTrue($wikiFam->isFamilyWiki());
        static::assertEquals('wikipedia', $wikiFam->getFamilyName());
        static::assertEquals([$wiki], $wikiFam->getChildWikis()->toArray());
        static::assertTrue($wiki->isChildWiki());
    }
}
