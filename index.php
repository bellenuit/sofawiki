<?php

/*
	SofaWiki
	Matthias Buercher 2010 
	matti@belle-nuit.com
	
	index.php 
	main entry point
*/

error_reporting(E_ERROR | E_WARNING | E_PARSE);
ini_set("display_errors",1); 
$swLazy = true; // implement lazy writing, no filter update when new records are written
$swHasWritten = false;

define('SOFAWIKIINDEX',true);
include 'api.php';

if (swGetDeny($_SERVER['REMOTE_ADDR'])) 
{
   die('invalid acces '.$_SERVER['REMOTE_ADDR']);
} 


// to keep session longer than some minutes use in .htaccess php_value session.cookie_lifetime 0 
session_name('PHPSESSION_'.@$swCookiePrefix);
session_start();



// get valid globals

// general

$name = swGetArrayValue($_REQUEST,'name',$swMainName);
$name = swSimpleSanitize($name); // XSS
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
if (swGetArrayValue($_REQUEST,'submitcancel',false))	
	$action = 'view';
	

// fix trailing and leading spaces
$name = trim($name);
$name2 = trim($name2);

if (!isset($powerusers))
{
	$powerusers[] = $poweruser;
}



// create user
$knownuser = false;
if (!isset($swUserCookieExpiration))
	$swUserCookieExpiration = 4*60*60;
$username = handleCookie('username','',$swUserCookieExpiration);
if ($action == 'logout') 
{
	unset($_SESSION[$swMainName.'-username']);
	swSetCookie('username','',0);
	swSetCookie('passwordtoken','',0);
	$username = ''; $pass = '';
	
	session_write_close();
	session_name('PHPSESSION_'.@$swCookiePrefix);
	session_start();

}
if(isset($_SESSION[$swMainName.'-username'])&& $_SESSION[$swMainName.'-username'] != '')
{	$knownuser = true;  $username = $_SESSION[$swMainName.'-username']; }
if($username && swGetCookie('passwordtoken') == md5(swNameURL($username).date('Ymd',time()).$swEncryptionSalt))
{	$knownuser = true; }
if($username && swGetCookie('passwordtoken') == md5(swNameURL($username).date('Ymd',time()-24*60*60).$swEncryptionSalt))
{	$knownuser = true; } 

$altuser = handleCookie('altuser','',$swUserCookieExpiration);


if (isset($_REQUEST['submitlogin'])) $knownuser = false;

if($knownuser)
{
        $found=false;
       	foreach($powerusers as $p)
        {
       		if ($found) continue;
       		if ($username == $p->username)
			{
				$user = $p;
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
	
	if (!$found)
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
			$user->ipuser = true;
		else
			$username = ''; // failed
	
	}
	
	
	
	
	$user->name = 'User:'.$username;
	
	
	if ($user->validpassword())
	{
		
		$_SESSION[$swMainName.'-username'] = $username;
		$passwordtoken = md5(swNameURL($username).date('Ymd',time()).$swEncryptionSalt);
		swSetCookie('passwordtoken',$passwordtoken,$swUserCookieExpiration);
		$user->username = $username = $user->nameshort();
		$action='view';
		
	}
    else
    {
		
		
		if ($username != '' && $action != 'lostpassword' && $action != 'lostpasswordsubmit'
		&& (array_key_exists('username', $_GET) || array_key_exists('username', $_POST) ) )
		{			
			$swError = swSystemMessage('wrong-password-error',$lang);
			$action = "login";
			swLogWrongPassword($_SERVER['REMOTE_ADDR']);
		}
		$user = new swUser;
		$user->name = '';
		$user->pass = '';
		$user->content = $swAllUserRights;
		
		$username = '';
    }
}


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
				
				if ($action != 'modify' && $action != 'modifymulti') 
				{
					
					
					 
						$swError =$action.' '.swSystemMessage('this-page-does-not-exist-error',$lang).' '.$wiki->name; 
					
				}
				break;		
			case 'Deleted record': $swError = swSystemMessage('this-page-has-been-deleted-error',$lang); break;
			default:
			$swError = $wiki->error;
		}
	}
}

