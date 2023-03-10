<?php

/**
 *	Main entry point 
 *	
 *  @author Matthias Bürcher 2010 matti@belle-nuit.com
 *  @link https://www.sofawiki.com
 *	@version 3.8.5
 *  
 */

ob_start(); // protect header for cookies
error_reporting(E_ERROR | E_WARNING | E_PARSE);
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
$swLazy = true; // implement lazy writing, no filter update when new records are written
$swHasWritten = false;
$swError = '';
$swStatus = '';
$swParseSpecial = '';
$swUserCookieExpiration = 4*60*60;
$swBaseHrefFolder = '';
$swBaseHrefFolder = @$_SERVER['SCRIPT_URI'];
define('SOFAWIKIINDEX',true);

include 'api.php';

if (swGetDeny($_SERVER['REMOTE_ADDR'])) 
{
   die('invalid acces '.$_SERVER['REMOTE_ADDR']);
} 

// echotime(print_r($_COOKIE, true));

// to keep session longer than some minutes use in .htaccess php_value session.cookie_lifetime 0 
session_name('PHPSESSION_'.@$swCookiePrefix);
session_start();



//swCookieTest('index41');


// get valid globals

// general

$name = swGetArrayValue($_REQUEST,'name',$swMainName); 
$name = swSimpleSanitize($name); // XSS
if (!$name) $name = $swMainName;
$name2 = swGetArrayValue($_REQUEST,'name2',$name);
$action = swGetArrayValue($_REQUEST,'action');
if (!$action) $action = 'view';
$query = swGetArrayValue($_REQUEST,'query');
$query = swSimpleSanitize($query); // XSS
$content = swGetArrayValue($_REQUEST,'content');
$comment = swGetArrayValue($_REQUEST,'comment');	

$name = str_replace("\\",'',$name);	
$name2 = str_replace("\\",'',$name2);	
$comment = 	str_replace("\\",'',$comment);

$ip = swGetArrayValue($_SERVER,'REMOTE_ADDR');
$referer = swGetArrayValue($_SERVER,'HTTP_REFERER');

// use only server referer
$referer = preg_replace("$\w*://([^/]*)/(.*)$","$1",$referer);
	
// editing
$revision = swGetArrayValue($_REQUEST,'revision',0);	
$content = swGetArrayValue($_REQUEST,'content',0);	

// submits overrides action
if (swGetArrayValue($_REQUEST,'submitmodify',false))
	$action = 'modify';
if (swGetArrayValue($_REQUEST,'submitmodifymulti',false))	
	$action = 'modifymulti';
if (swGetArrayValue($_REQUEST,'submiteditor',false))	
	$action = 'modifyeditor';
if (swGetArrayValue($_REQUEST,'submitcancel',false))	
	$action = 'view';

if (substr($name,0,strlen('rest/api'))=='rest/api') 
{
	$action = 'rest';
	$_REQUEST['q'] = substr($name,strlen('rest/api'));
}


// fix trailing and leading spaces
$name = trim($name);
$name2 = trim($name2);

if (!isset($powerusers))
{
	$powerusers[] = $poweruser;
}

// create user
$knownuser = false;
$username = swHandleCookie('username','',$swUserCookieExpiration); 
if ($action == 'logout') 
{
	unset($_SESSION[$swMainName.'-username']);
	swSetCookie('username','',1000);
	swSetCookie('passwordtoken','',1000);
	$username = ''; $pass = '';
	
	session_write_close();
	session_name('PHPSESSION_'.@$swCookiePrefix);
	session_start();

}
if(isset($_SESSION[$swMainName.'-username'])&& $_SESSION[$swMainName.'-username'] != '')
{	
	$knownuser = true; 
	$username = $_SESSION[$swMainName.'-username']; 
	$passwordtoken = md5(swNameURL($username).date('Ymd',time()).$swEncryptionSalt);
	swSetCookie('passwordtoken',$passwordtoken,$swUserCookieExpiration);
// 	echotime('session');
}
elseif($username && swHandleCookie('passwordtoken') == md5(swNameURL($username).date('Ymd',time()).$swEncryptionSalt))
{	
	$knownuser = true; 
// 	echotime('passwordtoken');
}
elseif($username && swHandleCookie('passwordtoken') == md5(swNameURL($username).date('Ymd',time()-24*60*60).$swEncryptionSalt))
{	
	$knownuser = true;
// 	echotime('passwordtoken1');
} 

$altuser = swHandleCookie('altuser','',$swUserCookieExpiration);


if (isset($_REQUEST['submitlogin'])) $knownuser = false;


