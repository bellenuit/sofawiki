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

function swhtmlselect($name, $list, $v, $default)
{
	if (!$v) $v = $default;
	$s = '<select name="'.$name.'" style="width:95%">';
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
project id max
print raw';
	$lh = new swRelationLineHandler;
	$s = $lh->run($q);
	$s = explode(PHP_EOL,$s); // tab format
	array_shift($s); // header;
	
	$id = (int)@$s[0]+1;
	
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
	'[[activity::'.date('Y-m-d h:i',time()).' '.$activity.']]';
	$w->insert();

	$link = '<nowiki><a href="index.php?name=special:tickets&id='.$id.'">ticket #'.$id.'</a></nowiki>';
	$swParsedContent .= PHP_EOL.PHP_EOL.str_replace('ticket #'.$id,$link,$activity);
	$assigned = '';
	$mytickets = false;
	$status = '';
	$id = '';
}

if (isset($_POST['submitcomment']))
{
	
	$w = new swWiki;
	$w->name = 'Ticket:'.$id;
	$w->lookup();
	$fields = $w->internalfields;
	$oldtext = $w->content;
	$oldtext = preg_replace('/\[\[(.*)::(.*)\]\]/', '', $oldtext);
	
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
	'[[activity::'.join('::',@$fields['activity']).'::'.date('Y-m-d h:i',time()).' '.$activity.']]';
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
	'[[activity::'.join('::',@$fields['activity']).'::'.date('Y-m-d h:i',time()).' '.$activity.']]';
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
	'[[activity::'.join('::',@$fields['activity']).'::'.date('Y-m-d h:i',time()).' '.$activity.']]';
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
	'[[activity::'.join('::',@$fields['activity']).'::'.date('Y-m-d h:i',time()).' '.$activity.']]';
	$w->insert();
	
	
	$link = '<nowiki><a href="index.php?name=special:tickets&id='.$id.'">ticket #'.$id.'</a></nowiki>';
	$swParsedContent .= PHP_EOL.PHP_EOL.str_replace('ticket #'.$id,$link,$activity);
	$mytickets = false;
	$assigned = '';
	$id = '';
}



if ($ticketaction == 'new')
{

	$swParsedContent .= "\n".'===New Ticket===';
	$swParsedContent .= "\n".'<nowiki><form method="post" action="index.php?name=special:tickets" class="ticketform"><table class="blanktable"><tr><td >Title</td><td ><input type="text" name="title" value="" autocomplete="off" style="width:95%" /></td></tr><tr><td>Text</td><td><textarea name="text" rows="20" cols="80" style="width:95%"></textarea></td></tr><tr><td>Assign to </td><td>'.swhtmlselect('assigned',$ticketusers,$username,$username).'</td></tr><tr><td>Priority</td><td>'.swhtmlselect('priority',$priorities,'2 normal','2 normal').'</td></tr><tr><td></td><td><input type="submit" name="submitopen" value="Open Ticket" /></td></tr></table></form></nowiki>';
	$mytickets = false;
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
		$lines = swRelationToTable('filter _namespace "ticket", id, title, priority, assigned, status "'.$status.'"
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
			$swParsedContent .= "\n".'<nowiki><a href="index.php?name=special:tickets&id='.@$line['id'].'">ticket #'.@$line['id'].'</a></nowiki> (assigned to '.@$line['assigned'].' with priority '.substr(@$line['priority'],2).'): '.@$line['title'];
		  $i++;
		}
		$mytickets = false;
		$assigned = '';
}

if ($mytickets) $assigned = $username; // default


if ($assigned && !$id)
{
		$lines = swRelationToTable('filter _namespace "ticket", id, title, priority, status, assigned "'.$assigned.'"
select status !== "closed"
select status !== "resolved"
order priority, id 9');
		$i=0;
		$swParsedContent .= "\n".'===Tickets assigned to '.$assigned.'===';
		foreach($lines as $line)
		{
			if ($i > 100) break;
			$swParsedContent .= "\n".'<nowiki><a href="index.php?name=special:tickets&id='.@$line['id'].'">ticket #'.@$line['id'].'</a></nowiki> ('.substr(@$line['priority'],2).'): '.@$line['title'];
		  $i++;
		}
}


if ($id)
{
		$w = new swWiki;
		$w->name = 'Ticket:'.$id;
		$w->lookup();
		$fields = $w->internalfields;
		$swParsedContent .= "\n".'===Ticket #'.$id.': '.array_pop($fields['title']).'===';
		
		$text = $w->content;
		$text = preg_replace('/\[\[(.*)::(.*)\]\]/', '', $text);
		$lines = $fields['activity'];
		$swParsedContent .= "\n".trim($text);
		$swParsedContent .="\n\n----\n\n";
		foreach($lines as $line)
		{
			$swParsedContent .= "''".$line."''\n";
			
		}	
		
		$status = @$fields['status'][0];
		
		switch($status)
		{
			case 'open':  // allows to comment, reassign, change priority and set reseolved. creator can also close it.
			
			$swParsedContent .= "\n\n".'<nowiki><form method="post" action="index.php?name=special:tickets" class="ticketform"><table><tr><td>Comment</td><td><textarea name="text" rows="10" cols="80" style="width:95%"></textarea></td></tr><tr><td>Reassign to </td><td>'.swhtmlselect('assigned',$ticketusers,@$fields['assigned'][0],'').'</td></tr><tr><td>Change Priority</td> <td>'.swhtmlselect('priority',$priorities,@$fields['priority'][0],'').'</td></tr><tr><td></td><td><input type="hidden" name="id" value='.$id.'><input type="submit" name="submitcomment" value="Comment Ticket" /><input type="submit" name="submitresolve" value="Set Resolved" /></td></tr></table></form></nowiki>';
			
			break;
			case 'resolved':  // only creator can close, but anybody can reopen.
			
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
		$swParsedContent .= "\n".'Creator: '.@$fields['creator'][0];
		$swParsedContent .= "\n".'Assigned to: '.@$fields['assigned'][0];
		$swParsedContent .= "\n".'Priority: '.substr(@$fields['priority'][0],2);
		$swParsedContent .= "\n".'Status: '.@$fields['status'][0];
		$swParsedContent .= "\n".'<nowiki><a href="index.php?name=Ticket:'.$id.'&action=edit" target="_blank">Edit Ticket</a> </nowiki>';
		
}



 
$swParseSpecial = true;





?>