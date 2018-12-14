<?php

namespace JK\SamBundle\Tests\Event;

use JK\Sam\Event\NotificationEvent;
use JK\SamBundle\Event\Subscriber\NotificationSubscriber;
use PHPUnit\Framework\TestCase;

class NotificationSubscriberTest extends TestCase
{
    /**
     * The subscriber should subscribe to the notify event.
     */
    public function testGetSubscribedEvents()
    {
        $this
            ->assertEquals([
                NotificationEvent::NAME => 'notify'
            ], NotificationSubscriber::getSubscribedEvents())
        ;
    }

    /**
     * Notify method should add a notification into the subscriber. Clear method should reset the notification array.
     */
    public function testNotify()
    {
        $event = new NotificationEvent();
        $event->setMessage('What a test');

        $subscriber = new NotificationSubscriber();
        $subscriber->notify($event);

        $this->assertCount(1, $subscriber->getNotifications());
        $this->assertEquals('What a test', $subscriber->getNotifications()[0]);

        $subscriber->clearNotifications();
        $this->assertEquals([], $subscriber->getNotifications());
    }
}
