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

//setcookie('aaa', '55', time() + 9000000 , "/"); 

$ip = swGetArrayValue($_SERVER,'REMOTE_ADDR');
$referer = swGetArrayValue($_SERVER,'HTTP_REFERER');

// use only server referer
$referer = preg_replace("$\w*://([^/]*)/(.*)$","$1",$referer);
	
// editing
$id = swGetArrayValue($_REQUEST,'id');	   //obsolete??
$revision = swGetArrayValue($_REQUEST,'revision',0);	
$content = swGetArrayValue($_REQUEST,'content',0);	

// submits overrides action
if (swGetArrayValue($_REQUEST,'submitmodify',false))
	$action = 'modify';
if (swGetArrayValue($_REQUEST,'submitmodifymulti',false))	
	$action = 'modifymulti';
if (swGetArrayValue($_REQUEST,'submitpreview',false))	
	$action = 'preview';
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
}
if(isset($_SESSION[$swMainName.'-username'])&& $_SESSION[$swMainName.'-username'] != '')
{	$knownuser = true;  $username = $_SESSION[$swMainName.'-username']; }
if($username && swGetCookie('passwordtoken') == md5(swNameURL($username).date('Ymd',time()).$swEncryptionSalt))
{	$knownuser = true; }
if($username && swGetCookie('passwordtoken') == md5(swNameURL($username).date('Ymd',time()-24*60*60).$swEncryptionSalt))
{	$knownuser = true; } 

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
			error_reporting(0);
			ini_set("display_errors", 0); 

		}
		if ($action=='login') $action='view'; // do not stay in login 
		
}
else
{
	if (array_key_exists('username', $_POST)) 
		$username = $_POST['username'];
	if (array_key_exists('username', $_GET)) 
		$username = $_GET['username'];
		
	if (array_key_exists('pass', $_POST)) 
		$pass = $_REQUEST['pass'];
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
			$swError = swSystemMessage('WrongPasswordError',$lang);
			$action = "login";
			swLogWrongPassword($_SERVER['REMOTE_ADDR']);
		}
		$user = new swUser;
		$user->name = '';
		$user->pass = '';
		$user->content = $swAllUserRights;
    }
}
if ($action == 'logout')
{
	$user = new swUser;
	$user->name = '';
	$user->pass = '';
	$user->content = $swAllUserRights;
}

if ($swUseSemaphore)
session_write_close();

// add searchnamespaces by user rights
$viewdomains = swGetValue($user->content,'_view',true);
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

$transcludedomains = swGetValue($user->content,'_transclude',true);
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
			$swError = swSystemMessage('NoAccessError',$lang);
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
					
					
					if ($action !='preview')  
						$swError =$action.' '.swSystemMessage('ThisPageDoesNotExistError',$lang).' '.$wiki->name; 
					
				}
				break;		
			case 'Deleted record': $swError = swSystemMessage('ThisPageHasBeenDeletedError',$lang); break;
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
$swHomeMenu = '<a href="index.php">'.swSystemMessage('Home',$lang).'</a>';

$swLangMenus = array();
foreach ($swLanguages as $v)
{
		$swLangMenus[$v] = '<a href="'.$wiki->link('view','--').'&amp;lang='.$v.'">'.swSystemMessage($v,$lang).'</a>';
}
unset($v);
$swSearchMenu = ' <form method="get" action="index.php"><p>
 <input type="hidden" name="action" value="search" />
 <input type="text" name="query" value="'.$query.'" style="width:100%"/>
 <input type="submit" name="submit" value="'.swSystemMessage('Search',$lang).'" /> 
 </p></form> ';

$swLoginMenus= array();
if ($user->username != "")
{
		$swLoginMenus['user'] = $user->nameshort();
		$swLoginMenus['logout'] = "<a href='index.php?action=logout' rel='nofollow'>".swSystemMessage('Logout',$lang).'</a>';
}
elseif ($action != 'login')
{
	$swLoginMenus['login'] = '<a href="index.php?action=login&amp;name='.$name.'" rel="nofollow">'.swSystemMessage('Login',$lang).'</a>';
}		

$swEditMenus = array();


