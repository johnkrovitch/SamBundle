<?php

namespace JK\SamBundle\Event\Subscriber;

use JK\Sam\Event\NotificationEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class NotificationSubscriber implements EventSubscriberInterface
{
    /**
     * @var array
     */
    protected $notifications = [];

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            NotificationEvent::NAME => 'notify'
        ];
    }

    /**
     * @param NotificationEvent $event
     */
    public function notify(NotificationEvent $event)
    {
        $this->notifications[] = $event->getMessage();
    }

    /**
     * @return array
     */
    public function getNotifications()
    {
        return $this->notifications;
    }

    /**
     * 
     */
    public function clearNotifications()
    {
        $this->notifications = [];
    }
}
