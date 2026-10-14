<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Felsidor. sida_saknas() är 404_override i routes.php, så den visas för
 * alla adresser som inte matchar någon controller.
 */
class Fel extends CI_Controller
{
	public function sida_saknas()
	{
		show_texttv_404();
	}
}
