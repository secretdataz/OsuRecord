<?php

define("DEVELOPER_KEY", "your youtube dev key");

require_once 'Zend/Loader.php';
Zend_Loader::loadClass('Zend_Gdata_YouTube');
Zend_Loader::loadClass('Zend_Gdata_AuthSub');
Zend_Loader::loadClass('Zend_Gdata_App');
Zend_Loader::loadClass('Zend_Gdata_App_MediaFileSource');
Zend_Loader::loadClass('Zend_Gdata_App_HttpException');
Zend_Loader::loadClass('Zend_Gdata_App_Exception');
Zend_Loader::loadClass('Zend_Http_Client_Exception');
Zend_Loader::loadClass('Zend_Http_Client');

set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__));

define("HERE", dirname(__FILE__));

$mem = new Memcache();
$mem->connect("localhost");
$mem->set("recording", true);


function PsExec($commandJob){
	$command = $commandJob.' > /dev/null 2>&1 & echo $!';
	exec($command, $op);
	$pid = (int)$op[0];

	if($pid != "")
		return $pid;

	return false;
} 

function coolsleep($seconds){
	while($seconds > 0){
		printf("%05.1f", $seconds);
		usleep(1000000);
		$seconds -= 1.0;
		echo "\x08\x08\x08\x08\x08";
	}
}

function shutdown(){		
	$mem = new Memcache();
	$mem->connect("localhost");
	$mem->set("recording", false);
	echo "\n\n\n";
	chdir("/home/osu/osu");
	//if(!empty($GLOBALS['sspid']))
	//	exec("kill -9 {$GLOBALS['sspid']}");
	exec("killall -9 osu!.exe > /dev/null 2>&1");
	exec("killall -9 osu! > /dev/null 2>&1");
	exec("killall -9 osume.exe > /dev/null 2>&1");
	sleep(1);
	exec("sudo ".HERE."/kill_screenshotter > /dev/null 2>&1");
	exec("rm -rf /dolan/osurecord/glc/*.glc");
	exec("rm -rf /tmp/osurecord/glc/*.glc");
	exec("rm -rf /tmp/osurecord/replay.osk");
	exec("rm -rf /home/osu/osu/Skins/BESTSKINNU/*");
	exec("sudo ".HERE."/blank_out_screenshot");
	chmod("/tmp/osurecord", 0777);
}


declare(ticks = 1);

pcntl_signal(SIGINT, "signal_handler");

function signal_handler($signal) {
	if($signal == SIGINT){
		echo "\n\n\n\033[01;31m ************ MANUAL INTERRUPT - JOB TERMINATED ************ \n\n\n\033[0m";
		shutdown();
		exit;
	}
}


register_shutdown_function('shutdown');

define("DISPLAY", "8");

// THE MORE DISPLAY THE MORE BETTER
putenv("DISPLAY=:".DISPLAY);
exec("export DISPLAY=:".DISPLAY);
shell_exec("export DISPLAY=:".DISPLAY);

chdir("/home/osu/osu");

// MORE LAZINESS FRIENDLY THAN PDO, DEAL WITH IT
mysql_connect("localhost", "osu", "");
mysql_select_db("osu");

$youtubeSessionId = intval($argv[1]);

exec("killall -9 osu!.exe > /dev/null 2>&1");
exec("killall -9 osu! > /dev/null 2>&1");
exec("sudo ".HERE."/kill_screenshotter > /dev/null 2>&1");

exec("rm -rf /home/osu/osu/Skins/BESTSKINNU/*");
	
echo "\n\n\n\033[01;31m Reading replay file... \n\033[0m";

//
$replayContents = file_get_contents("/tmp/osurecord/replay.osr");
//hex_dump(substr($replayContents, 0, 300));
//[a-f0-9]+
if(!preg_match('#\x01\x0B\x20(.*?)\x0B.(.*?)\x0B#s', $replayContents, $matches)){
	die("Invalid map hash or username aka stop uploading shite that isn't really a .osr file");
}
$mapHash = $matches[1];
$username = $matches[2];

echo "Got map hash '{$mapHash}' and player username '{$username}'";


echo "\n\n\n\033[01;31m Querying osu! api for map info... \033[0m";


$sorryPeppy = file_get_contents("http://osu.ppy.sh/web/osu-osz2-getscores.php?s=0&c={$mapHash}&us=Darkimmortal&ha=aaaaaaaaaaaaaaaaaaaaaaaaaa");

