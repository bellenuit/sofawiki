<?php

if (!defined("SOFAWIKI")) die("invalid acces");

function echotime($s)
{
	global $swStartTime;
	$endtime = microtime(true);
	global $swDebug;
	$swDebug .=  sprintf('%04d',($endtime-$swStartTime)*1000).' '.$s.'<br>';
}

function echomem($s,$direct=false)
{
	global $swDebug;
	$k = sprintf('%0d',memory_get_usage()/1024/1024);
	if ($direct) echo '<p>'.$k.'k '.$s;
	$swDebug .=  '____ '.$k.' MB '.$s.'<br>';
}


function swNotify($action,$error,$label,$message,$receiver="",$plain=true)
{
	global $username;
	global $name;
	global $lang;
	
	
	
	// send email

	global $swNotifyMail;
	global $swNotifyMailBCC;
	global $swNotifyMailCC;
	global $swNotifyMailReplyTo;
	global $swNotifyActions;
	$actions = array_flip($swNotifyActions);
	
	if ($swNotifyMail)
	{
		$headers = "From: $swNotifyMail" . "\r\n";

		if($swNotifyMailReplyTo != '')
		{
			$headers .= "Reply-To: $swNotifyMailReplyTo" . "\r\n";
		}
		else
		{
			$headers .= "Reply-To: $swNotifyMail" . "\r\n";
		}

   		if($plain)
   		{
   			$headers .= 'Content-type: text/plain; charset=UTF-8' . "\r\n" ;
   		}
   		else
   		{
   			$headers .= 'MIME-version: 1.0' . "\r\n" .
   			'Content-type: text/html; charset=UTF-8' . "\r\n";	
   		}
   		
   		
		if (array_key_exists($action,$actions))
		{
			if ($receiver !="")
			$receiver .= ", ";
			
			$headers .= "Cc: $swNotifyMail, $swNotifyMailCC". "\r\n".
   		"Bcc: $swNotifyMailBCC". "\r\n";
			
		}
		$headers .= 'X-Mailer: PHP/' . phpversion();
	}
	else
	{	
		$headers = "";
	}
	
	
	if ($receiver !="")
		$preferences = array('input-charset' => 'UTF-8', 'output-charset' => 'UTF-8');
		$encoded_subject = @iconv_mime_encode('Subject', $label, $preferences);
		$encoded_subject = substr($encoded_subject, strlen('Subject: '));
		
		if (!mail($receiver, $encoded_subject, $message, $headers))
		{		$error .= ' mail not sent';
		}
				
	swLog($username,$name,$action,"",$lang,"","",$error,$label,$message,$receiver);
	
}


function swLog($user,$name,$action,$query,$lang,$referer,$time,$error,$label,$message,$receiver)
{
	global $swRoot;
	// write to log
	$timestamp = date("Y-m-d H:i:s",time());
	$daystamp = date("Y-m-d",time());
	$memory = memory_get_usage();
	global $ip;
	
	if ($user=='')
	{
		//echo "IP $ip";
		$user = $ip;
		
		global $swEncryptionSalt ;
		global $swLogAnonymizedIPNumber;
		
		if ($swLogAnonymizedIPNumber)
		{
			$fields = explode('.',$ip);
			$last = array_pop($fields);
			$last = hexdec(substr(md5($last.$ip.$swEncryptionSalt),0,2));
			array_push($fields,$last);
			$user = join('.',$fields);
			//echo " $ip";
		}
		
	}
	
	
	$t = "[[timestamp::$timestamp]] [[user::$user]] [[name::$name]] [[action::$action]] ";
	if ($query)
		$t .= "[[query::$query]] ";
	if ($query)
		$t .= "[[lang::$lang]] ";
	if ($referer)
		$t .= "[[referer::$referer]] ";
	if ($time)
		$t .= "[[time::$time]] ";
	if ($memory)
		$t .= "[[memory::$memory]] ";
	if ($error)
		$t .= "[[error::$error]] ";
	if ($label)
		$t .= "[[label::$label]] ";
	if ($receiver)
		$t .= "[[receiver::$receiver]] ";
	$t .="\n";
	
	$file = $swRoot."/site/logs/$daystamp.txt";
	if (!is_dir($swRoot.'/site/logs')) mkdir($swRoot.'/site/logs');
	if ($handle = fopen($file, 'a')) { fwrite($handle, $t); fclose($handle); }
	else { swException('Write error swNotify $action.txt');}
	
	
	
	
}

