<?php

if (!defined('SOFAWIKI')) die('invalid acces');

function swRelationExecute($r,$body)
{
	switch(trim($body))
	{
		case 'append':	return swRelationExecuteAppend($r);
		case 'delete':	return swRelationExecuteDelete($r);
		case 'insert':	return swRelationExecuteInsert($r);
		case 'status':  return swRelationExecuteStatus($r);
		case 'update':	return swRelationExecuteUpdate($r);
		case 'undelete':return swRelationExecuteUndelete($r);
		default		 :  $result = new swRelation('_name, result');
						$result->insert('"#ERROR", "excute invalid command"');
						return $result;
	}	
}

function swRelationExecuteAppend($r)
{
	$result = new swRelation('_name, result');
	
	if (!in_array('_name',$r->header))
	{
		$result->insert('"#ERROR", "execute append missing column _name"');
		return $result;
	}
	if (!in_array('_revision',$r->header))
	{
		$result->insert('"#ERROR", "excute append missing column _revision"');
		return $result;
	}
	
	global $swOvertime;
	if ($swOvertime)
	{
		$result->insert('"#ERROR", "search timeout"');
		return $result;
	}
	
	$i=0;
	
	foreach($r->tuples as $t)
	{
		$n = $t->value('_name');
		$rev = $t->value('_revision');
		$n = swNameURL($n);
		$w = new swWiki;
		$w->name = $n;
		$w->lookup();
		
		$limit = 1000;
		if ($i>$limit)
		{
			$result->insert('"'.$n.'","'.$limit.' records limit"');
			continue;
		}
		
		if ($w->revision == $rev)
		{
			switch($w->status)
			{
				case 'ok':			$s = $w->revision.' '.$w->status;
				
									// get all fields
									$fields = $t->fields();
									$c = '';
									foreach($fields as $k=>$v)
									{
										if (! in_array($k, array('_displayname', '_length', '_namespace', '_template', '_content', '_short', '_paragraph', '_word','_any', '_status', '_name', '_revision')))
										{
											$c .= ' [['.$k.'::'.$v.']]';
										}
									}
				
									$w->comment = 'swRelationExecuteAppend '.$w->revision;
									global $username;
									$w->user = $username;
									if (isset($_REQUEST['confirmexecute']))
									{ 
										$w->content .= $c;
										$w->insert();
										$s .= $c.' appended';									
									}
									else
									{
										$s .= $c.' ready to append';
									}
									$i++;
									break;
				case 'protected': 	$s = $w->revision.' '.$w->status.' error';
									break;
				case 'deleted': 	$s = $w->revision.' '.$w->status.' error';
									break;
				default: 			$s = $w->revision.' '.$w->status.' unknown state';
									break;
			}
		}
		else
		{
			if ($w->revision) $s = $w->revision.' wrong revision'; else $s = 'missing revision';
		}
		$result->insert('"'.$n.'","'.$s.'"');
	}
	
	return $result;
}

function swRelationExecuteDelete($r)
{
	$result = new swRelation('_name, result');
	
	if (!in_array('_name',$r->header))
	{
		$result->insert('"#ERROR", "excute delete missing column _name"');
		return $result;
	}
	if (!in_array('_revision',$r->header))
	{
		$result->insert('"#ERROR", "excute delete missing column _revision"');
		return $result;
	}
	
	global $swOvertime;
	if ($swOvertime)
	{
		$result->insert('"#ERROR", "search timeout"');
		return $result;
	}
	
	$i=0;
	
	foreach($r->tuples as $t)
	{
		$n = $t->value('_name');
		$rev = $t->value('_revision');
		$n = swNameURL($n);
		$w = new swWiki;
		$w->name = $n;
		$w->lookup();
		
		$limit = 1000;
		if ($i>$limit)
		{
			$result->insert('"'.$n.'","'.$limit.' records limit"');
			continue;
		}
		
		if ($w->revision == $rev)
		{
			switch($w->status)
			{
				case 'ok':			$s = $w->revision.' '.$w->status;
									$w->comment = 'swRelationExecuteDelete '.$w->revision;
									global $username;
									$w->user = $username;
									if (isset($_REQUEST['confirmexecute']))
									{ 
										$w->delete(); 
										$s .= ' deleted';
									}
									else
									{ 	
										$s .= ' ready to delete';
									}
									$i++;
									break;
				case 'protected': 	$s = $w->revision.' '.$w->status.' error';
									break;
				case 'deleted': 	$s = $w->revision.' '.$w->status.' error';
									break;
				default: 			$s = $w->revision.' '.$w->status.' unknown state';
									break;
			}
		}
		else
		{
			if ($w->revision) $s = $w->revision.' wrong revision'; else $s = 'missing revision';
		}
		$result->insert('"'.$n.'","'.$s.'"');
	}
	
	return $result;
}

