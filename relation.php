<?php
	
ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);
ini_set("memory_limit", "-1");
set_time_limit(0);

define('SOFAWIKICLI',true); 

define('NORMAL',"\033[0m");
define('BOLD',"\033[1m"); // use double quotes!
define('ITALIC',"\033[3m");  //  not widely supported
define('INVERT',"\033[7m");
define('REDFG',"\033[31m");
define('GREENFG',"\033[32m");
define('DEL',"\0177");

include 'api.php';

$swMemoryLimit = 6000000000;

function wikiterminal($s)
{
	$s = preg_replace("/=(=)+(.*?)(=)+=/",PHP_EOL.BOLD.'$2'.NORMAL.PHP_EOL,$s);
	$s = preg_replace("/'''''(.*?)'''''/",BOLD.ITALIC.'$1'.NORMAL,$s);
	$s = preg_replace("/'''([^'].*?)[^']'''/",BOLD.'$1'.NORMAL,$s);
	$s = preg_replace("/''([^'].*?)[^']'/",ITALIC.'$1'.NORMAL,$s);
	return $s;
}


function gethelp($topic)
{
	
	switch ($topic)
	{
		
		case 'commit': return 'commit <tablename>

Commits an edit instruction

Example:
commit films
';	

		
		case 'instructions' : return 'Help instructions
		
Global Instructions: set, echo
Create relations: relation, read, database, database
Output relations: format, label print, write 
Unary relational algebra: select, project, rename, extend 
Binary relational algebra: join, union, difference, intesection 
Non-relational instructions: order, limit
Editing: edit, commit, update
Stack operators: dup, pop, swap
Advanced instructions: analyze, assert, compile, deserialize, formatdump, history, result, serialize, stack, stop
';
		
		case 'database': return 'database <expression>

Connects to a SQLite database (read and write). 

If the database does not exist, it tries to create it.
If the path is empty, the database is created in memory.

Exemple:
databse "file.db"
';	

		case 'difference': return 'difference

Subtracts the top relation of the second top relation. Both must have the same columns.

Exemple:
difference
';	
		
		
		case 'echo': return 'echo <expression>

Writes to console

Exemple:
echo 5*4
';	

		case 'edit': return 'edit <rownumber> <column>

Starts editing a value of the current relation. Leave empty to cancel. 
Only the current relation on the stack is edited. If you want to edit in the database, then commit after edit

Exemple:
edit 42 director
';	


		case 'extend': return 'set <name> = <expression>

Creates a new column based on calculations of the other columns 

Exemple:
extend captitle = upper(title)
';	


		case 'format': return 'format <name> <string> (, <name> <string>)*

Defines output formats for columns of the current expression.

Simple printf formats can be used.
There are special formats for numbers (n, N) and percentage (p, P).

Exemple:
format year "%04d"
';	

		case 'insert': return 'insert <expression> (, <expression>)*

Adds a new tuple to the current relation.

Exemple:
insert "Home", "Ursula Meier", 2007)
';	

	case 'instersection': return 'interection

Intersects the two top relations. They must have the same columns

Exemple:
intersection
';	

		case 'join': return 'join <option>

Joins the two top relations

Options
- join natural: based on common columns
- join left: based on common columns, adding missing rows on the right side
- join right: based on common columns, adding missing rows on the left side
- join outer: based on common columns, adding missing rows on the left side
- join leftsemi
- join rightsemi
- join leftanti
- join rightanti
- join cross
- join <expreesion>

Exemple:
join natural
';	

		case 'label': return 'label <name> <string> (, name <string>)*

Defines a column title for output

Exemple:
label director "Réalisation"
';	

case 'limit': return 'limit <start> <length>

Retains only a subset of the relation. Start is 1-based.

Exemple:
limit 1 100
';	



	case 'print': return 'print <options>

Prints the current relation to the console.

Options
- print csv: print as csv
- print json: print as json
- print tab: print as tab
- print <number>: print the first rows only
- print <string>: print rows containing the value in string (fast filter)

Exemple:
print godard
';	

case 'order': return 'order <name> <options> (, <name> <options>)* 

Orders the relation

Options
- a alphabetical case-insensitive
- A alphabetical case-sensitive
- z reverse alphabetical case-insensitive
- Z reverse alphabetical case-sensitive
- 1 numerical bottom-up
- 1 numerical top-down

Exemple:
order year 9, director a
';	

		case 'project': return 'project <name> (<aggregator>)(, <name> <aggregator>)*

Selects columns and aggregates rows

Aggregators: count, avg, max, median, min, stdev, sum

Exemple:
project film, director
project year, duration sum
';	

		case 'read': return 'read <filepath>

Reads a file into a new relation. 

Supported formats: .csv, .html, .json, .rel, .xml

Without extension, relation is read from memory. 

Extension .rel loads and rexecutes relation instructions. 

Exemple:
read "film.csv"
';	

		case 'relation': return 'relation <name> (, <name>)*

Creates a new relation with a list of columns and puts it on the stack.

Exemple:
relation film, director, year
';	

case 'set': return 'rename <name> = <newname> (, <name> = <newname>)*

Renames columns

Exemple:
rename director olddirector
';	
		
		case 'set': return 'set <name> = <expression>

Defines a local variable

Exemple:
set currentyear = 2020
';	


		case 'sql': return 'sql <expression>

Sends a SQL query or command to the current database and puts the resulting relation on the stack

Exemple:
sql "select * from films"
';	

		case 'select': return 'select <expression>

Filters rows based on expression

Exemple:
select year > 1970
';	

	case 'union': return 'union

Merges the two top relations. They must have the same columns

Exemple:
union
';	

	case 'update': return 'update <name> = <expression> (where <expression>)

Updates columns based on expression

Exemple:
update director = "godard" where year < 1970
';	

		case 'write': return 'write <filepath>

Writes the current relation to a file

Supported formats: .csv, .html, .json, .rel,

Without extension, relation is written to memory. 

Extension .rel writes the history to a file (to be reexecuted with read). 

Exemple:
write "film.csv"
';	

		
		default: return 'Help

Edit CSV and SQlite files in interactive mode with the Relation language.

Usage: Every line consists of an instruction and options, that work on a stack of relations.
Instructions are single words, all lower case.
Options can be numbers, double quoted strings, variables and algebraic expressions.

help instructions
help functions
help <instruction>
help <function>

Full documenation: https://www.belle-nuit.com/relation/ 
';
	}
} 


