<?php
/**
 * Notification Mail class for PHProjekt 6.0
 *
 * This software is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License version 2.1 as published by the Free Software Foundation
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * @copyright  Copyright (c) 2008 Mayflower GmbH (http://www.mayflower.de)
 * @license    LGPL 2.1 (See LICENSE file)
 * @version    $Id$
 * @author     Mariano La Penna <mariano.lapenna@mayflower.de>
 * @package    PHProjekt
 * @subpackage Core
 * @link       http://www.phprojekt.com
 * @since      File available since Release 6.0
 */

/**
 * Notification Mail class for PHProjekt 6.0
 *
 * @copyright  Copyright (c) 2008 Mayflower GmbH (http://www.mayflower.de)
 * @version    Release: @package_version@
 * @license    LGPL 2.1 (See LICENSE file)
 * @package    PHProjekt
 * @subpackage Core
 * @link       http://www.phprojekt.com
 * @since      File available since Release 6.0
 * @author     Mariano La Penna <mariano.lapenna@mayflower.de>
 */
class Phprojekt_Notification_Mail extends Phprojekt_Mail
{
    const MODE_HTML           = 'Html';
    const MODE_TEXT           = 'Text';
    const PARAMS_CHARSET      = 0;
    const PARAMS_BODYMODE     = 2;

    public function __construct($params)
    {
        parent::__construct($params[self::PARAMS_CHARSET]);

        $this->_bodyMode = $params[self::PARAMS_BODYMODE];
    }

    /**
     * Sets the name and email of the sender
     *
     * @see Phprojekt_User_User()
     *
     * @return void
     */
    public function setCustomFrom($from)
    {
        $phpUser = Phprojekt_Loader::getLibraryClass('Phprojekt_User_User');
        $phpUser->find($from);

        $email = $phpUser->getSetting('email');

        $name = trim($phpUser->firstname . ' ' . $phpUser->lastname);
        if (!empty($name)) {
            $name .= ' (' . $phpUser->username . ')';
        } else {
            $name = $phpUser->username;
        }

        $this->setFrom($email, $name);
    }

    /**
     * Sets the recipients according to the received ids
     *
     * @return void
     */
    public function setTo($recipients)
    {
        $phpUser = Phprojekt_Loader::getLibraryClass('Phprojekt_User_User');
        $setting = Phprojekt_Loader::getModel('Setting', 'Setting');

        foreach ($recipients as $recipient) {
            $email = $setting->getSetting('email', (int) $recipient);

            if ((int) $recipient) {
                $phpUser->find($recipient);
            } else {
                $phpUser->find(Phprojekt_Auth::getUserId());
            }

            $name = trim($phpUser->firstname . ' ' . $phpUser->lastname);
            if (!empty($name)) {
                $name = $name . ' (' . $phpUser->username . ')';
            } else {
                $name = $phpUser->username;
            }
            $this->addTo($email, $name);
        }
    }

    /**
     * Sets the subject of the email according to the string received
     *
     * @return void
     */
    public function setCustomSubject($subject)
    {
        $this->setSubject($subject);
    }

    /**
     * Sets the body of the email according to the data received from Notification class
     *
     * @return void
     */
    public function setCustomBody($params, $fields, $changes = null)
    {
        $view             = Phprojekt::getInstance()->getView();
        $view->mainFields = $fields;

        if ($changes !== null) {
            $view->changes = $changes;
        }

        $view->title = Phprojekt::getInstance()->translate('A ')
            . Phprojekt::getInstance()->translate($params['moduleTable'])
            . Phprojekt::getInstance()->translate(' item has been ')
            . Phprojekt::getInstance()->translate($params['actionLabel']);

        $view->url       = $params['url'];
        $view->translate = Phprojekt::getInstance()->getTranslate();

        if ($this->_bodyMode == self::MODE_TEXT) {
            $view->endOfLine = $this->getEndOfLine();
        }

        Phprojekt_Loader::loadViewScript();

        $body = $view->render('mail' . $this->_bodyMode . '.phtml');

        switch ($this->_bodyMode) {
            case self::MODE_TEXT:
            default:
                $this->setBodyText($body);
                break;
            case self::MODE_HTML:
                $this->setBodyHtml($body);
                break;
        }
    }

    /**
     * Sends an email notification in Html/Text mode using the inherited method send(), with
     * the contents according to a specific module and a specific event.
     * Previous to calling this function, there has to be called all the set* methods.
     *
     * @return void
     */
    public function sendNotification()
    {
        // Creates the Zend_Mail_Transport_<Smtp/SendMail> object
        $smtpTransport = $this->setTransport();

        try {
            $this->send($smtpTransport);
        } catch(Exception $e){
            throw new Phprojekt_PublishedException('SMTP error: ' . $e->getMessage());
        }
    }
}