if ($revision && $name == $swMainName) $name = $wiki->name;

$swParsedName = '';
$swParsedContent = '';
$swParsedCSS = '';
$swFooter = '';


// create menus
echotime("menus"); 
{
	$linkwiki = new swWiki;
	$linkwiki->name = $swMainName; 
	$swHomeMenu = '<a href="'.$linkwiki->link('view',$lang).'" rel="nofollow">'.swSystemMessage('home',$lang).'</a>';
}

$swLangMenus = array();
if (count($swLanguages)>1)
foreach ($swLanguages as $v)
{
		if ($swLangURL)
			$swLangMenus[$v] = '<a href="'.$wiki->link('view',$v).'">'.swSystemMessage($v,$lang).'</a>';
		else
			$swLangMenus[$v] = '<a href="'.$wiki->link('view','--').'&amp;lang='.$v.'">'.swSystemMessage($v,$lang).'</a>';
}
unset($v);
$swSearchMenu = '<div id="searchmenu"><form method="get" action="index.php"><p>
 <input type="hidden" name="action" value="search" />
 <input type="text" class="searchfield" name="query" value="'.$query.'"/>
 <input type="submit" class="searchbutton" name="submit" value="'.swSystemMessage('search',$lang).'" /> 
 </p></form></div> ';

$swLoginMenus= array();
 
if ($user->username != "" || isset($realuser))
{
		$swLoginMenus['user'] = $user->nameshort();
		if ($user->ipuser)
		$swLoginMenus['login'] = '<a href="index.php?action=login&lang='.$lang.'" rel="nofollow">'.swSystemMessage('login',$lang).'</a>';
		else
		{
			if (!isset($realuser))
			$swLoginMenus['logout'] = '<a href="index.php?action=logout&lang='.$lang.'" rel="nofollow">'.swSystemMessage('logout',$lang).'</a>';
		}
		
		$altuserlist = $user->altusers();
		if (is_array($altuserlist) && count($altuserlist)>0)
		{
			foreach($altuserlist as $elem)
			$swLoginMenus['altuser-'.$elem] = '- <a href="index.php?action=view&altuser='.$elem.'&lang='.$lang.'" rel="nofollow">'.$elem.'</a>';
		}
		if (isset($realuser))
		{
			$altuserlist = $realuser->altusers();
			$swLoginMenus['altuser-'.$realuser->nameshort()] = '! <a href="index.php?action=view&altuser&lang='.$lang.'" rel="nofollow">'.$realuser->nameshort().'</a>';
			foreach($altuserlist as $elem)
			$swLoginMenus['altuser-'.$elem] = '- <a href="index.php?action=view&altuser='.$elem.'&lang='.$lang.'" rel="nofollow">'.$elem.'</a>';
			
		}
		
}
elseif ($action != 'login')
{
	$swLoginMenus['login'] = '<a href="index.php?action=login&amp;name='.$name.'&lang='.$lang.'" rel="nofollow">'.swSystemMessage('login',$lang).'</a>';
}		

$swEditMenus = array();