//echo $username; echo ' '.swGetCookie('passwordtoken'); echo ' '.md5(swNameURL($username).date('Ymd',time()).$swEncryptionSalt); 
//echo '<p>'.swNameURL($username).date('Ymd',time()).$swEncryptionSalt;
//echo ' ('.$knownuser.')';


if($knownuser && substr($username,0,3) != 'ip.')
{
    //echo " known "; 
    $found=false;
   	foreach($powerusers as $p)
    {
   		if ($found) continue;
   		if ($username == $p->username)
		{
			$user = $p;
			$user->name = 'User:'.$username;
			$found = true;
			
			error_reporting(E_ALL);
			ini_set("display_errors", 1); 

		}
	}
	
	if (!$found)
	{
		$user = new swUser;
		
		$user->name = 'User:'.$username;
		$user->lookup();
		$user->username = $username;
		
		if (substr($username,0,3) == 'ip.')
			$user->ipuser = true;
		
		if (!$user->visible())
		{
			$username = '';
			$user->username = '';
		}
		
		error_reporting(0);
		ini_set("display_errors", 0); 

	}
	if ($action=='login' & !$user->ipuser) $action='view'; // do not stay in login 
		
}
else
{
	//echotime(swNameURL($username).date('Ymd',time()).$swEncryptionSalt);
	//echotime('needed '.md5(swNameURL($username).date('Ymd',time()).$swEncryptionSalt));
	//echotime('cookie '.swGetCookie('passwordtoken'));
	

	
	
	if (array_key_exists('username', $_REQUEST)) 
		$username = trim($_REQUEST['username']);
		
	if (array_key_exists('pass', $_REQUEST)) 
		$pass = trim($_REQUEST['pass']);

	else 
		$pass = '';
		
	$found = false;
	foreach($powerusers as $p)
    {
       	if ($found) continue;
       	if ($username == $p->username)
		{
			$user = $p;
			$user->name = 'User:'.$username;
			$user->pass = $pass;
			$found = true;
			error_reporting(E_ALL);
			ini_set("display_errors", 1); 
		}
	}
		
	if (!$found)
    {
		$user = new swUser;
		$user->username = $username;
		$user->name = 'User:'.$username;
		if ($username != '')
			$user->lookup();
		$user->pass = $pass;
		
		if ($user->revision) $found = true;
	}
	
	
	
	if (!$found && $action != 'login')
	{
		// check for ip-users
		
		$username = 'ip.'.$ip;
				
		$user = new swUser;
		$user->username = $username;
		$user->name = 'User:'.$username;
		if ($username != '')
			$user->lookup();
		$user->pass = $pass;
		
		if ($user->visible())
		{
			$user->ipuser = true;
		}
		else
		{
			// check for ip-ranges
			
			$q = 'filter _namespace "user", _name "user:ip."
select _name regex "-"
project _name';
			
			$list = swRelationToTable($q);
			
			//print_r($list);
			
			foreach($list as $elem)
			{
				$n = $elem['_name'];
				$n = substr($n,strlen('User:ip.'));
				$ips = explode('-',$n);
				$ip1 = $ips[0];
				$ip2 = $ips[1];
				$fields1 = explode('.',$ip1);
				$fields2 = explode('.',$ip2);
				$fieldsip = explode('.',$ip);
				$hex1 = trim(sprintf('%02x%02x%02x%02x', $fields1[0], $fields1[1], $fields1[2], $fields1[3]));
				$hex2 = trim(sprintf('%02x%02x%02x%02x', $fields2[0], $fields2[1], $fields2[2], $fields2[3]));
				$hexip = trim(sprintf('%02x%02x%02x%02x', $fieldsip[0], $fieldsip[1], $fieldsip[2], $fieldsip[3]));
								
				if (hexdec($hexip) >= hexdec($hex1) && hexdec($hexip) <= hexdec($hex2) )
				{
					$found = true; 
					$user->name = 'User:ip.'.$n;
					$user->lookup();
					$user->pass = $pass;
					$user->ipuser = true;
					$username = 'ip.'.$ip;
					break;
				}
				
			}
			
		}
		
	
	}
	if (!$found)
	{
		$username = ''; // failed
	}
	
	
	
	
	$user->name = 'User:'.$username;
// 	echotime('valid? '.$pass );
// 	echotime(swNameURL($username).date('Ymd',time()).$swEncryptionSalt);
	if ($user->validpassword())
	{
		
		$_SESSION[$swMainName.'-username'] = $username;
		$passwordtoken = md5(swNameURL($username).date('Ymd',time()).$swEncryptionSalt);
// 		echotime('valid '.$passwordtoken);
		swSetCookie('passwordtoken',$passwordtoken,$swUserCookieExpiration);
		$user->username = $username = $user->nameshort();
		$action='view';
		// echo 'valid';
		
	}
    else
    {
		
		
		if ($username != '' && $action != 'lostpassword' && $action != 'lostpasswordsubmit'
		&& (array_key_exists('username', $_GET) || array_key_exists('username', $_POST) ) )
		{			
			$swError = swSystemMessage('wrong-password-error',$lang); 
			$failaction = swGetArrayValue($_REQUEST,'failaction','login');
			$action = $failaction;
			swLogWrongPassword($_SERVER['REMOTE_ADDR']);
		}
		// log bots who are asking often for login without password
		elseif (@$swStrongDeny>0 && $action == 'login')
		{
			if (rand(0,100) < $swStrongDeny) swLogWrongPassword($_SERVER['REMOTE_ADDR']);
		}
		
		
		$user = new swUser;
		$user->name = '';
		$user->pass = '';
		$user->content = $swAllUserRights;
		
		$username = '';
		
    }
 
}