$debug = explode("\n", $sorryPeppy);
var_dump($debug[0]);

preg_match('#\d+\|(?:false|true)\|(\d+)\|(\d+)\|#', $sorryPeppy, $matches);
$osu_id = intval($matches[2]);
$difficulty_id = intval($matches[1]);


if(empty($osu_id))
	$osu_id = -1;

echo "ID $osu_id... Difficulty $difficulty_id... ";
	
$res = mysql_query("select * from beatmaps where osu_id >= $osu_id") or die(mysql_error());
if(mysql_num_rows($res) == 0) die("Map newer than what osu!record has in its library, please try an older map.");


$res = mysql_query("select * from beatmaps where osu_id = $osu_id") or die(mysql_error());
if(mysql_num_rows($res) != 1){
	echo "Not found, trying artist/title match... ";
	preg_match('#\[bold:\d+,size:\d+\](.*?)\|(.*?)$#m', $sorryPeppy, $matches);

	$res = mysql_query("select * from beatmaps where artist = '".mysql_real_escape_string($matches[1])."' and title = '".mysql_real_escape_string($matches[2])."'") or die(mysql_error());
}

mysql_query("insert into record_log set osu_id = {$osu_id}, difficulty_id = {$difficulty_id}, youtube_session='".mysql_real_escape_string($mem->get("youtubeSessionToken"))."', maphash='".mysql_real_escape_string($mapHash)."', osu_username='".mysql_real_escape_string($username)."', date=NOW(), ip='".mysql_real_escape_string($mem->get("recordingIp"))."', osr_content='".mysql_real_escape_string($replayContents)."'") or die(mysql_error());
$GLOBALS['log_id'] = mysql_insert_id(); 

//$res = mysql_query("select * from beatmaps where osu_id = $osu_id") or die(mysql_error());
if(mysql_num_rows($res) == 0) die("Not found. (map too new or not ranked?) (if you're sure it's ranked, it was probably never included in a pack, some maps weren't for some reason)");
if(mysql_num_rows($res) > 1) die("Matches more than one. (map too old or too new or not ranked?)");

$beatmap = mysql_fetch_assoc($res);
$beatmap['osufiles'] = unserialize($beatmap['osufiles']);

$mem->set("recordingInfo", array(
	"username" => $username, 
	"id" => $osu_id,
	"difficulty" => $difficulty_id,
	"title" => $beatmap['title'],
	"artist" => $beatmap['artist']
));

mysql_query("update record_log set beatmap_id = {$beatmap['id']} where id={$GLOBALS['log_id']}") or die(mysql_error());

$waitMs = 0;
/*

*/

echo "Got {$beatmap['filename']}";




echo "\n\n\n\033[01;31mExtracting replay length... \033[0m";


$sorryAgainPeppy = file_get_contents("http://osu.ppy.sh/b/{$difficulty_id}");
if(!preg_match('#<td width=0%>Total Time:<\/td><td class="colour">([\d:]+)</td>#', $sorryAgainPeppy, $matches))
	die("Online method failed.");
	
$timeSplit = explode(":", $matches[1]);
$waitMs = $timeSplit[1] + 5;
$waitMs += $timeSplit[0] * 60;	
$waitMs *= 1000;


echo "Got {$waitMs}ms";
if(preg_match('#(....)\x0b..[\d]+\|#s', $replayContents, $matches)){
	$modFlags = unpack('Vmods', $matches[1]);
	if(!empty($modFlags) && !empty($modFlags['mods'])){
		if($modFlags['mods'] & 0x40){
			$waitMs /= 1.45;
			echo " *DOUBLETIME/NIGHTCORE detected* adjusted to {$waitMs}ms";
		}
	}
} else {
	echo "FAILED to detect mod flags";
	sleep(5);
}

if($waitMs > 600000){
	die("\n\n\nToo long a map, you can go waste your own cpu cycles.");
}

if(file_exists("/tmp/osurecord/replay.osk")){
	echo "\n\n\n\033[01;31mInstalling skin... \033[0m";
	
	exec("unzip -oj /tmp/osurecord/replay.osk -d /home/osu/osu/Skins/BESTSKINNU");
	exec("rm -rf /home/osu/osu/Skins/BESTSKINNU/skin.ini");
	exec("rm -rf /home/osu/osu/Skins/BESTSKINNU/SKIN.ini");
	exec("rm -rf /home/osu/osu/Skins/BESTSKINNU/SKIN.INI");
	exec("rm -rf /home/osu/osu/Skins/BESTSKINNU/skin.INI");
	exec("rm -rf /home/osu/osu/Skins/BESTSKINNU/Skin.ini");
	echo "ok";
}

