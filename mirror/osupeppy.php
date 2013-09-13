<?php
  

mysql_connect("localhost", "osu", "");
mysql_select_db("osu");

$res = mysql_query("select * from vars");
$row = mysql_fetch_assoc($res);

$json = json_decode(file_get_contents("http://osu.ppy.sh/api/get_beatmaps.php?since=".$row['last_beatmap_check']."&k=aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa"), true);
$json = array_reverse($json);

chdir("/dolan/dl/OsuBeatmaps");
$done = array();
$latestGot = 0;
foreach($json as $beatmap){
	echo "\n\nBeatmap {$beatmap['beatmapset_id']}... ";
	$res = mysql_query("select * from beatmaps where osu_id=".intval($beatmap['beatmapset_id']));
	if(mysql_num_rows($res) == 0 && !array_key_exists($beatmap['beatmapset_id'], $done)){
					
		$done[$beatmap['beatmapset_id']] = true;
		
		if(file_exists("{$beatmap['beatmapset_id']} {$beatmap['artist']} - {$beatmap['title']}.osz")){
			$latestGot = strtotime($beatmap['approved_date']);
			continue;
		}
		
		passthru("wget -nc --content-disposition ".escapeshellarg("http://osu.ppy.sh/d/{$beatmap['beatmapset_id']}?u=Darkimmortal&h=aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa"));
		
		if(!file_exists("beatmap-get.php?u=Darkimmortal&h=aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa&s={$beatmap['beatmapset_id']}") && strtotime($beatmap['approved_date']) > $latestGot)
			$latestGot = strtotime($beatmap['approved_date']);
		
	}
	

}
shell_exec("rm -f beatmap-get.php*");
shell_exec("rm -f *.1");
shell_exec("rm -f *.2");
shell_exec("rm -f *u=Darkimmortal*");
if($latestGot == 0)
	$latestGot = time();
mysql_query("update vars set last_beatmap_check = '".date("Y-m-d", $latestGot)."'");