function swRelationExecuteInsert($r)
{
	$result = new swRelation('_name, result');
	
	if (!in_array('_name',$r->header))
	{
		$result->insert('"#ERROR", "execute insert missing column _name"');
		return $result;
	}
	
	global $swOvertime;
	if ($swOvertime)
	{
		$result->insert('"#ERROR", "search timeout"');
		return $result;
	}
	
	$i=0;
	
	foreach($r->tuples as $t)
	{
		$n = $t->value('_name');
		$n = swNameURL($n);
		$w = new swWiki;
		$w->name = $n;
		$w->lookup();
		
		$limit = 1000;
		if ($i>$limit)
		{
			$result->insert('"'.$n.'","'.$limit.' records limit"');
			continue;
		}
		
		if ($w->revision && $w->status != 'deleted')
		{
			$s = $w->revision.' '.$w->status.' error';
		}
		else
		{
			
			// get all fields
			$fields = $t->fields();
			$c = '';
			foreach($fields as $k=>$v)
			{
				if (! in_array($k, array('_displayname', '_length', '_namespace', '_template', '_content', '_short', '_paragraph', '_word','_any', '_status', '_name', '_revision')))
				{
					$c .= ' [['.$k.'::'.$v.']]';
				}
			}

			$w->comment = 'swRelationExecuteInsert';
			global $username;
			$w->user = $username;
			if (isset($_REQUEST['confirmexecute']))
			{ 
				$w->content .= $c;
				$w->insert();
				$s .= $c.' inserted';									
			}
			else
			{
				$s .= $c.' ready to insert';
			}
			$i++;

			
		}
		$result->insert('"'.$n.'","'.$s.'"');
	}
	
	return $result;
}

function swRelationExecuteStatus($r)
{
	$result = new swRelation('_name, _revision, _status');
	
	$key = swDbaFirstKey($db->urldb);	
	$urlcount = 0;
	$revisioncount = 0;
	do 
	{
		if (substr($key,0,1)==' ') $revisioncount++; else 
		{
			$result->insert('"'.$key.'", "'.swDbaFetch($key,$db->urldb).'", ""');
		}
			
	} while ($key = swDbaNextKey($db->urldb));
	
	return $result;

}

function swRelationExecuteUpdate($r)
{
	$result = new swRelation('_name, result');
	
	if (!in_array('_name',$r->header))
	{
		$result->insert('"#ERROR", "execute update missing column _name"');
		return $result;
	}
	if (!in_array('_revision',$r->header))
	{
		$result->insert('"#ERROR", "excute update missing column _revision"');
		return $result;
	}
	
	global $swOvertime;
	if ($swOvertime)
	{
		$result->insert('"#ERROR", "search timeout"');
		return $result;
	}
	
	$i=0;
	
	foreach($r->tuples as $t)
	{
		$n = $t->value('_name');
		$rev = $t->value('_revision');
		$n = swNameURL($n);
		$w = new swWiki;
		$w->name = $n;
		$w->lookup();
		
		$limit = 1000;
		if ($i>$limit)
		{
			$result->insert('"'.$n.'","'.$limit.' records limit"');
			continue;
		}
		
		if ($w->revision == $rev)
		{
			switch($w->status)
			{
				case 'ok':			$s = $w->revision.' '.$w->status;
				
									// get all fields
									$fields = $t->fields();
									$w->content = swReplaceFields($w->content,$fields);
				
									$w->comment = 'swRelationExecuteUpdate '.$w->revision;
									global $username;
									$w->user = $username;
									if (isset($_REQUEST['confirmexecute']))
									{ 
										$w->insert();
										$s .= $c.' updated';									
									}
									else
									{
										$s .= $c.' ready to update';
									}
									$i++;
									break;
				case 'protected': 	$s = $w->revision.' '.$w->status.' error';
									break;
				case 'deleted': 	$s = $w->revision.' '.$w->status.' error';
									break;
				default: 			$s = $w->revision.' '.$w->status.' unknown state';
									break;
			}
		}
		else
		{
			if ($w->revision) $s = $w->revision.' wrong revision'; else $s = 'missing revision';
		}
		$result->insert('"'.$n.'","'.$s.'"');
	}
	
	return $result;
}

function swRelationExecuteUndelete($r)
{
	$result = new swRelation('_name, result');
	
	if (!in_array('_name',$r->header))
	{
		$result->insert('"#ERROR", "excute undelete missing column _name"');
		return $result;
	}
	if (!in_array('_revision',$r->header))
	{
		$result->insert('"#ERROR", "excute undelete missing column _revision"');
		return $result;
	}
	
	global $swOvertime;
	if ($swOvertime)
	{
		$result->insert('"#ERROR", "search timeout"');
		return $result;
	}
	
	$i=0;
	
	foreach($r->tuples as $t)
	{
		$n = $t->value('_name');
		$rev = $t->value('_revision');
		$n = swNameURL($n);
		$w = new swWiki;
		$w->name = $n;
		$w->lookup();
		
		$limit = 1000;
		if ($i>$limit)
		{
			$result->insert('"'.$n.'","'.$limit.' records limit"');
			continue;
		}
		
		if ($w->revision == $rev)
		{
			switch($w->status)
			{
				case 'deleted':		$s = $w->revision.' '.$w->status;
									$w->comment = 'swRelationExecuteUndelete '.$w->revision;
									global $username;
									$w->user = $username;
									if (isset($_REQUEST['confirmexecute']))
									{ 
										$w->inserte(); 
										$s .= ' undeleted';
									}
									else
									{ 	
										$s .= ' ready to undelete';
									}
									$i++;
									break;
				case 'protected': 	$s = $w->revision.' '.$w->status.' error';
									break;
				case 'ok': 			$s = $w->revision.' '.$w->status.' error';
									break;
				default: 			$s = $w->revision.' '.$w->status.' unknown state';
									break;
			}
		}
		else
		{
			if ($w->revision) $s = $w->revision.' wrong revision'; else $s = 'missing revision';
		}
		$result->insert('"'.$n.'","'.$s.'"');
	}
	
	return $result;
}
