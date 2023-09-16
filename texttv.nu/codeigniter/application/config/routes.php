<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	http://codeigniter.com/user_guide/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There area two reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router what URI segments to use if those provided
| in the URL cannot be matched to a valid route.
|
*/

$route['default_controller'] = "sida";
$route['404_override'] = '';
#$route['404_override'] = 'sida/visa/404';

$route['sitemap.xml'] = "sitemap";

#$route['(:num)'] = "sida/visa/$1";
$route['(\d{3})'] = "sida/visa/$1";
$route['([0-9,\-]+)'] = "sida/visa/$1";

// AMP-sida för icke-arkiv-sida
$route['([0-9,\-]+)/amp'] = "sida/amp/$1";

// AMP-sida för arkiverad sida
$route['([0-9,\-]+)/(:any)-(:num)/amp'] = "sida/amp_arkiv/$1/$2/$3";

// Nytt arkiv-format sept 2015
// gammalt: http://texttv.nu/137/arkiv/hardare-straff-for-stenkastare/8466679/
// ny: http://texttv.nu/137,300-301/hardare-straff-for-stenkastare-8466679
$route['([0-9,\-]+)/(:any)-(:num)'] = "sida/arkiv/$1/$2/$3";

// Arkiv
// http://texttv.nu/114/arkiv
$route['(\d{3})\/arkiv'] = "sida/arkiv/$1";
// http://texttv.nu/106/arkiv/25-jan-2012-jamtin-manga-namn-i-luften/2211/
$route['(\d{3})\/arkiv\/([a-z0-9\-]+)\/(\d+)'] = "sida/arkiv/$1/$2/$3";
$route['(:any)\/arkiv\/([a-z0-9\-.]+)\/(:any)'] = "sida/arkiv/$1/$2/$3";

// vanliga textsidor
$route['sida/(:any)'] = "textsida/visa/$1";
$route['sida'] = "textsida";

// API
$route['api/'] = "api/$1";
$route['api/get'] = "api/get/$1";
$route['api/getid'] = "api/getid/$1";
$route['api/get'] = "api/share/$1";
$route['api/page/(:any)/(:any)'] = "api/page/$1/$2";

// Oembed
$route['oembed'] = "oembed";
$route['oembed/'] = "oembed";
$route['oembed/(:any)'] = "oembed";

// Blogg
$route['blogg/(:any)'] = "blogg/visa/$1";

// RSS feeds
$route['feed'] = "rssfeed";
$route['feed/(:any)'] = "rssfeed/visa/$1";

// Dev for dev & test pages
$route['dev'] = "dev";

// Page for Displio
$route['displio'] = "displio";

// Super-seo-page for svt text tv
$route['svt-text-tv'] = "svttexttv";

// Fakta-sidor med mycket info-text ala Wikipedia
$route['text-tv-fakta'] = "fakta";
$route['text-tv-fakta/(:any)'] = "fakta/sida/$1";

// facebook messenger webhook
// https://texttv.nu/fb/webhook
$route['fb/webhook'] = "fb/webhook";
$route['fb/generate_screenshot'] = "fb/generate_screenshot/$1";

// /embed/<pageRange>/ for new apps.
$route['appembed/(:any)'] = "appembed/visa/$1";

/* End of file routes.php */
/* Location: ./application/config/routes.php */