readline_completion_function(function($Input, $Index){
    
    $commands = array('analyze','assert','beep', 'compile','data','database','delegate','deserialize','difference','dup','echo', 'else','end','extend','format','formatdump','function','if','input','intersection','join','label','limit','order','parameter','parse','pivot','pop','print','program','project','read','relation','rename','repeat','run','select','serialize','set','sql','stack','stop','swap','transaction','union','update','walk','while','write');
    
    global $e;
    if ($r = end($e->stack))
    {
	    foreach($r->header as $h) $commands[]=$h;
    }
    
    
    // Filter Matches    
    $Matches = array();
    foreach($commands as $c)
        if(stripos($c, $Input) === 0)
            $Matches[] = $c;
    
    // Prevent Segfault
    if($Matches == false)
        $Matches[] = '';
    
    return $Matches;
});




$sapi_type = php_sapi_name(); 
if (substr($sapi_type, 0, 3) != 'cli') die('invalid acces '.$sapi_type);

echo BOLD.'Relation CLI interactive mode'.NORMAL.PHP_EOL; 

$offset = 0;
$i = 0;
$text = '';
$echo = '';
$e = new swRelationLineHandler('text');
$e->decoration['ptagerror'] = REDFG;// '\e[0;31;42m';
$e->decoration['ptagerrorend']= NORMAL;
$e->decoration['csv']= '';
$e->decoration['csvend']= PHP_EOL;
$e->decoration['json']= '';
$e->decoration['jsonend']= PHP_EOL;
$e->decoration['tab']= '';
$e->decoration['tabend']= PHP_EOL;
$e->decoration['ptag2']= PHP_EOL;

