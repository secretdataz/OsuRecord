osu!record
==========
In this repo is most of the backend of osu!record and my auto-updating osu! mirror.

Please excuse the quality of the PHP code and the use of the legacy MySQL API - everything you see here is a massive hack job.

Prerequisites
---
* A folder containing all ranked+approved osu! beatmaps in .osz format. If you are struggling to source this from the available mirrors, I can provide a temporary FTP on request.
* osu! already running successfully under Wine
* Autohotkey installed in your wineroot
* A cron job every few hours of osupeppy.php then osuindex.php
* Go through all the code and fix paths/display numbers/users/etc. to suit your system
* If using a resolution other than 640x480, bsb.jpg and mainmenu5.jpg need scaled/recreated accordingly and all coordinates in the AHK files will likely need to be recalculated
* Appropriate sudo config for the hundreds of sudo scripts
* This modified copy of GLC: https://github.com/darkimmortal/glc/tree/pulseaudio
* ffmpeg, imagemagick, SoX, etc.
* Arch Linux strongly recommended

Notes
---
* Even if you increase osu!'s resolution, keep some degree of upscaling in. No idea why, but it massively improves the quality of the final youtube video. 


GL & HF!