<?php

/**
 * Generates a CAPTCHA picture
 *
 * @copyright (C) 2008-2009 PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package pun_antispam
 */
 
/*

We use Securimage PHP CAPTCHA (http://www.phpcaptcha.org)

The only thing this script should do (besides to output an CAPTCHA image)
is to set the CAPTCHA text to the session variable.
Example:

session_start();

$_SESSION['pun_antispam_text'] = <CAPTCHA TEXT HERE>;

*/

include 'securimage.php';

usleep(300000);

$img = new securimage();
$img->show();