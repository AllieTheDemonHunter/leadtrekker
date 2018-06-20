<?php

namespace Drupal\leadtrekker\EventSubscriber;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class DefaultSubscriber.
 */
class DefaultSubscriber implements EventSubscriberInterface
{


    /**
     * Constructs a new DefaultSubscriber object.
     */
    public function __construct()
    {

    }

    /**
     * {@inheritdoc}
     */
    static function getSubscribedEvents()
    {
        $events['kernel.request'] = ['kernel_request'];
        $events['kernel.controller'] = ['kernel_controller'];

        return $events;
    }

    /**
     * This method is called whenever the kernel.request event is
     * dispatched.
     *
     * @param GetResponseEvent $event
     */
    public function kernel_request(Event $event)
    {
        global $_SESSION, $_SERVER;

        if (!session_status()) {
            session_start();
        }

        //Find extra data to attach to leadtrekker submissions
        if ($_SERVER['QUERY_STRING'] != "") {
            $this->_leadtrekker_recognise($_SERVER['QUERY_STRING']);
        }
    }

    /**
     * This method is called whenever the kernel.controller event is
     * dispatched.
     *
     * @param GetResponseEvent $event
     */
    public function kernel_controller(Event $event)
    {

    }

    /**
     *  Checks for a pattern identifying Leadtrekker or PMailer.
     */
    function _leadtrekker_recognise($url)
    {
        global $_SESSION;
        $query_array = [];
        parse_str($url, $query_array);

        if (!empty($query_array)) {
            $pattern['google'] = ['campaign', 'adgroup', 'keyword'];
            $pattern['mail'] = ['utm_source', 'utm_medium', 'utm_campaign'];

            foreach ($pattern as $source_to_check => $required_keys) {
                foreach ($required_keys as $required_key) {
                    if (array_key_exists($required_key, $query_array)) {
                        //This query set seems legit.
                        $this->_leadtrekker_register($query_array);
                        return;
                    }
                }
            }
        }
    }

    /**
     * @param $external_reference_info
     */
    function _leadtrekker_register($external_reference_info)
    {
        global $_SESSION;
        // We're saving it along with any LT submission.
        // This also means that only one external named reference can be used.
        $_SESSION['leadtrekker'] = $external_reference_info;
    }

}
