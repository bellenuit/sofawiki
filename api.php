<?php

define('SOFAWIKI',true);  // all included files will check for this variable
$swError = "";
$swDebug = "";
$swVersion = '1.7.3';   
$swMainName = 'Main';
$swOldFilter = false;
$swUseSemaphore = true;
$swUseTrigram = false;
$swStartTime = microtime(true);


/*
	include files
	all files included from here and from site/configuration.php
*/

// core
$swRoot = dirname(__FILE__); // must be first


	
	
include_once $swRoot.'/inc/persistance.php';
include_once $swRoot.'/inc/bitmap.php';
include_once $swRoot.'/inc/bloom.php';
include_once $swRoot.'/inc/backup.php';
include_once $swRoot.'/inc/cookies.php';
include_once $swRoot.'/inc/cron.php';
include_once $swRoot.'/inc/db.php';
include_once $swRoot.'/inc/filter.php';

include_once $swRoot.'/inc/trigram.php';
include_once $swRoot.'/inc/function.php';
include_once $swRoot.'/inc/legacy.php';
include_once $swRoot.'/inc/notify.php';
include_once $swRoot.'/inc/parser.php';
include_once $swRoot.'/inc/record.php';
include_once $swRoot.'/inc/rss.php';
include_once $swRoot.'/inc/semaphore.php';
include_once $swRoot.'/inc/sitemap.php';
include_once $swRoot.'/inc/user.php';
include_once $swRoot.'/inc/utilities.php';
include_once $swRoot.'/inc/wiki.php';

// external code
include_once $swRoot.'/inc/diff.php';
include_once $swRoot.'/inc/zip.php';



// functions
$swFunctions = array();
include_once $swRoot.'/inc/functions/substr.php';
include_once $swRoot.'/inc/functions/strreplace.php';
include_once $swRoot.'/inc/functions/info.php';
include_once $swRoot.'/inc/functions/value.php';
include_once $swRoot.'/inc/functions/resume.php';
include_once $swRoot.'/inc/functions/firstvalue.php';
include_once $swRoot.'/inc/functions/extvalue.php';
include_once $swRoot.'/inc/functions/query.php';
include_once $swRoot.'/inc/functions/sprintf.php';
include_once $swRoot.'/inc/functions/nameurl.php';
include_once $swRoot.'/inc/functions/prettydate.php';
include_once $swRoot.'/inc/functions/calc.php';
include_once $swRoot.'/inc/functions/css.php';

// parsers
$swParsers = array();
include_once $swRoot.'/inc/parsers/redirection.php';
include_once $swRoot.'/inc/parsers/displayname.php';
include_once $swRoot.'/inc/parsers/tidy.php';
include_once $swRoot.'/inc/parsers/category.php';
include_once $swRoot.'/inc/parsers/templates.php';
include_once $swRoot.'/inc/parsers/fields.php';
include_once $swRoot.'/inc/parsers/images.php';
include_once $swRoot.'/inc/parsers/links.php';
include_once $swRoot.'/inc/parsers/style.php';

// search only
include_once $swRoot.'/inc/parsers/nowiki.php';

// system defaults localization
$swSystemDefaults = array();
include $swRoot.'/inc/system-defaults-en.php';
include $swRoot.'/inc/system-defaults-fr.php';
include $swRoot.'/inc/system-defaults-de.php';
include $swRoot.'/inc/system-defaults-es.php';
include $swRoot.'/inc/system-defaults-da.php';
include $swRoot.'/inc/system-defaults-it.php';
$swSystemSiteValues = array();

// special pages
$swSpecials['All Pages'] = 'allpages.php';
$swSpecials['Recent Changes'] = 'recentchanges.php';
$swSpecials['Protected Pages'] = 'protectedpages.php';
$swSpecials['Deleted Pages'] = 'deletedpages.php';
$swSpecials['Categories'] = 'categories.php';
$swSpecials['Images'] = 'images.php';
$swSpecials['Upload'] = 'upload.php';
$swSpecials['Upload Multiple'] = 'uploadmultiple.php';
// does not work $swSpecials['Index PDF'] = 'indexpdf.php';
$swSpecials['Templates'] = 'templates.php';
$swSpecials['Users'] = 'users.php';
$swSpecials['Passwords'] = 'passwords.php';
$swSpecials['System Messages'] = 'systemmessages.php';
$swSpecials['Indexes'] = 'indexes.php';
$swSpecials['Info'] = 'info.php';
$swSpecials['Special Pages'] = 'specialpages.php';
$swSpecials['Snapshot'] = 'snapshot.php';
$swSpecials['Backup'] = 'backup.php';
$swSpecials['Regex'] = 'regex.php';
$swSpecials['Logs'] = 'logs.php';
$swSpecials['Deny'] = 'deny.php';
$swSpecials['Update'] = 'update.php';
//$swSpecials['Fields'] = 'fields.php';
$swSpecials['Query'] = 'query.php';
$swSpecials['Orphaned Pages'] = 'orphanedpages.php';
$swSpecials['Dead End Pages'] = 'deadendpages.php';
$swSpecials['Redirects'] = 'redirects.php';
$swSpecials['Most Linked Pages'] = 'mostlinkedpages.php';
$swSpecials['Most Linked Categories'] = 'mostlinkedcategories.php';
$swSpecials['Unused Files'] = 'unusedfiles.php';
$swSpecials['Unused Categories'] = 'unusedcategories.php';
$swSpecials['Unused Templates'] = 'unusedtemplates.php';
$swSpecials['Long Pages'] = 'longpages.php';
$swSpecials['Short Pages'] = 'shortpages.php';
$swSpecials['Uncategorized Pages'] = 'uncategorizedpages.php';
$swSpecials['Import'] = 'import.php';