do {
        $line = readline('> ');
        
        if($line == 'q' || $line == 'quit') break;
        
        $e->offsets[$i] = $i; // preserves history and executes line per line
		
        $fields = explode(' ',$line);
		$command = array_shift($fields);
		$body = join(' ',$fields);
		
		if ($command != 'commit') $statement = '';
        
        switch($command)
        {
	        
	        case "commit":  if (trim($body) && substr($statement,0,strlen('UDPATE'))=='UPDATE')
	        				{
		        				$body = str_replace('"','',$body);
		        				$line = 'sql "UPDATE '.$body.' SET '.substr($statement,strlen('UDPATE')).'"';
		        				$echo = $e->run($line, $i, false, false);
		        				$e->result = false;
		        				if (!$echo)
		        				{
			        				$echo = GREENFG.'Committed'.NORMAL.PHP_EOL;
			        				$statement = '';
			        			}
	        				}
							break;
	        
	        case 'edit':	
	        				$e->assert(defined('SOFAWIKICLI'),'Edit only in CLI',$i);
							$fields = explode(' ',trim($body));
							$e->assert(count($fields)==2,'Edit parameter is not 2',$i);
							$editrow = $fields[0];
							$editfield = $fields[1];
							$r = end($e->stack);
							$i=0;
							$found = false;
							foreach($r->tuples as $t)
							{
								$i++;
								if ($i == $editrow)
								{
									if ($t->hasKey($editfield))
									{
										$v = $t->value($editfield);
										echo $r->toText($editrow,true); 
										$line2 = readline('Edit '.$editrow.' field '.$editfield.' from '.BOLD.$v.NORMAL.' to > ');
										
										if ($line2)
										{
											echo GREENFG.'New value '.BOLD.$line2.NORMAL.PHP_EOL;
											
											
											
											$d = $t->fields();
											$d[$editfield] = $line2;
											$t2 = new swTuple($d);
											
											$before = array_slice($r->tuples,0,$i-1);
											$after = array_slice($r->tuples,$i);
											
											$r->tuples = array_merge($before,array($t2),$after);
											
											$statement = "UPDATE $editfield = '$line2' WHERE ";
											$kount=0;
											foreach($t->fields() as $k=>$v)
											{
												$v = SQLite3::escapeString($v);
												if ($kount>0) $statement .= " AND $k = '$v' ";
												else $statement .= " $k = '$v' ";
												$kount++;
											}
											
											echo $r->toText($editrow,true); 
											
											if ($e->currentdatabase)
												echo $statement.PHP_EOL;
											
											
										}
										else
										{
											echo REDFG. 'Cancelled '.NORMAL.PHP_EOL;
										}
										$found = true;
										
										break;
									}
									break;	
								}
									
							}
							$echo = '';
							if (!$found)
								$echo = REDFG.'line '.BOLD.$editrow.NORMAL.REDFG.' field '.BOLD.$editfield.NORMAL.REDFG.' not found'.NORMAL.PHP_EOL;
							
							break;
							
	        case 'help':	echo gethelp($body);
	        				break;
	        				
	        case 'history': $echo = join(PHP_EOL,readline_list_history()); 
	        				break;
	        case 'result':	$echo = $text;
	        				break;
	        default: 		$echo = $e->run($line, $i, '', false);
	        				$e->result = false;
	        				$e->history []= $line;
	        				readline_add_history($line);
        }
		
        
        $echo = wikiterminal($echo);
        $echo = str_replace(PHP_EOL.PHP_EOL,PHP_EOL,$echo);
        $echo = trim($echo);
        if ($echo !== '') $echo.=PHP_EOL;
        echo $echo;
        
        $text .= $echo;
        
		$i++; 
        
} while (true);

echo "Bye...".PHP_EOL;

// @uopz_allow_exit(true); // apparently, some code prevents exit when running in phar and including files