// swCookieTest('index291');

if ($user->name != '' && isset($altuser) && $altuser != '')
{
	$user2 = new swUser;
	$user2->name = 'User:'.$altuser;
	$user2->lookup();
	if ($user2->exists()) {
		$realuser = $user;
		$user = $user2;
		$user->username = $username = $user->nameshort();
	}
	
}

if ($action == 'logout')
{
	$user = new swUser;
	$user->name = '';
	$user->pass = '';
	$user->content = $swAllUserRights;
	unset($realuser);
}

echotime('user '.$username);




//session_write_close(); 1.9.0 moved down to end

// add searchnamespaces by user rights
$viewdomains = swGetValue($user->content,'_view'.$swAllUserRights,true);
{
	foreach($viewdomains as $v)
	{
		 // filter out right period
		 if (stristr($v,'|'))
		 {
		 	$fields = explode('|',$right);
		 	$v = $fields[0];
		 }
		 
		 $swSearchNamespaces[] = $v;
		 $swTranscludeNamespaces[] = $v;
	}
}

$transcludedomains = swGetValue($user->content.$swAllUserRights,'_transclude',true);
{
	foreach($transcludedomains as $v)
	{
		 // filter out right period
		 if (stristr($v,'|'))
		 {
		 	$fields = explode('|',$right);
		 	$v = $fields[0];
		 }
		 $swTranscludeNamespaces[] = $v;
	}
}

// create wiki
$wiki = new swWiki;
$wiki->name = $name;
$wiki->comment = $comment;
$wiki->parsers = $swParsers;
$swRedirectedFrom = '';
if(isset($_GET['redirectedfrom'])) $swRedirectedFrom  = $_GET['redirectedfrom'];


if (substr($name,0,8) == 'special:' && $action != 'login')
{
	$action = 'special';
}
elseif ($action != 'new')
{
	
	if ($revision) 
	{
		// we will only allow this if user has right to view the history
		
		if ($user->hasright('modify', $wiki->name) ||
		$user->hasright('protect', $wiki->name) || $user->hasright('delete', $wiki->name)) 
		{
			$wiki->revision=$revision;
		}
		else
		{
			$swError = swSystemMessage('no-access-error',$lang);
			$swFooter = '';
		}
	}
	

	$wiki->lookup(); // strict
	
	

	if ($wiki->error != '')
	{
		switch ($wiki->error)
		{ 
			case 'No record with this name': 
				// ignore if the page is here anyway
				if ($wiki->content != '') break;
				
				if ($action != 'modify' && $action != 'modifymulti' && !substr($wiki->name,0,7 != 'system:') ) 
				{
					$swError =swSystemMessage('this-page-does-not-exist-error',$lang); 
				}
				break;		
			case 'Deleted record': $swError = swSystemMessage('this-page-has-been-deleted-error',$lang); break;
			default:
			$swError = $wiki->error;
		}
	}
}

if ($revision && $name == $swMainName) $name = $wiki->name;

$swStatus = '';
$swParsedName = '';
$swParsedContent = '';
$swParsedCSS = '';
$swFooter = '';





echotime('action '.$action);

if ($swIndexError 
	&& 100*$db->indexedbitmap->countbits()/max(1,$db->GetLastRevisionFolderItem()) < 99 
	&& $db->GetLastRevisionFolderItem() > 100 )
		$action = 'indexerror';
	
if ($action == 'logout')
{
	$failaction = swGetArrayValue($_REQUEST,'failaction','logout');
	$action = $failaction;
}



