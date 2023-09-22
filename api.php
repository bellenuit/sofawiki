<?php
	
/**
 *	This file has all the includes. 
 *
 *  It includes all code from the inc and the site folder and the site/configuration.php file. 
 *  It initializes all global variables.
 *  It checks the cookies, if it is called from index.php (if (defined('SOFAWIKIINDEX')).
 *  It initializes the database.
 *
 *  Other PHP code can include api.php to get full access to the database. 
 *  It can either access a wiki by the functions swMessage($msg,$lang) or swSystemMessage($msg,$lang,$styled=false) or create a new wiki, lookup it or insert changes.
 *
 * $w = new swWiki;
 * $w->name = 'myname';
 * $w->lookup();
 * $s = $w->content;
 * ...
 * $w->content = $s;
 * $w->insert();
 * 
 * Note that you work with wikitext. If you want to have HTML, you should parse it.
 * You can also use the swFilter function to search in the wiki.
 */

define('SOFAWIKI',true);  // all included files will check for this variable
$swError = "";
$swDebug = "";
$swVersion = '3.9.0';   
$swMainName = 'Main';
$swStartTime = microtime(true);
$swSimpleURL = false;
$swLangURL = false;
$swOldStyle = false;
$swOldSearch = false;
$swLogAnonymizedIPNumber = true;
$swEditZoneColor = true;
$swRamdiskPath = 'db';
$swDbaHandler = 'sqlite3'; // small sites: persistance. bigger site: sqlite3. alternative if sqlite3 not present: db4.
$swWikiTextPre = false;


/*
	include files
	all files included from here and from site/configuration.php
*/

// core
/* SOFADOC_IGNORE $swRoot/ */
$swRoot = dirname(__FILE__); // must be first
//if (defined('SOFAWIKICLI')) $swRoot = '.';

// inis
ini_set('pcre.jit',0); // prevent preg_match to be limited to 2700 characters error 503
	
include_once $swRoot.'/inc/utilities.php'; // must be first as used by others
include_once $swRoot.'/inc/notify.php';
include_once $swRoot.'/inc/cookies.php';

//swCookieTest('api61');

include_once $swRoot.'/inc/persistance.php';
include_once $swRoot.'/inc/bitmap.php';
include_once $swRoot.'/inc/bloom.php';
include_once $swRoot.'/inc/backup.php';
include_once $swRoot.'/inc/chart.php';



include_once $swRoot.'/inc/cron.php';
include_once $swRoot.'/inc/db.php';
include_once $swRoot.'/inc/dba.php';
include_once $swRoot.'/inc/deepl.php';
include_once $swRoot.'/inc/expressionoperator.php';
include_once $swRoot.'/inc/expressionfunction.php';
include_once $swRoot.'/inc/expression.php'; // last for unit tests
include_once $swRoot.'/inc/filter.php';
include_once $swRoot.'/inc/function.php';
include_once $swRoot.'/inc/legacy.php';



include_once $swRoot.'/inc/monogram.php';
include_once $swRoot.'/inc/fulltext.php';


include_once $swRoot.'/inc/parser.php';
include_once $swRoot.'/inc/record.php';
include_once $swRoot.'/inc/rss.php';
include_once $swRoot.'/inc/semaphore.php';
include_once $swRoot.'/inc/sitemap.php';
include_once $swRoot.'/inc/user.php';




include_once $swRoot.'/inc/wiki.php';

include_once $swRoot.'/inc/relation.php';
include_once $swRoot.'/inc/relationtuple.php';
include_once $swRoot.'/inc/relationfilter.php';
include_once $swRoot.'/inc/relationexecute.php';

include_once $swRoot.'/inc/editortemplate.php';
include_once $swRoot.'/inc/mail.php';
include_once $swRoot.'/inc/async.php';
include_once $swRoot.'/inc/token.php';

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
include_once $swRoot.'/inc/functions/familyname.php';
include_once $swRoot.'/inc/functions/htmltabletofields.php';
include_once $swRoot.'/inc/functions/system.php';
include_once $swRoot.'/inc/functions/fields.php';
include_once $swRoot.'/inc/functions/schedule.php';
include_once $swRoot.'/inc/functions/relation.php';
include_once $swRoot.'/inc/functions/charts.php';
include_once $swRoot.'/inc/functions/translate.php';
include_once $swRoot.'/inc/functions/preventovertimesearchagain.php';
include_once $swRoot.'/inc/functions/upload.php';
include_once $swRoot.'/inc/functions/textrank.php';




