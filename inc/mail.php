<?php
	
/**
 *	Class to read imap mail
 *  
 */
 
 if (!defined("SOFAWIKI")) die("invalid acces");
 
 class swMailReader extends swPersistance
 {
	 var $server;
	 var $account;
	 var $password;
	 var $knownmessages = array();
	 var $allowedusers = array();
	 var $mbox;
	 
	 function init($s, $a, $p)
	 {
		 $this->server = $s;
		 $this->account = $a;
		 $this->password = $p;
		 $this->mbox = @imap_open($this->server, $this->account, $this->password);
		 if (!$this->mbox) echotime('connection mbox failed');
	 }
	 
	 function addUser($u)
	 {
		 $this->allowedusers[] = $u;
	 }

	 function getNextMessageID()
	 {
	    if ($this->mbox)
	    {
		    $emails = imap_search($this->mbox, 'ALL');
		    rsort($emails);
		    foreach($emails as $msg_number)
		    {
				$uid = imap_uid($this->mbox,$msg_number);		    
			    if (in_array($uid, $this->knownmessages)) continue;
			    $header = imap_headerinfo($this->mbox, $msg_number);
			    $fromaddr = $header->from[0]->mailbox . "@" . $header->from[0]->host;
			    if (!in_array($fromaddr, $this->allowedusers)) continue;			    
			    return $uid;
		    }
		    
	    } 
	 }
	 
	 function getMessageSubject($msg_number)
	 {
		if ($this->mbox)
	    {
		    $header = imap_headerinfo($this->mbox, $msg_number);
		    return $header->subject;	    
	    } 
	 }
	 
	 function getMessageBodyText($msg_number)
	 {
		if ($this->mbox)
	    {
		    $message = quoted_printable_decode(imap_fetchbody($this->mbox,$msg_number,1.1));
		    
		    if($message == '')
			{
           		 $message = quoted_printable_decode(imap_fetchbody($this->mbox,$msg_number,1));
			}
		    return $message;	    
	    } 
	 }

	 function setKnownMessage($id)
	 {
		 $this->knownmessages[] = $id;
	 }

	 
 }
 
 