echo "\n\n\n\033[01;31mChecking if updated beatmap is available... \n\033[0m";

passthru("sudo ".HERE."/wget_map ".escapeshellarg($beatmap['osu_id']));
shell_exec("rm -f beatmap-get.php*");
shell_exec("rm -f *.1");
shell_exec("rm -f *.2");


echo "\n\n\n\033[01;31mInstalling beatmap into osu!... \033[0m (wait about 15 seconds)";

exec("rm -rf /home/osu/osu/Songs/");
exec("rm -rf /home/osu/osu/Replays/");
mkdir("/home/osu/osu/Songs");
mkdir("/home/osu/osu/Replays");

copy("/dolan/dl/OsuBeatmaps/".$beatmap['filename'].".osz", "/home/osu/osu/Songs/thesong.osz");

exec("sudo ".HERE."/kill_screenshotter > /dev/null 2>&1");
PsExec('wine "C:\Program Files\Autohotkey\Autohotkey.exe" '.HERE.'/titlefix.ahk > /dev/null 2>&1');
PsExec('sudo '.HERE.'/screenshotter');
PsExec("sudo ".HERE."/start_osume");


echo "\n\n\n\033[01;31mWaiting for any osu updates to install... \033[0m";
coolsleep(15);


echo "\n\n\n\033[01;31mInstalling beatmap into osu!... \033[0m (wait about 15 seconds)";

$time = microtime(true);
exec('wine "C:\Program Files\Autohotkey\Autohotkey.exe" '.HERE.'/pressplay.ahk > /dev/null 2>&1', $derp, $errorlevel);
if($errorlevel != 0){
	die("\nError: osu! failed to start after waiting 10 seconds. Over 9000 possible reasons for this, best bet is to just try again.");
}


if(microtime(true) - $time < 1){
	die("\nThings happened too quickly - assuming something is broken and bailing out.\n");
}



echo "\n\n\n\033[01;31mCopying replay into osu!... \033[0m";

copy("/tmp/osurecord/replay.osr", "/home/osu/osu/Replays/replay.osr");
exec("rm -rf /tmp/osurecord");
exec("rm -rf /dolan/osurecord");
exec("mkdir -p /tmp/osurecord/glc");
exec("mkdir -p /dolan/osurecord/glc");


sleep(1);
exec("killall -9 osu!.exe > /dev/null 2>&1");
exec("killall -9 osu! > /dev/null 2>&1");
exec("killall -9 osume.exe > /dev/null 2>&1");
sleep(1);


echo "\n\n\n\033[01;31mStarting osu! and beginning replay playback... \033[0m ";
//--lock-fps -f 30
chdir("/home/osu/osu");

PsExec("sudo ".HERE."/record_osu_alt");

usleep(500 * 1000);

//workaround dumb bug, force osu to get to replay screen
PsExec("sudo ".HERE."/start_osu Replays/replay.osr");

usleep(500 * 1000);
PsExec("sudo ".HERE."/start_osu Replays/replay.osr");
usleep(500 * 1000);
PsExec("sudo ".HERE."/start_osu Replays/replay.osr");
usleep(500 * 1000);
PsExec("sudo ".HERE."/start_osu Replays/replay.osr");
usleep(500 * 1000);
PsExec("sudo ".HERE."/start_osu Replays/replay.osr");
usleep(500 * 1000);
PsExec("sudo ".HERE."/start_osu Replays/replay.osr");
usleep(500 * 1000);
PsExec("sudo ".HERE."/start_osu Replays/replay.osr");
usleep(500 * 1000);
PsExec("sudo ".HERE."/start_osu Replays/replay.osr");

PsExec('wine "C:\Program Files\Autohotkey\Autohotkey.exe" '.HERE.'/hide_updater.ahk > /dev/null 2>&1');

exec('wine "C:\Program Files\Autohotkey\Autohotkey.exe" '.HERE.'/viewreplay.ahk > /dev/null 2>&1', $derp, $errorlevel);
if($errorlevel != 0){
	die("\nError: osu! failed to start after waiting 25 seconds. Over 9000 possible reasons for this, best bet is to just try again.");
}


// raise io priority
PsExec('sudo '.HERE.'/rtio');

