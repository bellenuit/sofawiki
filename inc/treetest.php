<?php

error_reporting(E_ERROR | E_WARNING | E_PARSE);
ini_set("display_errors",1); 


define("SOFAWIKI",1); 

include_once "notify.php";
include_once "tree.php";

@unlink('test.txt');
$tree = new swTree;
$tree->path = 'test.txt';
$tree->open();


$s = "In September of that year, Rasmus expanded upon PHP and - for a short time - actually dropped the PHP name. Now referring to the tools as FI (short for Forms Interpreter), the new implementation included some of the basic functionality of PHP as we know it today. It had Perl-like variables, automatic interpretation of form variables, and HTML embedded syntax. The syntax itself was similar to that of Perl, albeit much more limited, simple, and somewhat inconsistent. In fact, to embed the code into an HTML file, developers had to use HTML comments. Though this method was not entirely well-received, FI continued to enjoy growth and acceptance as a CGI tool --- but still not quite as a language. However, this began to change the following month; in October, 1995, Rasmus released a complete rewrite of the code. Bringing back the PHP name, it was now (briefly) named Personal Home Page Construction Kit, and was the first release to boast what was, at the time, considered an advanced scripting interface. The language was deliberately designed to resemble C in structure, making it an easy adoption for developers familiar with C, Perl, and similar languages. Having been thus far limited to UNIX and POSIX-compliant systems, the potential for a Windows NT implementation was being explored.";


//$s = "a a b a a";

$list = explode(" ",$s);

shuffle($list);
//$list=array_unique($list);
$c = count($list);
//echo "set $c<br>";
for($i = 0; $i<$c;$i++)
{
	//if($list[$i]=='') continue;
	//echo "$list[$i] $i<br>";
	$tree->add($list[$i],$i);
}
//echo "get $c<br>";
$tree->close();
$tree->open();
for($i = 0; $i<$c;$i++)
{
	$j = rand(1,$c-1);
	$v = $tree->get($list[$j]);
	//echo "$list[$j] $j = $v<br>";
}

//echo $tree->dump();
echo "<pre>";
print_r($tree->match('~*','th'));
print_r($tree->match('~*','ph'));
print_r($tree->match('*=','ing'));

?>