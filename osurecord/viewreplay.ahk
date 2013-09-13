#singleinstance force
WinWait osu!,,25

if ErrorLevel
	Exit 1
	
Sleep 3000
WinActivate osu!
sleep 1000
Send {esc}
Sleep 1000
MouseMove 507, 390

click down
sleep 100
click up
sleep 100


click down
sleep 100
click up
sleep 100

click down
sleep 100
click up
sleep 100

click down
sleep 100
click up
sleep 100

click down
sleep 100
click up
sleep 100

sleep 1000
WinSetTitle osu!
sleep 5000
WinActivate
send {tab}