switch ($action)
{
	case 'askemailaccess':   		include 'inc/special/askemailaccess.php';
				 	 				break;
	case 'emailaccess':    			include 'inc/special/emailaccess.php';
				 	 				break;
	case 'delete': 					if ($user->hasright('delete', $wiki->name)) 
									{
										include 'inc/special/delete.php';
									}
									else
									{
										$swError = swSystemMessage('no-access-error',$lang);
										include 'inc/special/view.php';										
									}
									
								    break;
	case 'diff':					if ($user->hasright('view', $wiki->name))
									{
										include 'inc/special/diff.php';
									}
									else
									{
									  	$swError = swSystemMessage('no-access-error',$lang);
									  	include 'inc/special/view.php';
									}
								    break;
	case 'download': 				if (stristr($swBaseHrefFolder,$referer))
									{
										include 'inc/special/download.php';
									}
									else
									{
										$swError = swSystemMessage('no-access-error',$lang);
										include 'inc/special/view.php';										
									}
									
								    break;
	case 'edit':
	case 'editmulti':    
	case 'editsource': 				if ($user->hasright('modify', $wiki->name))
									{
										include 'inc/special/edit.php';
									}
									else
									{
										$swError = swSystemMessage('no-access-error',$lang);
										include 'inc/special/view.php';
									}
									break;
	case 'fields': 					if ($user->hasright('fields', $wiki->name))
									{
										include 'inc/special/fields.php';
									}
									else
									{
									  	$swError = swSystemMessage('no-access-error',$lang);
									  	include 'inc/special/view.php';
									}
								    break;
	case 'history':	 				if ($user->hasright('view', $wiki->name))
									{
										include 'inc/special/history.php';
									}
									else
									{
									  	$swError = swSystemMessage('no-access-error',$lang);
									  	include 'inc/special/view.php';
									}
								    break;
	case 'indexerror':				include 'inc/special/indexerror.php';
									break;
	case 'install': 				include_once 'inc/special/install.php';
				 	 				break;
	case 'login':       			include 'inc/special/login.php';
				 	 				break;
	case 'logout':   				
					 				$swStatus = swSystemMessage('you-have-logged-out',$lang);
					 				$wiki->name = $swMainName;

					 				include 'inc/special/view.php';
					 				break;
	case 'lostpassword':    
	case 'lostpasswordsubmit':    	include 'inc/special/lostpassword.php';
				 	 				break;
	case 'modify':	
	case 'modifymulti':	
	case 'modifyeditor':			include 'inc/special/modify.php';
									break;	

	case 'new':      				$wiki=new swWiki; $name = '';
									if ($user->hasright('create', $wiki->name))
									{
										include 'inc/special/edit.php';
									}
									else
									{
										$swError = swSystemMessage('no-access-error',$lang);
										include 'inc/special/view.php';
									}
	case 'newuser':    			
	case 'newusersubmit': 			include 'inc/special/newuser.php';
				 	 				break;
	case 'protect': 
	case 'unprotect':				if ($user->hasright('protect', $wiki->name))
									{
										include 'inc/special/protect.php';	
									}
									else
									{
										$swError = swSystemMessage('no-access-error',$lang);
								    }
									include 'inc/special/view.php';
									break;						
	case 'rename':					if ($user->hasright('rename', $wiki->name)) 
									{
										include 'inc/special/rename.php';
									}
									else
									{
										$swError = swSystemMessage('no-access-error',$lang);
										include 'inc/special/view.php';
									}
								    break;

	case 'resetpassword':    		include 'inc/special/resetpassword.php';
				 	 				break;
	case 'rest':    				include 'inc/special/rest.php';
				 	 				break;
	case 'revert':					if ($user->hasright('modify', $wiki->name))
									{
										
										$wiki->user = $user->nameshort();
										$wiki->insert();
										
										$swParsedName = $wiki->name;
										$swStatus = 'Reverted: '.$wiki->name;
										$swParsedContent = $wiki->parse();
			
									}
									else
									{
										$swError = swSystemMessage('no-access-error',$lang);
									}
									break;
	case 'snapshot':   				if ($user->hasright('upload', ''))
									{
										include 'inc/special/snapshot.php';
									}
									else
									{
										$swError = swSystemMessage('no-access-error',$lang);
									}
									break;
	case 'search':					include 'inc/special/search.php';
									break;
	case 'special':  				if ($user->hasright('special',str_replace('special:','',$name)) || $user->hasright('special','special') )
									{
										$specialname = substr($name,8);
										$swParseSpecial = false;
										$specialfound = false;
											foreach ($swSpecials as $k=>$v)
											{
												if (swNameURL($k) == $specialname)
												{
													
													/* SOFADOC_INCLUDE inc/special/allpages.php */
													/* SOFADOC_INCLUDE inc/special/backup.php */
													/* SOFADOC_INCLUDE inc/special/categories.php */
													/* SOFADOC_INCLUDE inc/special/dead-end-pages.php */
													/* SOFADOC_INCLUDE inc/special/deleted-pages.php */
													/* SOFADOC_INCLUDE inc/special/deny.php */
													/* SOFADOC_INCLUDE inc/special/fieldsearch.php */
													/* SOFADOC_INCLUDE inc/special/images.php */
													/* SOFADOC_INCLUDE inc/special/import.php */
													/* SOFADOC_INCLUDE inc/special/indexes.php */
													/* SOFADOC_INCLUDE inc/special/info.php */
													/* SOFADOC_INCLUDE inc/special/logs.php */
													/* SOFADOC_INCLUDE inc/special/long-pages.php */
													/* SOFADOC_INCLUDE inc/special/metrics.php */
													/* SOFADOC_INCLUDE inc/special/most-linked-categories.php */
													/* SOFADOC_INCLUDE inc/special/most-linked-pages.php */
													/* SOFADOC_INCLUDE inc/special/orphaned-pages.php */
													/* SOFADOC_INCLUDE inc/special/passwords.php */
													/* SOFADOC_INCLUDE inc/special/protected-pages.php */
													/* SOFADOC_INCLUDE inc/special/query.php */
													/* SOFADOC_INCLUDE inc/special/recent-changes.php */
													/* SOFADOC_INCLUDE inc/special/regex.php */
													/* SOFADOC_INCLUDE inc/special/relation.php */
													/* SOFADOC_INCLUDE inc/special/rest.php */
													/* SOFADOC_INCLUDE inc/special/snapshot.php */
													/* SOFADOC_INCLUDE inc/special/special-pages.php */
													/* SOFADOC_INCLUDE inc/special/system-messages.php */
													/* SOFADOC_INCLUDE inc/special/templates.php */
													/* SOFADOC_INCLUDE inc/special/tickets.php */
													/* SOFADOC_INCLUDE inc/special/uncategorized-pages.php */
													/* SOFADOC_INCLUDE inc/special/unused-categories.php */
													/* SOFADOC_INCLUDE inc/special/unused-files.php */
													/* SOFADOC_INCLUDE inc/special/update.php */
													/* SOFADOC_INCLUDE inc/special/upload.php */
													/* SOFADOC_INCLUDE inc/special/upload-multiple.php */
													/* SOFADOC_INCLUDE inc/special/users.php */
													/* SOFADOC_INCLUDE inc/special/wanted-pages.php */
													
													include 'inc/special/'.$swSpecials[$k];
													$specialfound = true;
												}
											}
											unset($k);
											unset($v);
											if (!$specialfound && !substr($wiki->name,0,7 != 'system:') )
												$swError = swSystemMessage('this-page-does-not-exist-error',$lang); 
										
										
									}
									elseif ( $user->hasright('upload','') && $name=='special:upload' )
									{
										include 'inc/special/upload.php';
									}
									else
									{
										$swError = swSystemMessage('no-access-error',$lang);
									}
			
									if ($swParseSpecial)
									{
										$wiki->content = $swParsedContent;
										$swParsedContent = $wiki->parse();
									}
			
									break;	
	case 'upload':   				if ($user->hasright('upload', ''))
									{
									 	include 'inc/special/upload.php';
									}
									else
									{
										$swError = swSystemMessage('no-access-error',$lang);
									}
								 	break;

	case 'uploadfile':   			if ($user->hasright('upload', ''))
									{
									 	include 'inc/special/uploadfile.php';
									}
									else
									{
										$swError = swSystemMessage('no-access-error',$lang);
									}
								 	break;
	
	case 'uploadbigfile':   		if ($user->hasright('upload', '') || stristr($swBaseHrefFolder,$referer))
									{
									 	include 'inc/special/uploadbigfile.php';
									}
									else
									{
										$swError = swSystemMessage('no-access-error',$lang);
									}
								 	break;

						
	case 'whatlinkshere' : 			include 'inc/special/whatlinkshere.php';
									break;

	
		
	default: 						
									if (isset($swActionHookFile))
									{
										include $swActionHookFile;
									}
									else
									{	
										include 'inc/special/view.php';
									}

}

