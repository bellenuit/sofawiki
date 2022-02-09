<?php
	
	
/**
 *	Provides editor template parser function
 *  
 */

if (!defined("SOFAWIKI")) die("invalid acces");

function swParseEditorTemplate($editor, $wiki)
{
	global $swParsers;
	global $user;
	global $lang;
	
	
	$editorwiki = new swWiki;
	$editorwiki->name = "Template:".$editor;
	$editorwiki->lookup();
	$editorwiki->name = ''; // force parse of template
	$editorwiki->parsers = $swParsers;
	$editorwiki->parse();
	
	$s = $editorwiki->parsedContent;
	
	$foundfields = array_keys($wiki->internalfields);
	
	// handle fields
	while(preg_match('/{template\|(.*?)\}/',$s, $match))
	{
		$s1 = str_replace($match[0],'<input type="hidden" name="template" value="'.$match[1].'">',$s);;
		
		if ($s1 == $s) break;
		
		$s = $s1;
	}
	
	while(preg_match('/{name\|(.*?)\}/',$s, $match))
	{
		$v = $match[1];
		if ($wiki->name) $v = $wiki->name;
		$s1 = str_replace($match[0],'<input type="text" name="name" value="'.$v.'" ',$s);
		
		if ($s1 == $s) break;
		
		$s = $s1;
	}
	while(preg_match('/{text\|(.*?)\|(.*?)\}/',$s, $match))
	{
		$foundfields = array_diff($foundfields,array($match[1]));
		$v = $match[2];
		if (isset($wiki->internalfields[$match[1]]))
		{
			
			$v = $wiki->internalfields[$match[1]];
			if (is_array($v)) $v = join('::',$v);
		}
			

		$s1 = str_replace($match[0],'<input type="text" name="'.$match[1].'" value="'.$v.'" ',$s);
		
		if ($s1 == $s) break;
		
		$s = $s1;
	}
	while(preg_match('/{textplus\|(.*?)\|(.*?)\}/',$s, $match))
	{
		$foundfields = array_diff($foundfields,array($match[1]));
		$v = $match[2];
		if (isset($wiki->internalfields[$match[1]]))
		{
			
			$v = $wiki->internalfields[$match[1]];
		}
		$f = '<div id="divplus'.$match[1].'">';
		if (is_array($v))
		{
				$i=1;
				foreach($v as $elem)
				{
					$f .= '<div id="'.$match[1].':'.$i.'"><input type="text" name="'.$match[1].':'.$i.'"  value="'.$elem.'" style="width:80%">'; 
					if ($i>1 || true)
					{
						$f .= '<button type="button" onclick="textupfunction(\''.$match[1].':'.$i.'\')">↑</button>';
					}
					
					$f .= '</div>';
					$i++;
					
				}
				
				//$v = join('::',$v);
		}
		else
		{
				$f .= '<input type="text" name="'.$match[1].'" value="'.$v.'">';
		}
		
		$f.= '</div><button type="button" id="plus'.$match[1].'" onclick="textplusfunction(\''.$match[1].'\')">+</button>';


		$s1 = str_replace($match[0],$f,$s);
		
		if ($s1 == $s) break;
		
		$s = $s1;
	}
	while(preg_match('/{textarea\|(.*?)\|(.*?)\}/',$s, $match))
	{
		$foundfields = array_diff($foundfields,array($match[1]));
		$v = $match[2];
		if (isset($wiki->internalfields[$match[1]]))
		{
			
			$v = $wiki->internalfields[$match[1]];
			if (is_array($v)) $v = join('::',$v);
		}
		$s1 = str_replace($match[0],'<textarea name="'.$match[1].'" rows=10>'.$v.'</textarea>',$s);
		
		if ($s1 == $s) break;
		
		$s = $s1;
	}
	while(preg_match('/{sublang\|(.*?)\|(.*?)\}/',$s, $match))
	{
		
		
		$v = @$match[2];
	
		$sublangwiki = new swWiki;
		$sublangwiki->name = $wiki->namewithoutlanguage().'/'.$match[1];
		$sublangwiki->lookup();
		if ($sublangwiki->revision && $sublangwiki->visible())
		{
			$c = $sublangwiki->content;
			$v = str_replace('{{}}','',$c);
		}

		
		$s1 = str_replace($match[0],'<textarea name="sublang_'.$match[1].'" rows=10>'.$v.'</textarea>',$s);
		
		if ($s1 == $s) break;
		
		$s = $s1;
	}
	while(preg_match('/{check\|(.*?)\|(.*?)\}/',$s, $match))
	{
		
		$foundfields = array_diff($foundfields,array($match[1]));
		$list = array();
		$opts = explode('|',$match[2]);
		
		$set = array();
		if (isset($wiki->internalfields[$match[1]]))
		{
			$set = $wiki->internalfields[$match[1]];
		}
		// print_r($set);
		foreach($opts as $opt)
		{
			if (in_array($opt,$set)) 
				$list[] = ' <span style="white-space:nowrap;"><input type="checkbox" name="'.$match[1].'['.$opt.']" checked /> '.$opt.'</span>';
			else
				$list[] = ' <span style="white-space:nowrap;"><input type="checkbox" name="'.$match[1].'['.$opt.']" /> '.$opt.'</span>';
		}
		$s1 = str_replace($match[0],join(' ',$list),$s);
		
		if ($s1 == $s) break;
		
		$s = $s1;
	}
	while(preg_match('/{radio\|(.*?)\|(.*?)\}/',$s, $match))
	{
		
		$foundfields = array_diff($foundfields,array($match[1]));
		$list = array();
		$opts = explode('|',$match[2]);
		
		
		$set = array();
		if (isset($wiki->internalfields[$match[1]]))
		{
			$set = $wiki->internalfields[$match[1]];
		}
		//print_r($set);
		foreach($opts as $opt)
		{
			if (in_array($opt,$set)) 
				$list[] = ' <span style="white-space:nowrap;"><input type="radio" name="'.$match[1].'" value="'.$opt.'" checked /> '.$opt.'</span>';
			else
				$list[] = ' <span style="white-space:nowrap;"><input type="radio" name="'.$match[1].'" value="'.$opt.'" /> '.$opt.'</span>';
		}
		$s1 = str_replace($match[0],join(' ',$list),$s);
		
		if ($s1 == $s) break;
		
		$s = $s1;
	}
	while(preg_match('/{select\|(.*?)\|(.*?)\}/',$s, $match))
	{
		
		$foundfields = array_diff($foundfields,array($match[1]));
		$list = array();
		$opts = explode('|',$match[2]);
		
		
		$set = array();
		if (isset($wiki->internalfields[$match[1]]))
		{
			$set = $wiki->internalfields[$match[1]];
		}
		//print_r($set);
		$list[] = '<select name="'.$match[1].'">';
		foreach($opts as $opt)
		{
			if (in_array($opt,$set)) 
				$list[] = ' <option value="'.$opt.'" selected >'.$opt.'</option>';
			else
				$list[] = ' <option value="'.$opt.'" >'.$opt.'</option>';
		}
		$list[] = '</select>';
		$s1 = str_replace($match[0],join(' ',$list),$s);
		
		if ($s1 == $s) break;
		
		$s = $s1;
	}

	
	$script = "
<script>
function textplusfunction(id){
theid = id.toString();
div = document.getElementById('divplus'+theid);
children = div.childNodes.length+1;
s = div.innerHTML;
s = s + '<div id=\"'+theid+':'+children+'\"><input type=\"text\" name=\"'+theid+':'+children+'\" value=\"\" style=\"width:80%\"><button type=\"button\" onclick=\"textupfunction(\''+theid+':'+children+'\')\">↑</button>';
div.innerHTML = s;
}

function textupfunction(id){
theid = id.toString();
div = document.getElementById(theid);
parent = div.parentNode;
parent.insertBefore(div,div.previousElementSibling);
}
</script>";



	$editorwiki->parsedContent = $s.$script;
	
	
	
	//if ($wiki->revision)
	//$swParsedContent .= ' <a href="index.php?action=editsource&name='.$name.'">Edit source</a>';
	$result  = PHP_EOL.'<table class="blanktable">';
	$result .= PHP_EOL.'<tr><td>';

	
	$result .=	'<form method="post" action="index.php?action=modifyeditor">';
	
	$result .= PHP_EOL.'<input type="submit" name="submitcancel" value="'.swSystemMessage('cancel',$lang).'" />';
	if ($user->hasright('modify', $wiki->name))	$result .= "<input type='submit' name='submiteditor' value='".swSystemMessage("save",$lang)."' />";

	$result .= $editorwiki->parsedContent;
	
	$result .=	'<input type="hidden" name="editortemplate" value="'.$_REQUEST['editor'].'">';
	
	if ($wiki->revision)	$result .=	'<input type="hidden" name="revision" value="'.$wiki->revision.'">';
	
	
	$foundfields = array_diff($foundfields,array('template','revision','editortemplate','_template','_link'));
	
	if (count($foundfields)) $result .=	'<p><b>Warning unused fields:</b> '.join(', ',$foundfields) ; 
		
	$foundtext = $wiki->content;
	$foundtext = preg_replace('/\[\[.+?::.*?\]\]/','', $foundtext);
	$foundtext = preg_replace('/\{\{.+?\}\}/','', $foundtext);
	
	if (trim($foundtext)) $result .=	'<p><b>Warning unused text:</b> '.$foundtext ; 
	
	$foundcontent = '';	
	foreach($foundfields as $k)
	{
		$vs = $wiki->internalfields[$k];
		if (is_array($vs))
		{
			$foundcontent.= '[['.$k.'::'.join('::',$vs).']]'.PHP_EOL;
		}
		else
		{
			$foundcontent.= '[['.$k.'::'.$vs.']]'.PHP_EOL;
		}
	}
	$foundcontent .= trim($foundtext);
	
	if (trim($foundcontent)) $result .= '<textarea name="unusedtext" style="display:none">'.$foundcontent.'</textarea>';
	
	$result .=	PHP_EOL.'</form>'; 
	$result .= PHP_EOL.'</td></tr>';
	$result .= PHP_EOL.'</table>';

	
	return $result;
							
}


