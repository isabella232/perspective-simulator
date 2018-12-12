<?php
/**
 * Email class for Perspective Simulator.
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator\Libs;

use PerspectiveSimulator\Bootstrap;

/**
 * Email class
 */
class Email
{

    private static $sentEmails = [];


    /**
     * Validate an email address using regular expression.
     *
     * @param string $email The email address to validate.
     *
     * @return boolean
     */
    public static function validate($email)
    {
        if (is_string($email) === FALSE) {
            return FALSE;
        }

        // Dot character cannot be the first/last character in the local-part.
        $local       = '\d0-9a-zA-Z-_+';
        $localMiddle = $local.'.\w';

        // Matches a normal email address.
        $pattern  = '/^(['.$local.'](['.$localMiddle.'\']*['.$local.']){0,1}';
        $pattern .= '@(((?:[\da-zA-Z]|[\da-zA-Z][\'\-\w]*[\da-zA-Z])\.)+';
        $pattern .= '[a-zA-Z]{2,7}))$/';
        if (preg_match($pattern, $email) === 1) {
            return TRUE;
        }

        // Email with display name, e.g. 'Someone <some.one@example.com>'.
        $pattern  = '/^[a-zA-Z]+(([\'\,\.\- ][a-zA-Z ])?[a-zA-Z]*)*\s+';
        $pattern .= '<(['.$local.'](['.$localMiddle.']*['.$local.']){0,1}';
        $pattern .= '@(((?:[\da-zA-Z]|[\da-zA-Z][\'\-\w]*[\da-zA-Z])\.)+';
        $pattern .= '[a-zA-Z]{2,7}))>$/';
        if (preg_match($pattern, $email) === 1) {
            return TRUE;
        }

        return FALSE;

    }//end validate()


    /**
     * Sends an email in the simulator.
     *
     * @param string $to      Recipient email address.
     * @param string $from    Sender email address.
     * @param string $subject Email subject.
     * @param string $message Email content.
     *
     * @return boolean
     */
    public static function sendEmail($to, $from, $subject='', $message='') {
        if (self::validate($to) === FALSE || self::validate($from) === FALSE) {
            return FALSE;
        }

        if (Bootstrap::isNotificationsEnabled() === true) {
            $message = wordwrap($message, 70);
            $headers = 'From: '.$from."\r\n";
            $ret     = mail($to, $subject, $message, $headers);
            return $ret;
        } else {
            self::$sentEmails[] = [
                'to'      => $to,
                'from'    => $from,
                'subject' => $subject,
                'message' => $message,
            ];
            return true;
        }

    }//end send()


    /**
     * Returns an array of emails that would have been sent.
     *
     * @return array
     */
    public static function getSentEmails()
    {
        return self::$sentEmails;

    }//end getSentEmails()


}//end class
