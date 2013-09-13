<?php
  
chdir("aaaaaaa/OsuBeatmaps");
@mkdir("/tmp/osu");
exec("rm -rf /tmp/osu/*");

mysql_connect("localhost", "osu", "");
mysql_select_db("osu");

$files = glob("{./*.osz,./*.zip}",GLOB_BRACE);
foreach($files as $file){
	$ext = pathinfo($file, PATHINFO_EXTENSION);
	$filename = pathinfo($file, PATHINFO_FILENAME);
		
	$res = mysql_query("select * from beatmaps where filename='".mysql_real_escape_string($filename)."'") or die(mysql_error());
	if(mysql_num_rows($res) == 0){
		echo "Extracting $file... ";
		exec('cp '.escapeshellarg($file).' '.escapeshellarg('/tmp/osu/'.$filename.'.zip'));
		exec('unzip -j '.escapeshellarg('/tmp/osu/'.$filename.'.zip').' *.osu -d /tmp/osu');
		$osufiles = array();
		$files_osu = glob("/tmp/osu/*.osu");
		if(count($files_osu) > 0){
			echo "found ".count($files_osu)." *.osu files... ";
			foreach($files_osu as $file_osu){
				$osufiles[]=file_get_contents($file_osu);
			}
			mysql_query("insert into beatmaps set filename='".mysql_real_escape_string($filename)."', osufiles='".mysql_real_escape_string(serialize($osufiles))."', zip=".(file_exists($filename.'.zip') ? 1 : 0)) or die(mysql_error());
			echo "Added.";
		} else {
			echo "No osu files?";
		}
		echo "\n";
		exec("rm -rf /tmp/osu/*");
	}
	
	mysql_query("update beatmaps set size='".intval(filesize($filename.'.osz'))."' where filename='".mysql_real_escape_string($filename)."'") or die(mysql_error());
	
	if($ext == 'zip'){
		exec('mv -n '.escapeshellarg($file).' '.escapeshellarg($filename.'.osz'));
		unlink($file);
	}        
}

$res = mysql_query("select * from beatmaps") or die(mysql_error());
while($row = mysql_fetch_assoc($res)){
	$osufiles = unserialize($row['osufiles']);
	if(preg_match('#Title:(.*)#', $osufiles[0], $matches))
		mysql_query("update beatmaps set title='".mysql_real_escape_string(trim($matches[1]))."' where id={$row['id']}") or die(mysql_error());
	if(preg_match('#Artist:(.*)#', $osufiles[0], $matches))
		mysql_query("update beatmaps set artist='".mysql_real_escape_string(trim($matches[1]))."' where id={$row['id']}") or die(mysql_error());
	if(preg_match('#Creator:(.*)#', $osufiles[0], $matches))
		mysql_query("update beatmaps set creator='".mysql_real_escape_string(trim($matches[1]))."' where id={$row['id']}") or die(mysql_error());
}

mysql_query("update beatmaps set osu_id = substring_index(filename, ' ', 1)");