$waitSeconds = $waitMs / 1000 + 10;
echo "\n\n\n\033[01;31mNow recording, wait {$waitSeconds} seconds... \033[0m";
$waitSeconds -= 10;
echo "Performing fail check in 10 seconds... ";
coolsleep(10);
PsExec('wine "C:\Program Files\Autohotkey\Autohotkey.exe" '.HERE.'/hide_updater.ahk > /dev/null 2>&1');

exec('sudo '.HERE.'/check_screen');
$compare = shell_exec('compare -metric MAE /tmp/osurecord/check.jpg '.HERE.'/mainmenu5.jpg null: 2>&1');
if(!preg_match('#\(([\d.]+)\)#', $compare, $matches))
	die("\n\n\n\n\nosu!record has detected that osu! has closed, crashed or is display a dialog box. Probably needs an update.");
$diffIndex = $matches[1];
echo "difference index {$diffIndex} (min 0.28)... ";
if($diffIndex <= 0.28){	
	echo "Check failed!";
	echo "\n\n\n\n\n osu!record has detected that osu! is stuck at the main menu screen. Recording has been aborted to save resources. Usually this is caused by an outdated map on the server - please do not try constantly uploading the same replay, it's not going to work.";
	exit;
}	

$compare = shell_exec('compare -metric MAE /tmp/osurecord/check.jpg '.HERE.'/bsb.jpg null: 2>&1');
if(!preg_match('#\(([\d.]+)\)#', $compare, $matches))
	die("\n\n\n\n\nosu!record has detected that osu! has closed, crashed or is display a dialog box. Probably needs an update. [2]");
$diffIndex = $matches[1];
echo "2nd index {$diffIndex} (min 0.05)... ";
if($diffIndex <= 0.05){	
	echo "Check failed!";
	echo "\n\n\n\n\n osu! has stuck on 'checking for new files' so recording has been aborted to save resources. I'm not aware of any way to fix this issue.";
	exit;
}	


echo "Check passed, continuing recording... ";
coolsleep($waitSeconds);
exec("killall -9 osu!.exe > /dev/null 2>&1");
exec("killall -9 osu! > /dev/null 2>&1");
exec("sudo ".HERE."/kill_screenshotter > /dev/null 2>&1");
exec("sudo ".HERE."/blank_out_screenshot");


echo "\n\n\n\033[01;31mExtracting 32-bit float audio... \033[0m";
$biggestGlc = trim(shell_exec("ls -1S /dolan/osurecord/glc/ | head -1"));
exec("glc-play /dolan/osurecord/glc/{$biggestGlc} -a 1 -o - | sox --show-progress -c 2 -r 48000 -e floating-point -b 32 -t raw - -t wav /tmp/osurecord/audio.wav gain -1 ");

//pad 0.016
//glc-play /tmp/osurecord/glc/4217.glc -y 1 -o - | ffmpeg -i - -sameq -y video.mp4    

/*
echo "\n\n\n\033[01;31mDetecting black padding... \033[0m";
$blackOut = shell_exec("glc-play /dolan/osurecord/glc/{$biggestGlc} -y 1 -o - | ffmpeg -i - -vf blackdetect -f null - 2>&1 | grep black");
//$blackOut = shell_exec("ffmpeg -i /tmp/osurecord/video.avi -vf blackdetect -f null /dev/null 2>&1 | grep black");
preg_match('#black_start:[\d.]+ black_end:([\d.]+)#', $blackOut, $matches);
$blackSeconds = $matches[1];
echo "Found {$blackSeconds} seconds.";
*/

$blackSeconds = 0;
//sleep(1);

echo "\n\n\n\033[01;31mEncoding x264 video + mp3 audio... \033[0m";
//exec("glc-play /tmp/osurecord/glc/{$biggestGlc} -y 1 -o - | mencoder -demuxer y4m - -audiofile /tmp/osurecord/audio.wav -mc 0 -oac faac -ovc x264 -x264encopts qp=18:pass=1 -of avi -vf flip -o /tmp/osurecord/video.avi");
//exec("glc-play /tmp/osurecord/glc/{$biggestGlc} -y 1 -o - | mencoder -demuxer y4m - -audiofile /tmp/osurecord/audio.wav -mc 0 -oac faac -ovc x264 -x264encopts qp=18:pass=2 -of avi -vf flip -o /tmp/osurecord/video.avi");

if(empty($blackSeconds))
	$blackSeconds = 0;
	