// page based edit menus
if ($action != 'special' && $action != 'login' && $action != 'logout' && $action!='search')
{
	// view
	if ($user->hasright('view', $wiki->name))
	{
		$swEditMenus['view'] = '<a href="'.$wiki->articlelink('editview').'&lang='.$lang.'" rel="nofollow">'.swSystemMessage('view',$lang).'</a>';
    }
		
	// edit
	if ($user->hasright('modify', $wiki->name) || 
	$user->hasright('protect', $wiki->name) || $user->hasright('delete', $wiki->name) || $action == 'modifymulti')
	{
	
		if (($wiki->status != '' && $wiki->status != 'deleted' && $wiki->status != 'delete') || $action == 'modifymulti' || $action == 'modify' ) // page does exist // delete is obsolete
		{
			if ($user->hasright('modify', $wiki->namewithoutlanguage()))
				$swEditMenus['edit'] = '<a href="'.$wiki->link('edit','--').'&lang='.$lang.'" rel="nofollow">'.swSystemMessage('edit',$lang).'</a>';
						
			if ($action == 'rename')
			{
				$w2 = new swWiki();
				$w2->name = $name2;
				$swEditMenus['edit2'] = '<a href="'.$w2->link('edit','').'&lang='.$lang.'" rel="nofollow">'.swSystemMessage('edit',$lang).' '.$name2.'</a>';
			}
			
			if (count($swLanguages)>1 && $wiki->status != 'protected')
			{
				if ($action == 'editmulti' )
					$swEditMenus['editmulti'] = swSystemMessage('edit',$lang).' Multi';
				else
				{
					if ($user->hasright('modify', $wiki->namewithoutlanguage()))
					$swEditMenus['editmulti'] = '<a href="'.$wiki->link('editmulti','--').'&lang='.$lang.'" rel="nofollow">'.swSystemMessage('edit',$lang).' Multi</a>';
				}
			}
		}
	
		
		if ($name && $wiki->status != ''  ) // || $action == 'editmulti' || $action == 'modifymulti'
		{
			
			if (count($swLanguages)>1)
			foreach ($swLanguages as $v)
			{
				$linkwiki = new swWiki;
				
				$linkwiki->name = $wiki->localname($v).'/'.$v;
				//$linkwiki->lookupName();
				//if ($linkwiki->revision>0)
					$swEditMenus['edit'.$v] = '<a href="'.$wiki->link('edit',$v).'&lang='.$lang.'" rel="nofollow">'.swSystemMessage('edit',$lang).' '.$v.'</a>';
			}
			unset($v);
		}
		
	}
	else
	{
		foreach ($swLanguages as $v)
		{
			if($user->hasright('modify', $wiki->name.'/'.$v))
				$swEditMenus['edit'.$v] = '<a href="'.$wiki->link('edit',$v).'&lang='.$lang.'" rel="nofollow">'.swSystemMessage('edit',$lang).' '.$v.'</a>';
		}
	
	}

	if ($wiki->revision > 0 && $user->hasright('fields', $wiki->namewithoutlanguage()) && $wiki->status != 'deleted')
		$swEditMenus['fields'] = '<a href="'.$wiki->link('fields').'&lang='.$lang.'" rel="nofollow">'.swSystemMessage('fields',$lang).'</a>';
	
	// history
	
	if ($user->hasright('modify', $wiki->namewithoutlanguage()) || 
	$user->hasright('protect', $wiki->namewithoutlanguage()) || $user->hasright('delete', $wiki->namewithoutlanguage()) )
	{
		if ($wiki->status=='deleted' || $wiki->status=='delete') 
		{
			if ($user->hasright('delete', $wiki->name))
				$swEditMenus['history'] = '<a href="'.$wiki->link('history').'&lang='.$lang.'" rel="nofollow">'.swSystemMessage('history',$lang).'</a>';
		}
		elseif($wiki->status != '')
		{
			$swEditMenus['history'] = '<a href="'.$wiki->link('history').'&lang='.$lang.'" rel="nofollow">'.swSystemMessage('history',$lang).'</a>';
		}
		$swEditMenus['whatlinkshere'] = '<a href="'.$wiki->link('whatlinkshere').'&lang='.$lang.'" rel="nofollow">'.swSystemMessage('what-links-here',$lang).'</a>';
		
		
	}
	
	
}

// global edit menus
if ($user->hasright('create', '*'))
{
	if ($action != 'special' && $action != 'modify' && $wiki->status == '')
		$swEditMenus['new'] = '<a href="'.$wiki->link('edit').'&lang='.$lang.'" rel="nofollow">'.swSystemMessage('new',$lang).'</a>';
	else
		$swEditMenus['new'] = '<a href="index.php?action=new&lang='.$lang.'" rel="nofollow">'.swSystemMessage('new',$lang).'</a>';
}


if ($user->hasright('special','special') && $action != 'logout')
{
	$linkwiki = new swWiki;
	$linkwiki->name = 'special:special-pages'; 
	$swEditMenus['special'] = '<a href="'.$linkwiki->link('view','').'&lang='.$lang.'" rel="nofollow">'.swSystemMessage('special',$lang).'</a>';
}

