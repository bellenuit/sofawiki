 <?php

if (!defined('SOFAWIKI')) die('invalid acces');

$swParsedName = 'Special:Tickets';


$title = (array_key_exists('title', $_REQUEST) ? $_REQUEST['title'] : '' );

$id = (array_key_exists('id', $_REQUEST) ? $_REQUEST['id'] : '' );
$assigned = (array_key_exists('assigned', $_REQUEST) ? $_REQUEST['assigned'] : '' );
$priority = (array_key_exists('priority', $_REQUEST) ? $_REQUEST['priority'] : '' );
$status = (array_key_exists('status', $_REQUEST) ? $_REQUEST['status'] : '' );
$text = (array_key_exists('text', $_REQUEST) ? $_REQUEST['text'] : '' );
$ticketaction = (array_key_exists('ticketaction', $_REQUEST) ? $_REQUEST['ticketaction'] : '' );
 

$swParsedContent = '<nowiki><a href="index.php?name=special:tickets&ticketaction=new">New Ticket</a> <a href="index.php?name=special:tickets&status=open">Open Tickets</a> <a href="index.php?name=special:tickets&assigned='.$username.'">My Tickets</a> <a href="index.php?name=special:tickets&status=resolved">Resolved tickets</a> <a href="index.php?name=special:tickets&status=closed">Closed tickets</a>	<a href="index.php?name=special:tickets&ticketaction=activity">Activity</a> <a href="index.php?name=special:tickets&ticketaction=help">Help</a></nowiki>';

$priorities = array('1 high', '2 normal', '3 low');

