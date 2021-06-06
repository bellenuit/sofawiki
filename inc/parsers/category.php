<?php

if (!defined('SOFAWIKI')) die('invalid acces');

class swCategoryParser extends swParser
{

	function info()
	{
	 	return 'Creates category directory';
	}


	function dowork(&$wiki)
	{
		
		if (substr($wiki->originalName,0,9) == 'Category:' || substr($wiki->originalName,0,9) == 'category:')
		{
			
			global $swSearchNamespaces;
			$namespaces = 'main|'.strtolower(join('|',array_filter($swSearchNamespaces)).' ');
			
			$wn = $wiki->originalName;
			$wn = substr($wn,strlen('category:'));
			if ($p = strpos($wn,'/'))
				$wn = substr($wn,0,$p);
			
			$wn = swNameURL($wn);
			
			if (!function_exists('swInternalCategoryHook')) 
			{
				$s = $wiki->parsedContent;
				
				$q = 'filter _namespace "'.$namespaces.'", _name, _category "'.$wn.'"
select _category regexi "^'.$wn.'$"
update _name = "[["._name."]]"
update _name = "[[:".substr(_name,2,99) where _namespace regex "image|category"
project _name
label _name ""
print grid';
				
				
				$lh = new swRelationLineHandler;
				$s .= $lh->run($q);
				
				$wiki->parsedContent = $s;
				
			}
			else
			
			{
				$q = 'filter _namespace "'.$namespaces.'", _name, _category "'.$wn.'"
select _category regexi "^'.$wn.'$"';
				$revisions = swRelationToTable($q);
				$names = array();
				
				foreach($revisions as $row)
				{
					$n = $row['_name'];
				
					//filter out namespaces now only
					if ($p=strpos($n,':')>0)
					{
						$ns = strtolower(substr($n,0,$p)).' ';
						if (!stristr($namespaces,$ns)) continue;
					}
				
				$names[$n] = '';
					
				}
				ksort($names);
				
				$gprefix = '<div id="categoryList"><ul>';
				$gpostfix = '</ul></div>';
				
				$hookresult = swInternalCategoryHook($names,$wn);
				if ($hookresult)
				{
					if (isset($hookresult['gprefix']))
						$gprefix = $hookresult['gprefix'];
					else
						$gprefix = '';
					if (isset($hookresult['gpostfix']))
						$gpostfix = $hookresult['gpostfix'];
					else
						$gpostfix = '';
					if (isset($hookresult['names']))
					$names = $hookresult['names'];
				else
					$names = '';
				if (isset($hookresult['separator']))
					$separator = $hookresult['separator'];
				else
					$separator = '';
				if (isset($hookresult['limit']))	
					$limit = $hookresult['limit'];
				else
					$limit = '';
				}

									
				
				 
							
				// print_r($revisions);
				
				
				
				
				
				
				// now we have a sorted lists of all names;
				
				
				$separator = "\n";
				$limit = 0;
				// function can reorder list and apply custom templates for each name
				if (function_exists('swInternalCategoryHook')) 
				{
				}
				if ($limit==0) $limit = 50;
				
				if (isset($_REQUEST['start']))	
					$start = $_REQUEST['start'];
				else
					$start = 0;
				$count = count($names);
				global $lang;
				global $name;
				if (count($names)>$limit || true)
				{
					$navigation = '<div class="categorynavigation">';
					if ($start>0)
						$navigation .= '<a href="index.php?name='.swNameURL($wiki->originalName)
						.'&start='.sprintf("%0d",$start-$limit).'"> '.
						swSystemMessage('back',$lang).'</a>';
					$navigation .= " ".sprintf("%0d",min($start+1,$count))." - ".sprintf("%0d",min($start+$limit,$count))." / ".$count;
					if ($start<$count-$limit)
						$navigation .= ' <a href="index.php?name='.swNameURL($wiki->originalName).'&start='.sprintf("%0d",$start+$limit).
						'">'.swSystemMessage('forward',$lang).'</a>';
					$navigation .= '</div>';
				}			
				
				
				$list = array();
				$i=0;
				foreach ($names as $k=>$v)
				{
						$i++;
						if ($i<=$start) continue; 
						if ($i>$start+$limit) continue; 
						
						
						if ($v)
						{
							$elem = $v;
						}
						else
						{
							$elem = '<li>[['.$k.']]</li>';
						}
						
						$list[$k] = $elem;
				}
				
				$s = $wiki->parsedContent;
				
				
				
				$s .= $navigation.$gprefix.join($separator,$list).$gpostfix.$navigation;
				
				$wiki->parsedContent = $s;
			}
		}
		
	}

}
$swParsers["category"] = new swCategoryParser;



?>