// page based edit menus
if ($action != 'special' && $action != 'login' && $action != 'logout' && $action!='search')
{
	// view
	if ($user->hasright('view', $wiki->name))
	{
		$swEditMenus['view'] = '<a href="'.$wiki->articlelink('editview').'" rel="nofollow">'.swSystemMessage('View',$lang).'</a>';
    }
		
	// edit
	if ($user->hasright('modify', $wiki->name) || 
	$user->hasright('protect', $wiki->name) || $user->hasright('delete', $wiki->name) || $action == 'modifymulti' )
	{
	
		if (($wiki->status != '' && $wiki->status != 'deleted' && $wiki->status != 'delete') || $action == 'modifymulti' || $action == 'modify' ) // page does exist // delete is obsolete
		{
			if ($user->hasright('modify', $wiki->namewithoutlanguage()))
				$swEditMenus['edit'] = '<a href="'.$wiki->link('edit','--').'" rel="nofollow">'.swSystemMessage('Edit',$lang).'</a>';
						
			if ($action == 'rename')
			{
				$w2 = new swWiki();
				$w2->name = $name2;
				$swEditMenus['edit2'] = '<a href="'.$w2->link('edit','').'" rel="nofollow">'.swSystemMessage('Edit',$lang).' '.$name2.'</a>';
			}
			
			if (count($swLanguages)>1 && $wiki->status != 'protected')
			{
				if ($action == 'editmulti' )
					$swEditMenus['editmulti'] = swSystemMessage('Edit',$lang).' Multi';
				else
				{
					if ($user->hasright('modify', $wiki->namewithoutlanguage()))
					$swEditMenus['editmulti'] = '<a href="'.$wiki->link('editmulti','--').'" rel="nofollow">'.swSystemMessage('Edit',$lang).' Multi</a>';
				}
			}
		}
	
		
		if ($name && $wiki->status != ''  ) // || $action == 'editmulti' || $action == 'modifymulti'
		{
			
			foreach ($swLanguages as $v)
			{
				$linkwiki = new swWiki;
				
				$linkwiki->name = $wiki->localname($v).'/'.$v;
				//$linkwiki->lookupName();
				//if ($linkwiki->revision>0)
					$swEditMenus['edit'.$v] = '<a href="'.$wiki->link('edit',$v).'" rel="nofollow">'.swSystemMessage('Edit',$lang).' '.$v.'</a>';
			}
			unset($v);
		}
		
	}
	else
	{
		foreach ($swLanguages as $v)
		{
			if($user->hasright('modify', $wiki->name.'/'.$v))
				$swEditMenus['edit'.$v] = '<a href="'.$wiki->link('edit',$v).'" rel="nofollow">'.swSystemMessage('Edit',$lang).' '.$v.'</a>';
		}
	
	}

	if ($wiki->revision > 0 && $user->hasright('fields', $wiki->namewithoutlanguage()) && $wiki->status != 'deleted')
		$swEditMenus['fields'] = '<a href="'.$wiki->link('fields').'" rel="nofollow">'.swSystemMessage('Fields',$lang).'</a>';
	
	// history
	
	if ($user->hasright('modify', $wiki->namewithoutlanguage()) || 
	$user->hasright('protect', $wiki->namewithoutlanguage()) || $user->hasright('delete', $wiki->namewithoutlanguage()) )
	{
		if ($wiki->status=='deleted' || $wiki->status=='delete') 
		{
			if ($user->hasright('delete', $wiki->name))
				$swEditMenus['history'] = '<a href="'.$wiki->link('history').'" rel="nofollow">'.swSystemMessage('History',$lang).'</a>';
		}
		elseif($wiki->status != '')
		{
			$swEditMenus['history'] = '<a href="'.$wiki->link('history').'" rel="nofollow">'.swSystemMessage('History',$lang).'</a>';
		}
		
	}
	
	
}

// global edit menus
if ($user->hasright('create', '*'))
{
	if ($action != 'special' && $action != 'modify' && $wiki->status == '')
		$swEditMenus['new'] = '<a href="'.$wiki->link('edit').'" rel="nofollow">'.swSystemMessage('New',$lang).'</a>';
	else
		$swEditMenus['new'] = '<a href="index.php?action=new" rel="nofollow">'.swSystemMessage('New',$lang).'</a>';
}


if ($user->hasright('special','special') && $action != 'logout')
	$swEditMenus['special'] = '<a href="index.php?name=special:special-pages" rel="nofollow">'.swSystemMessage('Special',$lang).'</a>';