$table = swRelationToTable('filter _namespace "user", _name, _special "special"
project _name'); 

// print_r($table);

foreach($table as $t) { $ticketusers[]=substr($t['_name'],strlen('user:')); }
foreach($powerusers as $p) $ticketusers[]=$p->username;

$mytickets = true;

function swHtmlSelect($name, $list, $v, $default)
{
	if (!$v) $v = $default;
	$s = '<select name="'.$name.'">';
	foreach ($list as $p)
		$s .= ( $v == $p ? ' <option value="'.$p.'" selected>'.$p.'</option> ' : 
' <option value="'.$p.'">'.$p.'</option> ' );
	$s .= '</select>';
	return $s;	
}


if (isset($_POST['submitopen']))
{

	//find next id
	$q = 'filter _namespace "ticket", id
project id max';
	$test = swRelationToTable($q);
	
	$id = @$test[0]['id_max'];
	
	$id = (int)@$id+1;
	
	//security: do not overwrite existing ticket
	$found = true;
	
	while ($found)
	{
		$w = new swWiki;
		$w->name = 'Ticket:'.$id;
		$w->lookup();
		if (!$w->revision) $found = false;
		$id++;
	}

	
	if (isset($_FILES['uploadedfile']) && $_FILES['uploadedfile'] && $_FILES['uploadedfile']['size'])
	{		
		$file = $_FILES['uploadedfile'];
		$filename = 'ticket-'.$id.'-'.$file['name'];
		$deleteexisting = false;

		$imagefile =  swHandleUploadFile($file, $filename, '', $deleteexisting);
		
		if (substr($imagefile,-4)=='.jpg' || substr($imagefile,-5)=='.jpeg' || substr($imagefile,-4)=='.png' )
		{
			$text .= "\n".'<nowiki><img src="site/files/'.$imagefile.'" width=100%/></nowiki>';
		}
		else
		{
			$text .= "\n".'[[Media:'.$imagefile.']]';
		}
	}
	
	
	
	
	$activity = $username.' opened ticket #'.$id.' ('.$title.') and assigned to '.$assigned.' with priority '.substr($priority,2).'.';

	$w = new swWiki;
	$w->name = 'Ticket:'.$id;
	$w->content = $username.': '.$text.PHP_EOL.
	'[[id::'.$id.']]'.PHP_EOL.
	'[[title::'.$title.']]'.PHP_EOL.
	'[[assigned::'.$assigned.']]'.PHP_EOL.
	'[[priority::'.$priority.']]'.PHP_EOL.
	'[[creator::'.$username.']]'.PHP_EOL.
	'[[user::'.$username.']]'.PHP_EOL.
	'[[status::open]]'.PHP_EOL.
	'[[activity::'.date('Y-m-d H:i',time()).' '.$activity.']]';
	$w->insert();

	$link = '<nowiki><a href="index.php?name=special:tickets&id='.$id.'">ticket #'.$id.'</a></nowiki>';
	$swParsedContent .= PHP_EOL.PHP_EOL.str_replace('ticket #'.$id,$link,$activity);
	$assigned = '';
	$mytickets = false;
	$status = '';
	//$id = '';
}

if (isset($_POST['submitcomment']))
{
	
	$w = new swWiki;
	$w->name = 'Ticket:'.$id;
	$w->lookup();
	$fields = $w->internalfields;
	$oldtext = $w->content;
	$oldtext = preg_replace('/\[\[(.*)::(.*)\]\]/', '', $oldtext);
	
	
	
	if (isset($_FILES['uploadedfile']) && $_FILES['uploadedfile'] && $_FILES['uploadedfile']['size'])
	{
		
		$file = $_FILES['uploadedfile'];
		$filename = 'ticket-'.$id.'-'.$file['name'];
		$deleteexisting = false;

		$imagefile =  swHandleUploadFile($file, $filename, '', $deleteexisting);
		
		if (substr($imagefile,-4)=='.jpg' || substr($imagefile,-5)=='.jpeg' || substr($imagefile,-4)=='.png' )
		{
			$text .= "\n".'<nowiki><img src="site/files/'.$imagefile.'" width=100%/></nowiki>';
		}
		else
		{
			$text .= "\n".'[[Media:'.$imagefile.']]';
		}
	}

	
	$activity = '';
	if ($text != '')
	{
		$activity = $username.' commented ticket #'.$id;
		$oldtext = trim($oldtext)."\n\n----\n\n".$username.': '.$text;
	}
	
	if ($assigned != @$fields['assigned'][0])
	{
		if (!$activity)
			$activity = $username.' assigned ticket #'.$id.' to '.$assigned;
		else
			$activity .= ' and assigned it to '.$assigned;
	}
	
	if ($priority != @$fields['priority'][0])
	{
		if (!$activity)
			$activity = $username.' changed priority of ticket #'.$id.' to '.$priority;
		else
			$activity .= ' with priority '.$priority;
	}
	
	if (!$activity)
		$activity = $username.' added an empty comment';
	
	$activity .= '.';
		
	$w->content = trim($oldtext).PHP_EOL.
	'[[id::'.$id.']]'.PHP_EOL.
	'[[title::'.@$fields['title'][0].']]'.PHP_EOL.
	'[[assigned::'.$assigned.']]'.PHP_EOL.
	'[[priority::'.$priority.']]'.PHP_EOL.
	'[[creator::'.@$fields['creator'][0].']]'.PHP_EOL.
	'[[user::'.$username.']]'.PHP_EOL.
	'[[status::open]]'.PHP_EOL.
	'[[activity::'.join('::',@$fields['activity']).'::'.date('Y-m-d H:i',time()).' '.$activity.']]';
	$w->insert();
	
	
	$link = '<nowiki><a href="index.php?name=special:tickets&id='.$id.'">ticket #'.$id.'</a></nowiki>';
	$swParsedContent .= PHP_EOL.PHP_EOL.str_replace('ticket #'.$id,$link,$activity);
	$mytickets = false;
	$assigned = '';
}

if (isset($_POST['submitresolve']))
{
	
	$w = new swWiki;
	$w->name = 'Ticket:'.$id;
	$w->lookup();
	$fields = $w->internalfields;
	$oldtext = $w->content;
	$oldtext = preg_replace('/\[\[(.*)::(.*)\]\]/', '', $oldtext);
	
	
	
	if (isset($_FILES['uploadedfile']) && $_FILES['uploadedfile'] && $_FILES['uploadedfile']['size'])
	{
		
		$file = $_FILES['uploadedfile'];
		$filename = 'ticket-'.$id.'-'.$file['name'];
		$deleteexisting = false;

		$imagefile =  swHandleUploadFile($file, $filename, '', $deleteexisting);
		
		if (substr($imagefile,-4)=='.jpg' || substr($imagefile,-5)=='.jpeg' || substr($imagefile,-4)=='.png' )
		{
			$text .= "\n".'<nowiki><img src="site/files/'.$imagefile.'" width=100%/></nowiki>';
		}
		else
		{
			$text .= "\n".'[[Media:'.$imagefile.']]';
		}
	}

	
	$activity = '';
	if ($text != '')
	{
		$activity = $username.' commented ticket #'.$id;
		$oldtext = trim($oldtext)."\n\n----\n\n".$username.': '.$text;
	}
	
	if ($assigned != @$fields['assigned'][0])
	{
		if (!$activity)
			$activity = $username.' assigned ticket #'.$id.' to '.$assigned;
		else
			$activity .= ' and assigned it to '.$assigned;
	}
	
	if ($priority != @$fields['priority'][0])
	{
		if (!$activity)
			$activity = $username.' changed priority of ticket #'.$id.' to '.$priority;
		else
			$activity .= ' with priority '.$priority;
	}
	
	if ($activity)
	{
		$activity .= ' and set status of ticket #'.$id.' to resolved';
	}
	else
	{
		$activity = $username.' set status of ticket #'.$id.' to resolved';
	}
	
	$activity .= '.';
		
	$w->content = trim($oldtext).PHP_EOL.
	'[[id::'.$id.']]'.PHP_EOL.
	'[[title::'.@$fields['title'][0].']]'.PHP_EOL.
	'[[assigned::'.$assigned.']]'.PHP_EOL.
	'[[priority::'.$priority.']]'.PHP_EOL.
	'[[creator::'.@$fields['creator'][0].']]'.PHP_EOL.
	'[[user::'.$username.']]'.PHP_EOL.
	'[[status::resolved]]'.PHP_EOL.
	'[[activity::'.join('::',@$fields['activity']).'::'.date('Y-m-d H:i',time()).' '.$activity.']]';
	$w->insert();
	
	
	$link = '<nowiki><a href="index.php?name=special:tickets&id='.$id.'">ticket #'.$id.'</a></nowiki>';
	$swParsedContent .= PHP_EOL.PHP_EOL.str_replace('ticket #'.$id,$link,$activity);
	$assigned = '';
	$id = '';
	$status = '';
	$mytickets = false;
}

if (isset($_POST['submitreopen']))
{
	
	$w = new swWiki;
	$w->name = 'Ticket:'.$id;
	$w->lookup();
	$fields = $w->internalfields;
	$oldtext = $w->content;
	$oldtext = preg_replace('/\[\[(.*)::(.*)\]\]/', '', $oldtext);
	
	$activity = $username.' reopened ticket #'.$id;
	
	$activity .= '.';
		
	$w->content = trim($oldtext).PHP_EOL.
	'[[id::'.$id.']]'.PHP_EOL.
	'[[title::'.@$fields['title'][0].']]'.PHP_EOL.
	'[[assigned::'.@$fields['assigned'][0].']]'.PHP_EOL.
	'[[priority::'.@$fields['priority'][0].']]'.PHP_EOL.
	'[[creator::'.@$fields['creator'][0].']]'.PHP_EOL.
	'[[user::'.$username.']]'.PHP_EOL.
	'[[status::open]]'.PHP_EOL.
	'[[activity::'.join('::',@$fields['activity']).'::'.date('Y-m-d H:i',time()).' '.$activity.']]';
	$w->insert();
	
	
	$link = '<nowiki><a href="index.php?name=special:tickets&id='.$id.'">ticket #'.$id.'</a></nowiki>';
	$swParsedContent .= PHP_EOL.PHP_EOL.str_replace('ticket #'.$id,$link,$activity);
	$mytickets = false;
	$assigned = '';
}

if (isset($_POST['submitclose']))
{
	
	$w = new swWiki;
	$w->name = 'Ticket:'.$id;
	$w->lookup();
	$fields = $w->internalfields;
	$oldtext = $w->content;
	$oldtext = preg_replace('/\[\[(.*)::(.*)\]\]/', '', $oldtext);
	
	$activity = $username.' closed ticket #'.$id;
	
	$activity .= '.';
		
	$w->content = trim($oldtext).PHP_EOL.
	'[[id::'.$id.']]'.PHP_EOL.
	'[[title::'.@$fields['title'][0].']]'.PHP_EOL.
	'[[assigned::'.@$fields['assigned'][0].']]'.PHP_EOL.
	'[[priority::'.@$fields['priority'][0].']]'.PHP_EOL.
	'[[creator::'.@$fields['creator'][0].']]'.PHP_EOL.
	'[[user::'.$username.']]'.PHP_EOL.
	'[[status::closed]]'.PHP_EOL.
	'[[activity::'.join('::',@$fields['activity']).'::'.date('Y-m-d H:i',time()).' '.$activity.']]';
	$w->insert();
	
	
	$link = '<nowiki><a href="index.php?name=special:tickets&id='.$id.'">ticket #'.$id.'</a></nowiki>';
	$swParsedContent .= PHP_EOL.PHP_EOL.str_replace('ticket #'.$id,$link,$activity);
	$mytickets = false;
	$assigned = '';
	$id = '';
	$status = 'open';
}



if ($ticketaction == 'new')
{

	$swParsedContent .= '<nowiki><div id="editzone" class="editzone specialtickets">
		<div class="editheader">New ticket</div>';
	$swParsedContent .= "\n".'<nowiki><form method="post" action="index.php?name=special:tickets" enctype="multipart/form-data">
<input type="submit" name="submitopen" value="Open Ticket">
<input type="hidden" name="MAX_FILE_SIZE" value="'.$swMaxFileSize.'" />
<p>Title
<input type="text" name="title" value="" autocomplete="off" />
<p>Text
<textarea name="text" rows="4" cols="80"></textarea>
<p>Assign to
'.swHtmlSelect('assigned',$ticketusers,$username,$username).'
<p>Priority
'.swHtmlSelect('priority',$priorities,'2 normal','2 normal').'
<p>Add File
<input type="file" name="uploadedfile" />
</form></div></nowiki>';	$mytickets = false;
	$assigned = '';
}
if ($ticketaction == 'activity')
{

		$lines = swRelationToTable('filter _namespace "ticket", activity, id
order activity z');

		



		$i=0;
		$swParsedContent .= "\n".'===Activity===';
		foreach($lines as $line)
		{
			if ($i > 100) break;
			$link = '<nowiki><a href="index.php?name=special:tickets&id='.@$line['id'].'">ticket #'.@$line['id'].'</a></nowiki>';
			$swParsedContent .= "\n".str_replace('ticket #'.@$line['id'],$link,@$line['activity']);
		  $i++;
		}
		$mytickets = false;
		$assigned = '';
		
}
if ($ticketaction == 'help')
{
	$swParsedContent .= "\n".'===Tickets help===';
	$swParsedContent .= "\n".'Any user having access to special:special can open, comment, resolve and close tickets.'
	."\n".'Any of these users can \'\'open tickets\'\'. Tickets have a title, text (wikitext), are assigned to a user and have a priority (1 high, 2 normal, 3 low).'
	."\n".'My Tickets are tickets \'\'assigned\'\' to the current user.'
	."\n".'Any of these users can \'\'comment\'\' a ticket, \'\'reassign\'\' it, change the \'\'priority\'\', set it to \'\'resolved\'\' and \'\'reopen\'\' a resolved ticket.'
	."\n".'Only the initial creator of the ticket can \'\'close\'\' a resolved ticket.'
	."\n".'You cannot add directly files to a ticket, but you can upload files normally and refer to them with a media link.'; 
	$mytickets = false;
	$assigned = '';
}



if ($status)
{
		$lines = swRelationToTable('filter _namespace "ticket", id, title, priority, creator, assigned, status "'.$status.'"
order priority, id 9');


		

		$i=0;
		switch($status)
		{
			case 'open': $swParsedContent .= "\n".'===Open Tickets ==='; break;
			case 'closed': $swParsedContent .= "\n".'===Closed Tickets ==='; break;
			case 'resolved': $swParsedContent .= "\n".'===Resolved Tickets ==='; break;
			default: $swParsedContent .= "\n".'===Tickets with status '.$status.'==='; break;
		}
		
		foreach($lines as $line)
		{
			if ($i > 100) break;
			$swParsedContent .= "\n".'<nowiki><a href="index.php?name=special:tickets&id='.@$line['id'].'">ticket #'.@$line['id'].'</a></nowiki> (assigned from '.@$line['creator']. ' to '.@$line['assigned'].' with priority '.substr(@$line['priority'],2).'): '.@$line['title'];
		  $i++;
		}
		$mytickets = false;
		$assigned = '';
}

if ($mytickets) $assigned = $username; // default


if ($assigned && !$id)
{
		$lines = swRelationToTable('filter _namespace "ticket", id, title, priority, status, creator, assigned "'.$assigned.'"
select status !== "closed"
select status !== "resolved"
order priority, id 9');
		$i=0;
		$swParsedContent .= "\n".'===Tickets assigned to '.$assigned.'===';
		foreach($lines as $line)
		{
			if ($i > 100) break;
			$swParsedContent .= "\n".'<nowiki><a href="index.php?name=special:tickets&id='.@$line['id'].'">ticket #'.@$line['id'].'</a></nowiki> (assigned from '.@$line['creator'].' with priority '.substr(@$line['priority'],2).'): '.@$line['title'];
		  $i++;
		}
		
		$lines = swRelationToTable('filter _namespace "ticket", id, title, priority, status, assigned, creator "'.$assigned.'"
select status !== "closed"
select status !== "resolved"
order priority, id 9');
		$i=0;
		$swParsedContent .= "\n".'===Tickets created from '.$assigned.'===';
		foreach($lines as $line)
		{
			if ($i > 100) break;
			$swParsedContent .= "\n".'<nowiki><a href="index.php?name=special:tickets&id='.@$line['id'].'">ticket #'.@$line['id'].'</a></nowiki> (assigned to '.@$line['assigned'].' with priority '.substr(@$line['priority'],2).'): '.@$line['title'];
		  $i++;
		}
}


if ($id)
{


		$w = new swWiki;
		$w->name = 'Ticket:'.$id;
		$w->lookup();
		$fields = $w->internalfields;
		// $swParsedContent .= "\n".'===Ticket #'.$id.': '.array_pop($fields['title']).'===';
		
		$swParsedContent .= "\n\n".'<nowiki><div id="editzone" class="editzone">
<div class="editheader">Ticket #'.$id.': '.array_pop($fields['title']).'</div></nowiki>';
		
		
		$text = $w->content;
		$text = preg_replace('/\[\[(.*)::(.*)\]\]/', '', $text);
		$lines = $fields['activity'];
		$swParsedContent .= '<div class="ticketcontent" style="padding-left:10px; padding-right: 10px;">';
		$swParsedContent .= "\n".trim($text);
		$swParsedContent .="\n\n----\n\n";
		$swParsedContent .= '</div>';
		$status = @$fields['status'][0];
		

		switch($status)
		{
			case 'open':  // allows to comment, reassign, change priority and set reseolved. creator can also close it.
			
			$swParsedContent .= "\n\n".'<nowiki><form method="post" action="index.php?name=special:tickets" enctype="multipart/form-data">
			
<input type="submit" name="submitcomment" value="Comment Ticket" />
<input type="submit" name="submitresolve" value="Set Resolved" />
<input type="hidden" name="MAX_FILE_SIZE" value="'.$swMaxFileSize.'" />
<p>Comment
<textarea name="text" rows="4" cols="80"></textarea>
<p>Reassign to 
'.swHtmlSelect('assigned',$ticketusers,@$fields['assigned'][0],'').'
<p>Change Priority
'.swHtmlSelect('priority',$priorities,@$fields['priority'][0],'').'
<p>Add File
<input type="file" name="uploadedfile" />
<input type="hidden" name="id" value='.$id.'></form></nowiki>';
			
			break;
			case 'resolved':  // only creator can close, but anybody can reopen.  <input type="file" name="uploadedfile" />
			
			if ($username == @$fields['creator'][0])
			{
				$swParsedContent .= "\n\n".'<nowiki><form method="post" action="index.php?name=special:tickets" class="ticketform"><input type="hidden" name="id" value='.$id.'><input type="submit" name="submitreopen" value="Reopen Ticket" /><input type="submit" name="submitclose" value="Close Ticket" /></form></nowiki>';
			}
			else
			{
				$swParsedContent .= "\n\n".'<nowiki><form method="post" action="index.php?name=special:tickets" class="ticketform"><input type="hidden" name="id" value='.$id.'><input type="submit" name="submitreopen" value="Reopen Ticket" /></form></nowiki>';
			}
			break;
			
			case 'closed': // only creator can reopen it	
			
			if ($username == @$fields['creator'][0])
			{
				$swParsedContent .= "\n\n".'<nowiki><form method="post" action="index.php?name=special:tickets" class="ticketform"><input type="hidden" name="id" value='.$id.'><input type="submit" name="submitreopen" value="Reopen Ticket" /></form></nowiki>';
			}
			break;
		}


		$swParsedContent .= '<div class="ticketcontent" style="padding-left:10px; padding-right: 10px;">';
		foreach($lines as $line)
		{
			$swParsedContent .= "''".$line."''\n";
			
		}	




		$swParsedContent .= "\n".'Creator: '.@$fields['creator'][0];
		$swParsedContent .= "\n".'Assigned to: '.@$fields['assigned'][0];
		$swParsedContent .= "\n".'Priority: '.substr(@$fields['priority'][0],2);
		$swParsedContent .= "\n".'Status: '.@$fields['status'][0];
		$swParsedContent .= "\n".'<nowiki><a href="index.php?name=Ticket:'.$id.'&action=edit" target="_blank">Edit Ticket</a> </div></div></nowiki>';
		
}

/*
if (isset($swTicketMail))
{
	$mailreader = new swMailReader;
	$mailreader->init($swTicketServer, $swTicketMail, $swTicketMailPassword);
	foreach($ticketusers as $t) $mailreader->addUser($t);
	$swParsedContent .= "\n"."\n".'===Mail===';
	$id = $mailreader->getNextMessageID();
	$subject = $mailreader->getMessageSubject($id);
	$swParsedContent .= "\n'''".$subject."'''";
	$swParsedContent .= "\n";
	$message = $mailreader->getMessageBodyText($id);
	$swParsedContent .= $message;
	
	// we have the text
	// identify ticket #nn
	// identify separator ----write above this line---
	// 
	
}
*/

 
$swParseSpecial = true;





?>