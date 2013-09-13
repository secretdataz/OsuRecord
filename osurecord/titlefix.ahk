#singleinstance force

loop
{
	winwait osu!
	loop
	{
		winsettitle osu!
		ifwinnotexist
			break
		sleep 100	
	}
}