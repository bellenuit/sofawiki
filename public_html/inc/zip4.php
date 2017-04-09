<?php

/*

Zip file creation class
makes zip files on the fly...

use the functions add_dir() and add_file() to build the zip file;
see example code below

by Eric Mueller
http://www.themepark.com

v1.1 9-20-01
  - added comments to example

v1.0 2-5-01

initial version with:
  - class appearance
  - add_file() and file() methods
  - gzcompress() output hacking
by Denis O.Philippov, webmaster@atlant.ru, http://www.atlant.ru

*/

// official ZIP file format: http://www. // pkware.com/appnote.txt

class zipfile  
{  

    var $datasec = array(); // array to store compressed data
    var $ctrl_dir = array(); // central directory   
    var $eof_ctrl_dir = "\x50\x4b\x05\x06\x00\x00\x00\x00"; //end of Central directory record
    var $old_offset = 0;

    function add_dir($name, $time = 0)   

    // adds "directory" to archive - do this before putting any files in directory!
    // $name - name of directory... like this: "path/"
    // ...then you can add files using add_file with names like "path/file.txt"
    {  
        $name = str_replace("\\", "/", $name);  

        $fr = "\x50\x4b\x03\x04";
        $fr .= "\x0a\x00";    // ver needed to extract
        $fr .= "\x00\x00";    // gen purpose bit flag
        $fr .= "\x00\x00";    // compression method
        //$fr .= "\x00\x00\x00\x00"; // last mod time and date
		$fr .= packTimeDate($time);
		
        $fr .= pack("V",0); // crc32
        $fr .= pack("V",0); //compressed filesize
        $fr .= pack("V",0); //uncompressed filesize
        $fr .= pack("v", strlen($name) ); //length of pathname
        $fr .= pack("v", 0 ); //extra field length
        $fr .= $name;  
        // end of "local file header" segment

        // no "file data" segment for path

        // "data descriptor" segment (optional but necessary if archive is not served as file)
        /*
        $fr .= pack("V",$crc); //crc32
        $fr .= pack("V",$c_len); //compressed filesize
        $fr .= pack("V",$unc_len); //uncompressed filesize
		*/
        // add this entry to array
        $this -> datasec[] = $fr;

        $new_offset = strlen(implode("", $this->datasec));

        // ext. file attributes mirrors MS-DOS directory attr byte, detailed
        // at http://support.microsoft.com/support/kb/articles/Q125/0/19.asp

        // now add to central record
        $cdrec = "\x50\x4b\x01\x02";
        $cdrec .="\x00\x00";    // version made by
        $cdrec .="\x0a\x00";    // version needed to extract
        $cdrec .="\x00\x00";    // gen purpose bit flag
        $cdrec .="\x00\x00";    // compression method
       // $cdrec .="\x00\x00\x00\x00"; // last mod time & date
        $cdrec .= packTimeDate($time);
        $cdrec .= pack("V",0); // crc32
        $cdrec .= pack("V",0); //compressed filesize
        $cdrec .= pack("V",0); //uncompressed filesize
        $cdrec .= pack("v", strlen($name) ); //length of filename
        $cdrec .= pack("v", 0 ); //extra field length   
        $cdrec .= pack("v", 0 ); //file comment length
        $cdrec .= pack("v", 0 ); //disk number start
        $cdrec .= pack("v", 0 ); //internal file attributes
        $ext = "\x00\x00\x10\x00";
        $ext = "\xff\xff\xff\xff";  
        $cdrec .= pack("V", 16 ); //external file attributes  - 'directory' bit set

        $cdrec .= pack("V", $this -> old_offset ); //relative offset of local header
        $this -> old_offset = $new_offset;

        $cdrec .= $name;  
        // optional extra field, file comment goes here
        // save to array
        $this -> ctrl_dir[] = $cdrec;  

         
    }


    function add_file($data, $name, $time = 0)   

    // adds "file" to archive   
    // $data - file contents
    // $name - name of file in archive. Add path if your want