/*
	initialize variables
*/

$swLanguages = array();

$swTranscludeNamespaces = array();
$swTranscludeNamespaces[] = '';
$swTranscludeNamespaces[] = 'Template';
$swTranscludeNamespaces[] = 'System';

$swSearchNamespaces = array();
$swSearchNamespaces[] = '';
$swQuickSearchinTitle = false;
$swMaxSearchTime = 3000;
$swMaxOverallSearchTime = 15000;
$swDenyCount = 5;

$swNewUserFormFields = array();
$swAllUserRights = '[[_view::Main]] ';
$swNewUserRights = '[[_view::Main]] ';
$swNewUserEnable = true;
$swNotifyMail = '';
$swNotifyActions = array();

$swSkins['default'] = $swRoot.'/inc/skins/default.php';
$swSkins['tribune'] = $swRoot.'/inc/skins/tribune.php';
$swSkins['iphone'] = $swRoot.'/inc/skins/iphone.php';
$swSkins['tele'] = $swRoot.'/inc/skins/tele.php';
$swSkins['zeitung'] = $swRoot.'/inc/skins/zeitung.php';
$swSkins['wiki'] = $swRoot.'/inc/skins/wiki.php';
$swSkins['diary'] = $swRoot.'/inc/skins/diary.php';
$swSkins['law'] = $swRoot.'/inc/skins/law.php';
$swDefaultSkin = 'default';

$swDefaultLang = 'en';

$swMediaFileTypeDownload = '';
$swRamdiskPath = '';

$db = new swDB;

if (file_exists($swRoot.'/site/configuration.php'))
	include_once $swRoot.'/site/configuration.php';
else
	include_once $swRoot.'/inc/configuration-install.php';

include_once $swRoot.'/inc/ramdisk.php';

if (defined('SOFAWIKIINDEX'))
{
	//require_once $swRoot.'/inc/cookies.php'; // 0.3.2 moved here, swMainName must have been set
	$lang = handleCookie("lang",$swDefaultLang); 
	$skin = handleCookie("skin",$swDefaultSkin);
}

$swIndexError = false;
$db->init();

// lang depending on configuration
if (! in_array(@$lang,$swLanguages))
	$lang = $swDefaultLang;
	
	
// code depending on configuration
if (isset($swImageScaler))
	include_once $swRoot.'/inc/image.php';
else
	include_once $swRoot.'/inc/image0.php';

$db->salt = $swEncryptionSalt;


// public functions

function swMessage($msg,$lang, $styled=false)
{
	if (strtolower(substr($msg,0,strlen('system:'))) =='system:') 
	{
		$msg = substr($msg,strlen('system:'));
		return swSystemMessage($msg,$lang,$styled);
	}
	else
		echo $msg;
	// wrapper as shortcut
	$lang = substr($lang,0,2); // cut off
	$t = new swTemplateParser;
	$ts = new swStyleParser;
	$ti = new swImagesParser;
	$tl = new swLinksParser;
	$w = new swWiki;
	if (!strstr($msg,':')) $msg = ':'.$msg;
	if ($lang)
		$w->parsedContent = '{{'.$msg.'/'.$lang.'}}';
	else
		$w->parsedContent = '{{'.$msg.'}}';
	$t->dowork($w);
	// if ($styled) {$ti->dowork($w); $tl->dowork($w); $ts->dowork($w); }
	$s = $w->parsedContent;
	return $s;
	
	
}


function swSystemMessage($msg,$lang,$styled=false)
{
	// wrapper as shortcut
	$lang = substr($lang,0,2); // cut off
	$t = new swTemplateParser;
	$ts = new swStyleParser;
	$ti = new swImagesParser;
	$tl = new swLinksParser;
	$w = new swWiki;
	
	global $swSystemSiteValues;
	global $swSystemDefaults;
	global $swDefaultLang;
	
	if ($lang)
		$langmsg =  $msg.'/'.$lang;
	else
		$langmsg =  $msg.'/'.$swDefaultLang;
		
	$w->name = 'System:'.$langmsg;
		
	if (array_key_exists($langmsg,$swSystemSiteValues))
	{
		$w->parsedContent = $swSystemSiteValues[$langmsg];
	}
	else
	{
		$w->lookup();   
		if ($w->visible())
		{
				$swSystemSiteValues[$msg] = $w->content;
				$w->parsedContent = $w->content;
		}
		else
		{
			if (array_key_exists($langmsg,$swSystemDefaults))
			{
				$w->parsedContent = $swSystemSiteValues[$langmsg] = $swSystemDefaults[$langmsg];
			}
			else
			{
				$w->parsedContent = $swSystemSiteValues[$langmsg] = $msg;
			}
		}
	}
	
	if ($styled) {$ti->dowork($w); $tl->dowork($w); $ts->dowork($w); }
	
	$t = $w->parsedContent;
	
	return $t; 

}






?>