ob_end_flush();

if ($swRedirectedFrom)
{
	$swStatus = 'Redirected from '.$swRedirectedFrom;
	$swParsedName = $wiki->namewithoutlanguage();
}
	
	

// build menus

// create menus
echotime("menus"); 
{
	$linkwiki = new swWiki;
	$linkwiki->name = $swMainName; 
	$swHomeMenu = '<a href="'.$linkwiki->link('view',$lang).'" rel="nofollow">'.swSystemMessage('home',$lang).'</a>';
}


$swLoginMenus= array();
 
if ($user->username != "" || isset($realuser))
{
		
		// add menus by user rights
		$menudomains = swGetValue($user->content,'_menu',true);
		{
			foreach($menudomains as $v)
			{
				$swLoginMenus[$v] = '<a href="index.php?name='.$v.'" rel="nofollow">'.$v.'</a>';
			}
		}

		$swLoginMenus['login-user'] = $user->nameshort();
		if ($user->ipuser)
		$swLoginMenus['login'] = '<a href="index.php?action=login&lang='.$lang.'" rel="nofollow">'.swSystemMessage('login',$lang).'</a>';
		else
		{
			if (!isset($realuser))
			$swLoginMenus['login-logout'] = '<a href="index.php?action=logout&lang='.$lang.'" rel="nofollow">'.swSystemMessage('logout',$lang).'</a>';
		}
		
		$altuserlist = $user->altusers();
		if (is_array($altuserlist) && count($altuserlist)>0)
		{
			foreach($altuserlist as $elem)
			$swLoginMenus['login-altuser_'.$elem] = '- <a href="index.php?action=view&altuser='.$elem.'&lang='.$lang.'" rel="nofollow">'.$elem.'</a>';
		}
		if (isset($realuser))
		{
			$altuserlist = $realuser->altusers();
			$swLoginMenus['login-altuser_'.$realuser->nameshort()] = '! <a href="index.php?action=view&altuser&lang='.$lang.'" rel="nofollow">'.$realuser->nameshort().'</a>';
			foreach($altuserlist as $elem)
			$swLoginMenus['login-altuser_'.$elem] = '- <a href="index.php?action=view&altuser='.$elem.'&lang='.$lang.'" rel="nofollow">'.$elem.'</a>';
			
		}
		
}
else
{
	$swLoginMenus['login-login'] = '<a href="index.php?action=login&amp;name='.$name.'&lang='.$lang.'" rel="nofollow">'.swSystemMessage('login',$lang).'</a>';
}		