// keep stats to end and hope we aren't chopping off too much gameplay...
$blackSeconds += 7.5;


// add 3 for new start over and over again 7 times
//$blackSeconds += 3.0;
// 3 is too much
$blackSeconds += 2.0;


//exec("glc-play /tmp/osurecord/glc/{$biggestGlc} -y 1 -o - | ffmpeg -y -i - -i /tmp/osurecord/audio.wav -vf vflip -ss {$blackSeconds} -r 30000/1001 -b 2M -bt 4M -vcodec libx264 -pass 1 -vpre fastfirstpass -an /tmp/osurecord/output.mp4");
//exec("glc-play /tmp/osurecord/glc/{$biggestGlc} -y 1 -o - | ffmpeg -y -i - -i /tmp/osurecord/audio.wav -vf vflip -ss {$blackSeconds} -r 30000/1001 -b 2M -bt 4M -vcodec libx264 -pass 2 -vpre hq -acodec libfaac -ac 2 -ar 48000 -ab 192k output.mp4");

//,scale=1440:1080
sleep(1);
exec("glc-play /dolan/osurecord/glc/{$biggestGlc} -y 1 -o - | ffmpeg -y -i - -i /tmp/osurecord/audio.wav -ss {$blackSeconds} -s 1280x960 -vcodec libx264 -preset fast -crf 14 -acodec libmp3lame -q:a 2 -r 30 -threads 3 /tmp/osurecord/output.mp4");


echo "\n\n\n\n\n\n\n\n\n\n\n\033[01;31mNow uploading to your Youtube account... \033[0m(this may take a while)\n\n";



// so much code stolen from google example code, hnnng
$yt = new Zend_Gdata_YouTube(Zend_Gdata_AuthSub::getHttpClient($mem->get("youtubeSessionToken")), 'osu!record', null, DEVELOPER_KEY);
$myVideoEntry = new Zend_Gdata_YouTube_VideoEntry();
$filesource = $yt->newMediaFileSource("/tmp/osurecord/output.mp4");
$filesource->setContentType('video/mp4'); 
$filesource->setSlug('output.mp4');
$myVideoEntry->setMediaSource($filesource);
$myVideoEntry->setVideoTitle(/*htmlentities*/("[osu!] {$beatmap['artist']} - {$beatmap['title']} ({$username})"));
$myVideoEntry->setVideoDescription("Played by: http://osu.ppy.sh/u/".rawurlencode($username)."\nVideo created with osu!record: http://osurecord.weeaboo.com/\nBeatmap: http://osu.ppy.sh/b/{$difficulty_id}");
$myVideoEntry->setVideoCategory('Games');
$myVideoEntry->SetVideoTags('osu!record,osurecord,osu!,osu');

$uploadUrl = 'http://uploads.gdata.youtube.com/feeds/users/default/uploads';


try {
	$newEntry = $yt->insertEntry($myVideoEntry,
								 $uploadUrl,
								 'Zend_Gdata_YouTube_VideoEntry');
} catch (Zend_Gdata_App_HttpException $httpException) {
	echo "Youtube upload failed with reason: " . $httpException->getRawResponseBody()."\n\n\n";
	echo "If the above error is NoLinkedYouTubeAccount, you haven't actually signed up for youtube and are just using a plain google account, so you can't upload videos.";
	exit;
} catch (Zend_Gdata_App_Exception $e) {
	echo "Youtube upload failed with reason: " . $e->getMessage()."\n\n\n";
	echo "If the above error is NoLinkedYouTubeAccount, you haven't actually signed up for youtube and are just using a plain google account, so you can't upload videos.";
	exit;
}

$id = $newEntry->getVideoId();
$url = "http://www.youtube.com/watch?v={$id}";

$mem->set("lastVideoUrl", $url);

mysql_query("update record_log set youtube_url = '".mysql_real_escape_string($url)."', success=1, finished=NOW() where id={$GLOBALS['log_id']}") or die(mysql_error());
//
//echo "\nConverting audio to aac and producing final .avi file... ";
//exec("mencoder /tmp/osurecord/video.avi -audiofile /tmp/osurecord/audio.wav -oac faac -ovc copy -of avi -mc 0 -ss {$blackSeconds} -o /tmp/osurecord/video_final.avi");


echo "\n\n\n\n\n\n\n\n\033[01;31mHoly shit it's done!\033[0m\n\nVideo URL: {$url}";