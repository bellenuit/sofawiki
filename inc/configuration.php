<?php
	
/** 
 * Sample configuration.php file.
 *  
 * Is not be used by api.php and serves only as a template
 */

if (!defined("SOFAWIKI")) die("invalid acces");

// 1. Name your wiki

$swMainName = "{{{swmainname}}}"; // SofaWiki
$swBaseHrefFolder = "{{{swbasehrefolder}}}"; // https://www.sofawiki.com/
$swBaseHref = $swBaseHrefFolder.'index.php'; // https://www.sofawiki.com/index.php


// 2. Create a cookie prefix if you have multiple wikis on the same domain and add personal cookies here

//$swCookiePrefix = 'sofawiki'; 

/*
if (array_key_exists('affiliate',$_GET))
{
include_once 
	swSetCookie("affiliate", $_GET['aff'],2*604800); 
}
*/
 

// 2. Create a master user. This needed because there is no user page yet at installation

$poweruser = new swUser;
$poweruser->username = "{{{powerusername}}}"; // admin
$poweruser->ppass = "{{{poweruserpass}}}"; // 1234
$poweruser->content = "[[_view::*]] 
[[_create::*]] 
[[_upload::*]]
[[_modify::*]]
[[_protect::*]] 
[[_rename::*]]
[[_delete::*]]
[[_special::special]]";

// 3. Define default rights for users that create themselves

$swAllUserRights .= " [[_view::Main]] [[_view::Category]] ";
//$swNewUserRights .= " ";
//$swNewUserEnable = true;

// 5. Define your encryption salt so that md5 footprints cannot be reused on another server.
// Note that a change here makes all your current User passwords invalid.

$swEncryptionSalt = "{{{encryptionsalt}}}"; // 0000

// 6. Configure skins and add your own. If you create your own skins, start with a copy of default.php and move it to the site/skins folder

// $swSkins["default"] = "inc/skins/default.php";
 $swDefaultSkin = "{{{swskin}}}";

// 7. Enable languages and set default language

//$swLanguages[] = "de"; 
$swLanguages[] = "{{{swlang}}}";
//$swLanguages[] = "fr";
$swDefaultLang = "{{{swlang}}}";


// 8. Namespaces that are allowed for transclusion. normally this is only main and templates.
// Pay attention: If you allow User namespace, everybody can see User Rights of other users.

 $swTranscludeNamespaces[] = "Image";

// 9. Namespaces that are included for search and set timelimit for searches

// $swSearchNamespaces[] = "Namespace";

 // on all searches, filtering will be aborted after n msec. Index will be created over multiple searches
 $swMaxOverallSearchTime = 2000;  
 // searches priority in title. if title search gives results, returns only these
 $swQuickSearchinTitle = true;
 // if $swQuickSearchinTitle is set and an exact match is found, the page is returned instead of a search result
 $swQuickSearchRedirect = false;
 // limits number of searching words for long search to avoid long searches
 $swSearchWordLimit = 4;
 // index only on cron job or Special:Recent Changes. swFilter may temporarily not show latest results.
 // set this true if you have more than 15'000 revisions.
 $swLazyIndexing = false;
 // max file size for upload
 // note that the PHP environment may limit the upload size (upload_max_filesize default 2M and post_max_size) 
 $swMaxFileSize = 8000000;
ini_set('upload_max_filesize',$swMaxFileSize);
ini_set('post_max_size',$swMaxFileSize);

 
// 12. Define Email and actions to notify

// $swNotifyMail = "a@b.c";
// $swNotifyActions[] = "newusersubmit";  // password sent to user
// $swNotifyActions[] = "lostpasswordsubmit"; // new password sent to user

// 13. Define Hooks


/*
// allows you to return a custom page when a user has not the right to view a page
function swInternalNoAccessHook($name) {} 
*/


/*
// allows you to reorder and format Category pages and search results
function swInternalCategoryHook($name,$wn) {} 
function swInternalSearchHook($name,$wn) {} 
*/

/*
// allows you to make your own housekeeping. return true to prevent the default cron jobs (see inc/cron.php)
// you might also do this in a real cronjob
function swInternalCronHook() {} 
*/

/*
// handles pseudo namespaces linke interlanguage links 
// handles pseudo namespaces linke interlanguage links 
function swInternalLinkHook($val) {} 

*/


// 14. Define custom functions
// put all functions in the site/functions folder. use the Special:Backup page to backup these files

// include_once "$swRoot/site/functions/myfunction.php";


// 15. Define Simple URL scheme and enable simple URL scheme

/* add the following to your .htaccess file

RewriteEngine on
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php?name=$1 [L,QSA]

*/

// $swSimpleURL = true; 

// 16. allow image scaler functions (works only on servers, not on localhost

$swImageScaler = true;

// 17. set Timezone

swSetTimeZone("Europe/Zurich");

// 18. filetypes for which browser should download and not open a new window

$swMediaFileTypeDownload = ".xls.docx.doc.";


// 19. Set limit (number of wrong logins) for Deny Manager. Set -1 to disable it. 

$swDenyCount = 5;