$swEditMenus = array();



if ($action != 'special' && $action != 'login' && $action != 'logout' && $action!='search')
{
	$editwiki = new swWiki;
	$editwiki->name = $name;
	$el = $editwiki->language();
	if ($el == '--') $el = '';
	
	$wikinames = array();
	$n = $wiki->namewithoutlanguage();
	$editwiki->name = $n;
	$editwiki->lookup();
	
	// view
	if ($user->hasright('view', $editwiki->name) )
	{
		if ($editwiki->revision)
		{
			$swEditMenus['viewmenu-article'] = '<a href="'.$editwiki->link('view','--').'" rel="nofollow" accesskey="v">'.swSystemMessage('view',$lang).'</a>';
			$swEditMenus['viewmenu-history'] = '<a href="'.$editwiki->link('history','--').'" rel="nofollow">'.swSystemMessage('history',$lang).'</a>';
		}
		
		foreach ($swLanguages as $l)
		{
			$wiki2 = new swWiki;
			$wiki2->name = $n.'/'.$l; 
			$wiki2->lookup();
						
			if ($wiki2->revision) 
			{
				if (!$editwiki->revision)
					$swEditMenus['viewmenu-article-'.$l] = '<a href="'.$editwiki->link('view',$l).'" rel="nofollow">'.swSystemMessage('view',$lang).' '.$l.'</a>';
				
				$swEditMenus['viewmenu-history-'.$l] = '<a href="'.$editwiki->link('history',$l).'" rel="nofollow">'.swSystemMessage('history',$lang).' '.$l.'</a>';
			}		
		}
		$swEditMenus['viewmenu-whatlinkshere'] = '<a href="'.$editwiki->link('whatlinkshere','--').'" rel="nofollow">'.swSystemMessage('what-links-here',$lang).'</a>';	
	}	
	
	if ($user->hasright('modify', $editwiki->name) && $editwiki->status == 'ok') 
	{
		$swEditMenus['editmenu-edit'] = '<a href="'.$editwiki->link('edit','--').'" rel="nofollow" accesskey="e">'.swSystemMessage('edit',$lang).'</a>';
		if ($swRedirectedFrom)
		{
			$swEditMenus['editmenu-edit-redirected'] = '<a href="index.php?action=edit&lang='.$lang.'&name='.$swRedirectedFrom.'" rel="nofollow" >'.swSystemMessage('edit',$lang).' Redirection</a>';

		}
		
		
		if (isset($editwiki->internalfields['editortemplate']))
		{
			$swEditMenus['editmenu-source'] = '<a href="'.$editwiki->link('editsource','--').'" rel="nofollow">'.swSystemMessage('edit-source',$lang).'</a>';
		}
		$swEditMenus['editmenu-multi'] = '<a href="'.$editwiki->link('editmulti','--').'" rel="nofollow">'.swSystemMessage('edit-multi',$lang).'</a>';

	}
	foreach ($swLanguages as $l)
	{
		$editwiki = new swWiki;
		$editwiki->name = $n.'/'.$l; 
		$editwiki->lookup();
		if ($user->hasright('modify', $editwiki->name) && $editwiki->status == 'ok') 
		{
			//print_r($editwiki);
			$swEditMenus['editmenu-edit-'.$l] = '<a href="'.$editwiki->link('edit',$l).'" rel="nofollow">'.swSystemMessage('edit',$lang).' '.$l.'</a>';
		}		
	}	
		
	$el = $wiki->language();
	if ($el == '--') $el = '';
	
	if ($editwiki->status == 'ok')
	{
		if ($user->hasright('fields', $wiki->name))
		{
			$swEditMenus['editmenu-fields'] = '<a href="'.$wiki->link('fields','--').'" rel="nofollow">'.swSystemMessage('fields',$lang).'</a>';
		}		
		if ($user->hasright('protect', $editwiki->name))
		{
			$swEditMenus['editmenu-protect'] = '<a href="'.$wiki->link('protect','--').'" rel="nofollow">'.swSystemMessage('protect',$lang).'</a>';
		}
		if ($user->hasright('rename', $editwiki->name))
		{
			$swEditMenus['editmenu-rename'] = '<a href="'.$wiki->link('rename','--').'" rel="nofollow">'.swSystemMessage('rename',$lang).' </a>';
		}
		if ($user->hasright('delete', $editwiki->name))
		{
			$swEditMenus['editmenu-delete'] = '<a href="'.$wiki->link('delete','--').'" rel="nofollow">'.swSystemMessage('delete',$lang).'</a>';
		}
	}
	elseif ($editwiki->status == 'protected')
	{
		$swEditMenus['editmenu-unprotect'] = '<a href="'.$wiki->link('unprotect','--').'" rel="nofollow">'.swSystemMessage('unprotect',$lang).'</a>';
	}
	if ($editwiki->status != 'ok' && $wiki->status == 'ok')
	{
		if ($user->hasright('rename', $editwiki->name))
		{
			$swEditMenus['editmenu-rename'] = '<a href="'.$wiki->link('rename','--').'" rel="nofollow">'.swSystemMessage('rename',$lang).' </a>';
		}
		if ($user->hasright('delete', $editwiki->name))
		{
			$swEditMenus['editmenu-delete'] = '<a href="'.$wiki->link('delete','--').'" rel="nofollow">'.swSystemMessage('delete',$lang).'</a>';
		}
		
	}
	
	
	
}	


	
if ($user->hasright('create', '*'))
{
	if ($action != 'special' && $action != 'modify' && $action != 'new' && $wiki->status == '')
		$swEditMenus['newmenu-new'] = '<a href="'.$wiki->link('edit').'&lang='.$lang.'" rel="nofollow" accesskey="n">'.swSystemMessage('new-page',$lang).'</a>';
	else
		$swEditMenus['newmenu-new'] = '<a href="index.php?action=new&lang='.$lang.'" rel="nofollow" accesskey="n">'.swSystemMessage('new-page',$lang).' </a>';
	
	$templatewiki = new swWiki;
	$templatewiki->name = 'System:editortemplate';
	$templatewiki->lookup();
	$list = array();
	if (isset($templatewiki->internalfields['_link'])) $list = $templatewiki->internalfields['_link'];
	
	$menu = array();
	
	foreach($list as $elem)
	{
		if (substr($elem,0,strlen('Template:'))!='Template:') continue;
		$editor = str_replace('Template:','',$elem);
		$swEditMenus['newmenu-'.$editor] = '<a href=index.php?action=new&editor='.$editor.'">'.swSystemMessage('new',$lang).' '.$editor.'</a>';
	}
}