function swInsertFromEditorTemplate($post,$wiki)
{
	$comment = 'editor '.$post['editortemplate'];										
	$content = '{{'.$post['template'].'}}'.PHP_EOL;
	
	// print_r($_POST);
	foreach($post as $k=>$v)
	{
		if ($k == 'editortemplate') continue;
		if ($k == 'submiteditor') continue;
		if ($k == 'name') continue;
		
		$v0 = $v;
		
		if (is_array($v)) 
		{
			// options, keep keys, not values
			$va = array();
			foreach($v as $velem)
				$va[] = swEscape($velem);
			$v = join('::',array_keys($va));
		}
		else
		{
			$v = swEscape($v);
		}
		
		if (strstr($k,':')) // textplus
		{
			$k = substr($k,0,strpos($k,':'));
		}
		
		if (!$v) continue;
		
		if (substr($k,0,strlen('sublang'))=='sublang')
		{
			$sublang = substr($k,strlen('sublang')+1);
			$sublangwiki = new swWiki;
			$sublangwiki->name = $wiki->namewithoulanguage().'/'.$sublang;
			$sublangwiki->lookup();
			if ($sublangwiki->content != '{{}}'.PHP_EOL.$v)
			{
				$sublangwiki->content = '{{}}'.PHP_EOL.$v;
				$sublangwiki->comment = $comment;
				$sublangwiki->insert();
			}
			continue;
		}
		
		if ($k == 'unusedtext')	
		{
			$content .= $v0.PHP_EOL;
			continue;
		}								
		
		$content .= '[['.$k.'::'.$v.']]'.PHP_EOL;
	}
	$content .= '[[editortemplate::'.$post['editortemplate'].']]'.PHP_EOL;
	$wiki->content = $content;
	$wiki->insert();
}