if ($user->hasright('upload','') && $action != 'logout')
	$swEditMenus['upload'] = '<a href="index.php?name=special:upload" rel="nofollow">'.swSystemMessage('Upload',$lang).'</a>';		


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
									$swError = swSystemMessage('ThisPageDoesNotExistError',$lang); 
							
							if ($swParseSpecial)
							{
								$wiki->content = $swParsedContent;
								$swParsedContent = $wiki->parse();
							}
						}
						else
						{
							$swError = swSystemMessage('NoAccessError',$lang);
						}
						
						if ($user->hasright('create', $wiki->name))
							$swEditMenus[] = '<a href="index.php?action=new">'.swSystemMessage('New',$lang).'</a>';


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

	case 'logout':   $swParsedName = 'Logout';
					 $swParsedContent = swSystemMessage('You have logged out',$lang);
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
	
	case 'install': include_once 'inc/special/install.php';
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
	case 'diff':	include 'inc/special/diff.php';
				     break;

	case 'preview':  if (!swValidate($name2,"\"\\<>[]{}*")) $swError = swSystemMessage('InvalidCharacters',$lang).' (name2)';
					 if (!swValidate($name,"\"\\<>[]{}*")) $swError = swSystemMessage('InvalidCharacters',$lang).' (name)';
					 $wiki->content = str_replace("\\",'',$content);
					 $wiki->comment = str_replace("\\",'',$comment);
					 include_once 'inc/special/edit.php';
					 break;
	case 'modify':	
	case 'modifymulti':	
					$wiki->name = $name;
					if (trim($name)=='') $swError = swSystemMessage('EmptyName',$lang);
					if (!swValidate($name2,"\"\\<>[]{}*")) $swError = swSystemMessage('InvalidCharacters',$lang).' (name2)';
					if (!swValidate($name,"\"\\<>[]{}*")) $swError = swSystemMessage('InvalidCharacters',$lang).' (name)';
					if ($wiki->status == '' && ! $user->hasright('create', $wiki->name))
					{
						$swError = swSystemMessage('NoAccessError',$lang);
					}
					elseif ($user->hasright('modify', $wiki->name))
					{
							// validate globals
							
							if (!swGetArrayValue($_POST,'submitmodify',false)&&!swGetArrayValue($_POST,'submitmodifymulti',false))
							{
								$swError = swSystemMessage('NotModifyWithoutPost',$lang);
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
										$swError = swSystemMessage('EditingConflict',$lang).' name: '.$name.' current: '.$r.' revision: '.$revision;
									else
										$swError = swSystemMessage('EditingNewConflict',$lang).' name: '.$name.' current: '.$r.' revision: '.$revision;
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
						$swError = swSystemMessage('NoAccessError',$lang);
					}
				    break;
	case 'rename':
					if ($user->hasright('rename', $wiki->name))
					{
					 		$swEditMenus[] = '<a href="'.$wiki->link('').'">'.swSystemMessage('View',$lang).'</a>';
							$swEditMenus[] = '<a href="'.$wiki->link('edit').'">'.swSystemMessage('Edit',$lang).'</a>';
							$swEditMenus[] = '<a href="'.$wiki->link('history').'">'.swSystemMessage('History',$lang).'</a>';
							
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
						$swError = swSystemMessage('NoAccessError',$lang);
					}
				    break;

	
	
	case 'delete': 	
					if ($user->hasright('delete', $wiki->name))
					{
						if (!swGetArrayValue($_POST,'submitdelete',false) )
						{
								$swError = swSystemMessage('NotDeleteWithoutPost',$lang);
						}
						else
						{
							$wiki->user = $user->name;
							$wiki->delete();
							$swParsedName = 'Deleted: '.$name;
							$swEditMenus[] = '<a href="'.$wiki->link('edit').'">'.swSystemMessage('Edit',$lang).'</a>';
						}
					}
					else
					{
						$swError = swSystemMessage('NoAccessError',$lang);
					 }
					 break;						

	case 'protect': 	if ($user->hasright('protect', $wiki->name))
						{
							$wiki->user = $user->name;
							$wiki->protect();
							$swParsedName = 'Protected: '.$name;
							$swParsedContent = $wiki->parse();
							$swEditMenus[] = '<a href="'.$wiki->link('').'">'.swSystemMessage('View',$lang).'</a>';
							$swEditMenus[] = '<a href="'.$wiki->link('edit').'">'.swSystemMessage('Edit',$lang).'</a>';
							$swEditMenus[] = '<a href="'.$wiki->link('history').'">'.swSystemMessage('History',$lang).'</a>';
						}
						else
						{
							$swError = swSystemMessage('NoAccessError',$lang);
					    }
					 break;						

	case 'unprotect': 	if ($user->hasright('protect', $wiki->name))
						{
							$wiki->user = $user->name;
							$wiki->unprotect();
							$swParsedName = 'Unprotected: '.$name;
							$swParsedContent = $wiki->parse();
							$swEditMenus[] = '<a href="'.$wiki->link('').'">'.swSystemMessage('View',$lang).'</a>';
							$swEditMenus[] = '<a href="'.$wiki->link('edit').'">'.swSystemMessage('Edit',$lang).'</a>';
							$swEditMenus[] = '<a href="'.$wiki->link('history').'">'.swSystemMessage('History',$lang).'</a>';
						}
						else
						{
							$swError = swSystemMessage('NoAccessError',$lang);
					    }
					 break;						

	
	case 'revert':		if ($user->hasright('modify', $wiki->name))
						{
							$swEditMenus[] = swSystemMessage('View',$lang);
							$swEditMenus[] = '<a href="'.$wiki->link('edit').'">'.swSystemMessage('Edit',$lang).'</a>';
							$swEditMenus[] = '<a href="'.$wiki->link('history').'">'.swSystemMessage('History',$lang).'</a>';
							
							$wiki->user = $user->nameshort();
							$wiki->insert();
							
							$swParsedName = 'Reverted: '.$wiki->name;
							$swParsedContent = $wiki->parse();

						}
						else
						{
							$swError = swSystemMessage('NoAccessError',$lang);
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
swLog($username,$name,$action,$query,$lang,$referer,$usedtime,$swError,'','','');
swSemaphoreRelease();


?>