function swLogWrongPassword($ip)
{
	global $swRoot;
	$daystamp = date("Y-m-d",time());
	$timestamp = date("Y-m-d H:i:s",time());
	$file = $swRoot."/site/logs/deny-$daystamp.txt";
	if (!is_dir($swRoot.'/site/logs')) mkdir($swRoot.'/site/logs');
	$t = "[[timestamp::$timestamp]] [[ip::$ip]]\n";
	if ($handle = fopen($file, 'a')) { fwrite($handle, $t); fclose($handle); }

	$file = $swRoot."/site/logs/deny-$daystamp.txt";
	if (!is_dir($swRoot.'/site/logs')) mkdir($swRoot.'/site/logs');
	
}

function swLogAllow($ip)
{
	global $swRoot;
	$file = $swRoot."/site/indexes/deny.txt";
	if (!is_dir($swRoot.'/site/indexes')) mkdir($swRoot.'/site/indexes');
	if (file_exists($file)) 
	{
		
		$t = file_get_contents($file);
		$d0 = swGetValue($t,$ip);
		$t = str_replace("[[$ip::$d0]]","",$t);
		if ($handle = fopen($file, 'w')) { fwrite($handle, $t); fclose($handle); }
		else echotime('error fopen w '.$file);
		
	}
	global $username, $name, $lang, $referer, $error, $label, $message, $receiver;
	
	$daystamp = date("Y-m-d",time());
	$timestamp = date("Y-m-d H:i:s",time());
	
	$file = $swRoot."/site/logs/deny-$daystamp.txt";
	if (!is_dir($swRoot.'/site/logs')) mkdir($swRoot.'/site/logs');
	$t = "[[timestamp::$timestamp]] [[allow::$ip]]\n";
	if ($handle = fopen($file, 'a')) { fwrite($handle, $t); fclose($handle); }
	
	swLog($username,$name,'allow','',$lang,$referer,$timestamp,$error,$label,$message,$receiver);
}

function swLogDeny($ip,$denyend='')
{
	
	global $swRoot;
	if (trim($denyend)=='') $denyend = date("Y-m-d",time());
	$file = $swRoot."/site/indexes/deny.txt";
	if (!is_dir($swRoot.'/site/indexes')) mkdir($swRoot.'/site/indexes');
	if (file_exists($file)) 
	{
		$t = file_get_contents($file);
	}
	else
	{
		$t = '';
	}
	
	//$d0 = swGetValue($t,$ip);
	//$t = str_replace("[[$ip::$d0]]","",$t);
	if (strstr($t,"[[$ip::$denyend]]")) return;
	
	$t .= "[[$ip::$denyend]]";
	if ($handle = fopen($file, 'w')) { fwrite($handle, $t); fclose($handle); }
	else  echotime('error fopen w '.$file);

	global $username, $name, $lang, $referer, $error, $message, $receiver;
	$timestamp = date("Y-m-d H:i:s",time());
	$label = "deny until $denyend";
	swLog($ip,$name,'deny','',$lang,$referer,'',$error,$label,$message,$receiver);
	

}


function swGetDeny($ip)
{
	
	global $swDenyCount;
	if ($swDenyCount == 0) $swDenyCount = 10;
	global $swRoot;
	$daystamp = date("Y-m-d",time());

	$file = $swRoot."/site/indexes/deny.txt";
	if (file_exists($file)) 
	{
		$t = file_get_contents($file);
		$d0 = swGetValue($t,$ip);
		if ($d0 != '' && $d0 >= date("Y-m-d",time()))
			return true;
	}

	$file = $swRoot."/site/logs/deny-$daystamp.txt";
	if (!file_exists($file)) return false;
	$t = file_get_contents($file);
	if (substr_count($t,"[[ip::$ip]]") > $swDenyCount && $swDenyCount> 0) 
	{
		if (substr_count($t,"[[allow::$ip]]") > 0) return false;
		
		swLogDeny($ip);	
		return true;
	}
	
}