if ($user->hasright('upload','') && $action != 'logout')
{
	$linkwiki = new swWiki;
	$linkwiki->name = 'special:upload'; 
	$swEditMenus['newmenu-upload'] = '<a href="'.$linkwiki->link('view','').'&lang='.$lang.'" rel="nofollow" accesskey="U">'.swSystemMessage('upload',$lang).'</a>';
}

if ($user->hasright('special','special') && $action != 'logout')
{	
	ksort($swSpecials);
	
	// force list first
	$k = 'All Pages';
	$linkwiki->name = 'Special:'.$k; 
	$m = 'listmenu-';
	$swEditMenus[$m.$k] = '<a href="'.$linkwiki->link('view','').'&lang='.$lang.'" rel="nofollow">'.$k.'</a>';
	
	foreach ($swSpecials as $k=>$v)
	{
		$linkwiki->name = 'Special:'.$k; 
		$m = 'specialmenu-';
		if (in_array($k, array('All Pages','Categories','Dead End Pages','Deleted Pages','Images','Long Pages','Most Linked Categories','Most Linked Pages', 'Orphaned Pages', 'Protected Pages', 'Redirects', 'Short Pages', 'System Messages ','Templates', 'Uncategorized Pages', 'Unused Categories', 'Unused Files', 'Unused Templates', 'Users' , 'Wanted Pages'))) $m = 'listmenu-';
		$swEditMenus[$m.$k] = '<a href="'.$linkwiki->link('view','').'&lang='.$lang.'" rel="nofollow">'.$k.'</a>';

	}
}