if ($user->hasright('upload','') && $action != 'logout')
{
	$linkwiki = new swWiki;
	$linkwiki->name = 'special:upload'; 
	$swEditMenus['upload'] = '<a href="'.$linkwiki->link('view','').'&lang='.$lang.'" rel="nofollow">'.swSystemMessage('upload',$lang).'</a>';
}


if ($swIndexError && 100*$db->indexedbitmap->countbits()/$db->GetLastRevisionFolderItem() < 99)
	$action = 'indexerror';

echotime('action '.$action);
switch ($action)
{
	case 'special':  	if ($user->hasright('special',str_replace('special:','',$name)) || $user->hasright('special','special') ||
							($user->hasright('upload','') && $name=='special:upload' ) )
						{
							$specialname = substr($name,8);
							$swParseSpecial = false;
							$specialfound = false;
								foreach ($swSpecials as $k=>$v)
								{
									if (swNameURL($k) == $specialname)
									{
										include 'inc/special/'.$swSpecials[$k];
										$specialfound = true;
									}
								}
								unset($k);
								unset($v);
								if (!$specialfound)
									$swError = swSystemMessage('this-page-does-not-exist-error',$lang); 
							
							if ($swParseSpecial)
							{
								$wiki->content = $swParsedContent;
								$swParsedContent = $wiki->parse();
							}
						}
						else
						{
							$swError = swSystemMessage('no-access-error',$lang);
						}
						
						if ($user->hasright('create', $wiki->name))
							$swEditMenus[] = '<a href="index.php?action=new">'.swSystemMessage('new',$lang).'</a>';


						break;
	case 'login':    include 'inc/special/login.php';
				 	 break;
	case 'newuser':    include 'inc/special/newuser.php';
				 	 break;
	case 'newusersubmit':    include 'inc/special/newuser.php';
				 	 break;

	case 'lostpassword':    include 'inc/special/lostpassword.php';
				 	 break;
	case 'lostpasswordsubmit':    include 'inc/special/lostpassword.php';
				 	 break;
	case 'resetpassword':    include 'inc/special/resetpassword.php';
				 	 break;

	case 'logout':   $swParsedName = 'Logout';
					 $swParsedContent = swSystemMessage('you-have-logged-out',$lang);
					 break;
	
	case 'upload':   if ($user->hasright('upload', ''))
					 	include 'inc/special/upload.php';
				 	 break;

	case 'uploadfile':   if ($user->hasright('upload', ''))
					 	include 'inc/special/uploadfile.php';
				 	 break;
	case 'snapshot':   if ($user->hasright('upload', ''))
					 	include 'inc/special/snapshot.php';
				 	 break;
	
	case 'install':  include_once 'inc/special/install.php';
				 	 break;
	
	case 'new':      $wiki=new swWiki; $name = '';// no break!
	case 'edit':    
	case 'editmulti': if ($user->hasright('modify', $wiki->name))
					 	 include 'inc/special/edit.php';
					  else
					  	include 'inc/special/view.php';
				    break;
	case 'fields': if ($user->hasright('fields', $wiki->name))
					 	 include 'inc/special/fields.php';
					  else
					  	include 'inc/special/view.php';
				    break;
						
	case 'history':	 include 'inc/special/history.php';
				     break;
	case 'whatlinkshere' : include 'inc/special/whatlinkshere.php';
							break;
	case 'diff':	include 'inc/special/diff.php';
				     break;

	case 'modify':	
	case 'modifymulti':	
					$wiki->name = $name;
					if (trim($name)=='') $swError = swSystemMessage('empty-name',$lang);
					if (!swValidate($name2,"\"\\<>[]{}*")) $swError = swSystemMessage('invalid-characters',$lang).' (name2)';
					if (!swValidate($name,"\"\\<>[]{}*")) $swError = swSystemMessage('invalid-characters',$lang).' (name)';
					if ($wiki->status == '' && ! $user->hasright('create', $wiki->name))
					{
						$swError = swSystemMessage('no-access-error',$lang);
					}
					elseif ($user->hasright('modify', $wiki->name))
					{
							// validate globals
							
							if (!swGetArrayValue($_POST,'submitmodify',false)&&!swGetArrayValue($_POST,'submitmodifymulti',false))
							{
								$swError = swSystemMessage('not-modify-without-post',$lang);
							}
							
							
							// check for editing conflict
							if ($revision > 0 || true)
							{
								$w2 = new swWiki();
								$w2->name = $name;
								$w2->lookup();
								$r = $w2->revision;
								if ($r<>0 && $r > $revision)
								{
									if ($revision > 0)
										$swError = swSystemMessage('editing-conflict',$lang).' name: '.$name.' current: '.$r.' revision: '.$revision;
									else
										$swError = swSystemMessage('editing-new-conflict',$lang).' name: '.$name.' current: '.$r.' revision: '.$revision;
									// set the old current content to the wiki
									$currentwiki =  new swWiki;
									$currentwiki->revision = $r;
									$currentwiki->lookup();
									
									
									$conflictwiki = new swWiki;
									$conflictwiki->content = $content;
									
									$revision0 = $revision;
									$revision = $r;
									
									
									include_once 'inc/special/editconflict.php';
								}
								else
								{
								 	$swError = ''; 
								}
							}
							
							
							if (!$swError)
							{
								$wiki->user = $user->name;
								$wiki->content = str_replace("\\",'',$content);
								$wiki->comment = str_replace("\\",'',$comment);
								$wiki->insert();

								
								
								echotime('reset wiki');
								// do like view now
								$wiki->content = '';
								$wiki->revision = 0;
								$wiki->persistance = '';
								$swParsedName = 'Saved: '.$wiki->name;
								$wiki->lookupLocalName(); // 1.7.1 reactivated, to render local, don't know why it was deactivated
								$wiki->lookup();
								$wiki->parsers = $swParsers;
								$swParsedContent = $wiki->parse();
							}
							else
							{
								$swParsedContent = $swError;
							}
					}
					else
					{
						$swError = swSystemMessage('no-access-error',$lang);
					}
				    break;
	case 'rename':
					if ($user->hasright('rename', $wiki->name))
					{
					 		$swEditMenus[] = '<a href="'.$wiki->link('').'">'.swSystemMessage('view',$lang).'</a>';
							$swEditMenus[] = '<a href="'.$wiki->link('edit').'">'.swSystemMessage('edit',$lang).'</a>';
							$swEditMenus[] = '<a href="'.$wiki->link('history').'">'.swSystemMessage('history',$lang).'</a>';
							
							$wiki->user = $user->name;
							$name2 = str_replace("\\",'',$name2);
							
							if ($name2 != $wiki->name)
							{
								$wiki2 = new swWiki;
								$wiki2->name = $wiki->name;
								$wiki2->user = $user->name;
								$wiki2->content = '#REDIRECT [['.$name2.']]';
								$wiki2->insert();
								$wiki->name = $name2;
							}
							
							$wiki->insert();
							
							$subpages = '';
							if (isset($_REQUEST['renamesubpages']) && $_REQUEST['renamesubpages'] )  // do it again for all subpages
							{
								foreach($swLanguages as $ln)
								{
									
									$wiki2 = new swWiki;
									$wiki2->name = $name.'/'.$ln;
									$wiki2->lookup();
									if ($wiki2->revision > 0)
									{
										$wiki3 = new swWiki;
										$wiki3->name = $name2.'/'.$ln;
										$wiki3->content = $wiki2->content;
										$wiki3->user = $user->name;
										$wiki3->insert();
										$wiki2->content = '#REDIRECT [['.$name2.'/'.$ln.']]';
										$wiki2->insert();
										$subpages.=' /'.$ln;
									}
								}
							
							
							}

							$swParsedName = 'Renamed: '.$name2.$subpages;
							$swParsedContent = $wiki->parse();

					}
					else
					{
						$swError = swSystemMessage('no-access-error',$lang);
					}
				    break;

	
	
	case 'delete': 	
					if ($user->hasright('delete', $wiki->name))
					{
						if (!swGetArrayValue($_POST,'submitdelete',false) )
						{
								$swError = swSystemMessage('not-delete-without-post',$lang);
						}
						else
						{
							$wiki->user = $user->name;
							$wiki->delete();
							$swParsedName = 'Deleted: '.$name;
							$swEditMenus[] = '<a href="'.$wiki->link('edit').'">'.swSystemMessage('edit',$lang).'</a>';
						}
					}
					else
					{
						$swError = swSystemMessage('no-access-error',$lang);
					 }
					 break;						

	case 'protect': 	if ($user->hasright('protect', $wiki->name))
						{
							$wiki->user = $user->name;
							$wiki->protect();
							$swParsedName = 'Protected: '.$name;
							$swParsedContent = $wiki->parse();
							$swEditMenus[] = '<a href="'.$wiki->link('').'">'.swSystemMessage('view',$lang).'</a>';
							$swEditMenus[] = '<a href="'.$wiki->link('edit').'">'.swSystemMessage('edit',$lang).'</a>';
							$swEditMenus[] = '<a href="'.$wiki->link('history').'">'.swSystemMessage('history',$lang).'</a>';
						}
						else
						{
							$swError = swSystemMessage('no-access-error',$lang);
					    }
					 break;						

	case 'unprotect': 	if ($user->hasright('protect', $wiki->name))
						{
							$wiki->user = $user->name;
							$wiki->unprotect();
							$swParsedName = 'Unprotected: '.$name;
							$swParsedContent = $wiki->parse();
							$swEditMenus[] = '<a href="'.$wiki->link('').'">'.swSystemMessage('view',$lang).'</a>';
							$swEditMenus[] = '<a href="'.$wiki->link('edit').'">'.swSystemMessage('edit',$lang).'</a>';
							$swEditMenus[] = '<a href="'.$wiki->link('history').'">'.swSystemMessage('history',$lang).'</a>';
						}
						else
						{
							$swError = swSystemMessage('no-access-error',$lang);
					    }
					 break;						

	
	case 'revert':		if ($user->hasright('modify', $wiki->name))
						{
							$swEditMenus[] = swSystemMessage('view',$lang);
							$swEditMenus[] = '<a href="'.$wiki->link('edit').'">'.swSystemMessage('edit',$lang).'</a>';
							$swEditMenus[] = '<a href="'.$wiki->link('history').'">'.swSystemMessage('history',$lang).'</a>';
							
							$wiki->user = $user->nameshort();
							$wiki->insert();
							
							$swParsedName = 'Reverted: '.$wiki->name;
							$swParsedContent = $wiki->parse();

						}
						else
						{
							$swError = swSystemMessage('no-access-error',$lang);
						}
						break;
	
	
	case 'search':		include 'inc/special/search.php';
						break;
	
	
	case 'indexerror':		$swParsedName = 'Site indexing';
						$swParsedContent = 'The site is reindexing and currently not available. Please come back in ten minutes. ('
						.sprintf('%0d',100*$db->indexedbitmap->countbits()/$db->GetLastRevisionFolderItem()).'%)';
						$swFooter = '';
						$swEditMenus = array();
						$swOvertime = false;
						break;
	default: 			
						if (isset($swActionHookFile))
							include $swActionHookFile;
						else	
							include 'inc/special/view.php';

}

if ($swRedirectedFrom)
	$swParsedContent .= '<p><i>Redirected from '.$swRedirectedFrom.'</i></p>';
	

if (count($swEditMenus) == 1) $swEditMenus = array();
// add menus by user rights
$menudomains = swGetValue($user->content,'_menu',true);
{
	foreach($menudomains as $v)
	{
		$swEditMenus['-'.$v] = '<a href="index.php?name='.$v.'" rel="nofollow">'.$v.'</a>';
	}
}

echotime("parsed");

// do cron job

if ($action != 'indexerror' && rand(0,100)<2)
	swCron();

$db->close(); 
session_write_close();


// apply page skin
if (!array_key_exists(@$skin,$swSkins)) $skin = 'default';
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


swLog($username,$name,$action,$query,$lang,$referer,$usedtime,$swError,'','','');
swSemaphoreRelease();


?>