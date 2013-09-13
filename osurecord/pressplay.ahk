#singleinstance force
SendMode Event

;Sleep 3000
;WinActivate osu!
;Send {esc}

WinWait osu!,,10

if ErrorLevel
	Exit 1
	
WinActivate osu!
Sleep 6000
;WinActivate osu!
Send {esc}
Sleep 1000
MouseMove 270,131
Click down
sleep 50
click up
Sleep 800
Click 430, 130
Sleep 800
Click 430, 130
Sleep 800
Click 430, 165
Sleep 800
Click 430, 130
Sleep 800
Click 430, 165
Sleep 4000
WinClose osu!
Sleep 500