if (isset($swAdditionalEditMenus))
{
	foreach($swAdditionalEditMenus as $k=>$v) $swEditMenus[$k] = $v;
}	
	

if (!$username) $swEditMenus = array();


if (!isset ($swLangMenus)) $swLangMenus = array();
if (count($swLanguages)>1)
foreach ($swLanguages as $v)
{
		if (!isset($swLangMenus[$v])) // interlanguage link already defined by link parser
		{
		
		if ($swLangURL)
			$swLangMenus[$v] = '<a href="'.$wiki->link('view',$v).'">'.swSystemMessage($v,$lang).'</a>';
		else
			$swLangMenus[$v] = '<a href="'.$wiki->link('view','--').'&amp;lang='.$v.'">'.swSystemMessage($v,$lang).'</a>';
			
		}
}

unset($v);
$swSearchMenu = '<div id="searchmenu">
<form method="get" action="index.php">
<input type="hidden" name="action" value="search" />
<input type="text" class="searchfield" name="query" value="'.$query.'"/>
<input type="submit" class="searchbutton" name="submit" value="'.swSystemMessage('search',$lang).'" /> 
</form>
</div><!-- searchmenu -->';

$swSingleSearchMenu = '<div id="singlesearchmenu">
<form method="get" action="index.php">
<input type="hidden" name="action" value="search" />
<input type="text" class="singlesearchfield" name="query" placeholder="'.swSystemMessage('search',$lang).'..."value="'.$query.'"/>
</form>
</div><!-- searchmenu -->';

flush();

if (isset($swOvertime) && $swOvertime)
{
	if (isset($_POST) || @$swPreventOvertimeSearchAgain)
		$swParsedContent .= '<div id="searchovertime">'.swSystemMessage('search-limited-by-timeout.',$lang);
	else
		$swParsedContent .= '<div id="searchovertime">'.swSystemMessage('search-limited-by-timeout.',$lang).' <a href="index.php?action='.$action.'&name='.$name.'&query='.$query.'">'.swSystemMessage('search-again',$lang).'</a></div>';
}
else
{
	if ($action != 'indexerror')
	{
		if (!$swError) swIndexFulltext(swNameURL($name),$lang,$wiki->revision,$swParsedName,$swParsedContent);
		echotime('cron');
		swAsyncCron();
	}	
}

$db->close(); 
session_write_close();

echotime('skin');
echomem('peak', true);

/* SOFADOC_INCLUDE inc/skins/default.php */
/* SOFADOC_INCLUDE inc/skins/diary.php */
/* SOFADOC_INCLUDE inc/skins/iphone.php */
/* SOFADOC_INCLUDE inc/skins/law.php */
/* SOFADOC_INCLUDE inc/skins/tele.php */
/* SOFADOC_INCLUDE inc/skins/tribune.php */
/* SOFADOC_INCLUDE inc/skins/wiki.php */
/* SOFADOC_INCLUDE inc/skins/zeitung.php */

// apply page skin

// fulltextindex




if (!array_key_exists($skin,$swSkins)) $skin = 'default';
if ($swSkins[$skin])
	$templatefile = $swSkins[$skin];
else
	$templatefile = $swSkins['default'];
if (file_exists($templatefile))
{
	include $templatefile;
}
else
	die('missing page template '.$templatefile);

flush();

// debug

if ($username != '')
	$u = $username;
else
	$u = $ip;
$endtime = microtime(true);

if ($endtime<$swStartTime) $endtime = $swStartTime;
$usedtime = sprintf('%04d',($endtime-$swStartTime)*1000);

if (function_exists('swLogHook')) 
{
	swLogHook($username,$name,$action,$query,$lang,$referer,$usedtime,$swError,'','','');
	
}
if (!isset($swOvertime))
	swUpdateRamDiskDB();

swLog($username,$name,$action,$query,$lang,$referer,$usedtime,$swError,'','','');

if ($swError && !$username && isset($swStrongDeny) && (rand(0,100) < $swStrongDeny)) swLogWrongPassword($_SERVER['REMOTE_ADDR']); // block anonymous users producing a lot of errors


swSemaphoreRelease();

/*
print_r($_COOKIE);
echo $username;
print_r($user);
*/
?>