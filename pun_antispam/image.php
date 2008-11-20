<?php

/**
 * Generates a CAPTCHA picture
 *
 * @copyright Copyright (C) 2008 PunBB, partially based on code by Jamie Furness
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package pun_antispam
 */

// Generate a random string
function pun_antispam_rand_str ()
{
	return strtr(substr(strtolower(md5(uniqid(rand(), 1))), 2, 7), 'abcdef', '165380');
}

// Output CAPTCHA string into an image
function pun_antispam_image($string)
{
	$im = imagecreate(100, 18);

	$white = imagecolorallocate($im, 255, 255, 255);
	$black = imagecolorallocate($im, 0, 0, 0);
	$other = imagecolorallocate($im, 128, 128, 255);

	for ($i = strlen($string); $i--; )
		imagestring($im, rand(4, 5), 14 * $i + rand(0, 5), rand(0, 2), $string{$i}, $black);

	sleep(1);

	header('Content-type:image/jpeg');
	header('Cache-Control: no-cache, must-revalidate');
	header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
	header('Pragma: no-cache');

	imagejpeg($im, null, 30);
	imagedestroy($im);

	return $string;
}

session_start();

$pun_antispam_string = pun_antispam_rand_str();
$_SESSION['pun_antispam_text'] = $pun_antispam_string;
pun_antispam_image($pun_antispam_string);

?>