    {  
        $name = str_replace("\\", "/", $name);  
        //$name = str_replace("\\", "\\\\", $name);

        $fr = "\x50\x4b\x03\x04";
        $fr .= "\x14\x00";    // ver needed to extract
        $fr .= "\x00\x00";    // gen purpose bit flag
        $fr .= "\x08\x00";    // compression method
        // $fr .= "\x00\x00\x00\x00"; // last mod time and date
        $fr .= packTimeDate($time);

        $unc_len = strlen($data);  
        $crc = crc32($data);  
        $zdata = gzcompress($data);  
        $zdata = substr( substr($zdata, 0, strlen($zdata) - 4), 2); // fix crc bug
        $c_len = strlen($zdata);  
        $fr .= pack("V",$crc); // crc32
        $fr .= pack("V",$c_len); //compressed filesize
        $fr .= pack("V",$unc_len); //uncompressed filesize
        $fr .= pack("v", strlen($name) ); //length of filename
        $fr .= pack("v", 0 ); //extra field length
        $fr .= $name;  
        // end of "local file header" segment
         
        // "file data" segment
        $fr .= $zdata;  

        // "data descriptor" segment (optional but necessary if archive is not served as file)
        $fr .= pack("V",$crc); //crc32
        $fr .= pack("V",$c_len); //compressed filesize
        $fr .= pack("V",$unc_len); //uncompressed filesize

        // add this entry to array
        $this -> datasec[] = $fr;

        $new_offset = strlen(implode("", $this->datasec));

        // now add to central directory record
        $cdrec = "\x50\x4b\x01\x02";
        $cdrec .="\x00\x00";    // version made by
        $cdrec .="\x14\x00";    // version needed to extract
        $cdrec .="\x00\x00";    // gen purpose bit flag
        $cdrec .="\x08\x00";    // compression method
        //$cdrec .="\x00\x00\x00\x00"; // last mod time & date
        $cdrec .= packTimeDate($time);
        $cdrec .= pack("V",$crc); // crc32
        $cdrec .= pack("V",$c_len); //compressed filesize
        $cdrec .= pack("V",$unc_len); //uncompressed filesize
        $cdrec .= pack("v", strlen($name) ); //length of filename
        $cdrec .= pack("v", 0 ); //extra field length   
        $cdrec .= pack("v", 0 ); //file comment length
        $cdrec .= pack("v", 0 ); //disk number start
        $cdrec .= pack("v", 0 ); //internal file attributes
        $cdrec .= pack("V", 32 ); //external file attributes - 'archive' bit set

        $cdrec .= pack("V", $this -> old_offset ); //relative offset of local header
//      &n // bsp; echo "old offset is ".$this->old_offset.", new offset is $new_offset<br>";
        $this -> old_offset = $new_offset;

        $cdrec .= $name;  
        // optional extra field, file comment goes here
        // save to central directory
        $this -> ctrl_dir[] = $cdrec;  
    }

    function file() { // dump out file   
        $data = implode("", $this -> datasec);  
        $ctrldir = implode("", $this -> ctrl_dir);  

        return   
            $data.  
            $ctrldir.  
            $this -> eof_ctrl_dir.  
            pack("v", sizeof($this -> ctrl_dir)).     // total # of entries "on this disk"
            pack("v", sizeof($this -> ctrl_dir)).     // total # of entries overall
            pack("V", strlen($ctrldir)).             // size of central dir
            pack("V", strlen($data)).                 // offset to start of central dir
            "\x00\x00";                             // .zip file comment length
    }
}  


// added code from php.net Marcin Szychowski

// $ts - standard UNIX timestamp, as returned by mktime()
function packTimeDate($ts){
 // MS-DOS can store dates ranging from 1980-01-01 up to 2107-12-31
    $year=date('Y', $ts);
    if(($year<1980) || ($year>2107)) return "\x00\x00\x00\x00";
    else return packTime($ts).packDate($ts);
}

/** From Wikipedia:
 * 15-11 Hours (0-23)
 * 10-5  Minutes (0-59)
 *  4-0  Seconds/2 (0-29)
 **/
function packTime($ts){
    $sec=round((('1'.date('s', $ts))-100)/2);
    $min=('1'.date('i', $ts))-100;
    $hour=date('G', $ts);

    $dosTime=($hour<<11)+($min<<5)+$sec;

    $m[0]=$dosTime%256;
    $m[1]=(($dosTime-$m[0])/256)%256;
    return sprintf('%c%c', $m[0], $m[1]);
}

/** From Wikipedia:
 * 15-9 Year (0 = 1980, 127 = 2107)
 *  8-5 Month (1 = January, 12 = December)
 *  4-0 Day (1 - 31)
 **/
function packDate($ts){
    $year=date('Y', $ts)-1980;
    $day=date('j', $ts);
    $month=date('n', $ts);

    $dosDate=($year<<9)+($month<<5)+$day;

    $m[0]=$dosDate%256;
    $m[1]=(($dosDate-$m[0])/256)%256;
    return sprintf('%c%c', $m[0], $m[1]);
}





/*
$zipfile = new zipfile();  

// add the subdirectory ... important!
$zipfile -> add_dir("dir/");

// add the binary data stored in the string 'filedata'
$filedata = "(read your file into $filedata)";  
$zipfile -> add_file($filedata, "dir/file.txt");  

// the next three lines force an immediate download of the zip file:
header("Content-type: application/octet-stream");  
header("Content-disposition: attachment; filename=test.zip");  
echo $zipfile -> file();  


// OR instead of doing that, you can write out the file to the loca disk like this:
$filename = "output.zip";
$fd = fopen ($filename, "wb");
$out = fwrite ($fd, $zipfile -> file());
fclose ($fd);

// then offer it to the user to download:
<a href="output.zip">Click here to download the new zip file.</a>
*/





?>