// parsers
$swParsers = array();
include_once $swRoot.'/inc/parsers/cache.php';
include_once $swRoot.'/inc/parsers/redirection.php';
include_once $swRoot.'/inc/parsers/displayname.php';
include_once $swRoot.'/inc/parsers/tidy.php';
include_once $swRoot.'/inc/parsers/category.php';
include_once $swRoot.'/inc/parsers/sublang.php';
include_once $swRoot.'/inc/parsers/fields.php';
include_once $swRoot.'/inc/parsers/templates.php';
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
include $swRoot.'/inc/system-defaults-dk.php';
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
$swSpecials['Metrics'] = 'metrics.php';
$swSpecials['Deny'] = 'deny.php';
$swSpecials['Update'] = 'update.php';
//$swSpecials['Fields'] = 'fields.php';
$swSpecials['Query'] = 'query.php';
$swSpecials['Relation'] = 'relation.php';
$swSpecials['Rest'] = 'rest.php';
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
$swSpecials['Tickets'] = 'tickets.php';
$swSpecials['Wanted Pages'] = 'wantedpages.php';
$swSpecials['Field Search'] = 'fieldsearch.php';
$swSpecials['Upload Big'] = 'uploadbig.php';
$swSpecials['Active Users'] = 'activeusers.php';
$swSpecials['Rest'] = 'rest.php';

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
$swSearchExcludeNamespaces = array();
$swQuickSearchinTitle = false;
$swMaxSearchTime = 3000;
$swMaxOverallSearchTime = 15000;
$swDenyCount = 5;
$swLogCount = 500;
$swLogNameSpace = '';
$swMaxFileSize = 8000000;
$swMaxBigFileSize = 1024*1024*1024*10;
$swMemoryLimit = 100000000;

$swNewUserFormFields = array();
$swAllUserRights = '[[_view::Main]] ';
$swNewUserRights = '[[_view::Main]] ';
$swNewUserEnable = false;
$swNotifyMail = '';
$swNotifyActions = array();

$swSkins['default'] = $swRoot.'/inc/skins/default.php';
$swSkins['zeitung'] = $swRoot.'/inc/skins/zeitung.php';
$swSkins['wiki'] = $swRoot.'/inc/skins/wiki.php';
$swSkins['diary'] = $swRoot.'/inc/skins/diary.php';
$swSkins['law'] = $swRoot.'/inc/skins/law.php';
$swDefaultSkin = 'default';

$swDefaultLang = 'en';

$swMediaFileTypeDownload = '';




$db = new swDB;


if (file_exists($swRoot.'/site/configuration.php'))
{
	// SOFADOC_IGNORE
	$configuration = $swRoot.'/site/configuration.php';
	include_once $configuration;
} 
else
{
	/* SOFADOC_INCLUDE inc/configuration.php */
	include_once $swRoot.'/inc/configuration-install.php';
}


include_once $swRoot.'/inc/ramdisk.php';


if (defined('SOFAWIKIINDEX'))
{
	//require_once $swRoot.'/inc/cookies.php'; // 0.3.2 moved here, swMainName must have been set
	if ($swLangURL)
	{
		
		//derive lang from name
		$name = swGetArrayValue($_REQUEST,'name',$swMainName);
		$name = swSimpleSanitize($name); 
		if (substr($name,2,1)=='/')
		{
			$lang = substr($name,0,2);
			$name = substr($name,3);
			$_REQUEST['name'] = $name; // let index.php discover it itself
		}
		else
		{
			$lang = swHandleCookie("lang",$swDefaultLang,$swUserCookieExpiration); 
		}
		
	}
	else
	{
		$lang = swHandleCookie("lang",$swDefaultLang,$swUserCookieExpiration); 
	}
	$skin = swHandleCookie("skin",$swDefaultSkin,$swUserCookieExpiration);
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


/** 
 * Returns a page based by name and language. The page is loosly parsed (template, links and style)
 */

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

/** 
 * Returns a page in the system namespace based by name and language. The page is loosly parsed (template, links and style)
 */

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
	{
		$langmsg =  $msg.'/'.$lang;
		$langmsgurl =  swNameURL($msg).'/'.$lang;
		$langen =  swNameURL($msg).'/en';
	}
	else
	{
		$langmsg =  $msg.'/'.$swDefaultLang;
		$langmsgurl =  swNameURL($msg).'/'.$swDefaultLang;
		$langen =  swNameURL($msg).'/en';
	}
		
	$w->name = 'System:'.$langmsgurl;
	
	// we try to look up only once these values per call - expensive file lookup	
	if (!array_key_exists($langmsg,$swSystemSiteValues))
	{
		$w->lookup(); 
		
		// site has defined a custom value as page  
		if ($w->visible())
		{
			$swSystemSiteValues[$langmsg] = $w->content;
		}
		
		// verbatim systemDefaults - obsolete  
		elseif (array_key_exists($langmsg,$swSystemDefaults))
		{
			$swSystemSiteValues[$langmsg] = $swSystemDefaults[$langmsg];
		}
		
		// urlname systemDefaults   
		elseif (array_key_exists($langmsgurl,$swSystemDefaults))
		{
			$swSystemSiteValues[$langmsg] = $swSystemDefaults[$langmsgurl];
		}
		
		// english
		elseif (array_key_exists($langen,$swSystemDefaults))
		{
			$swSystemSiteValues[$langmsg] = $swSystemDefaults[$langen];
		}

		
		// nor found, return texto
		else
		{
			$swSystemSiteValues[$langmsg] = $msg;
		}
	}
	$w->parsedContent = $swSystemSiteValues[$langmsg];
	
	if ($styled) 
	{
		 if (stristr($w->parsedContent,'{{')) $t->dowork($w); 
		 $ti->dowork($w); 
		 $tl->dowork($w);
		 $ts->dowork($w);
	}
	
	$t = $w->parsedContent;
	
	return $t; 

}






?>