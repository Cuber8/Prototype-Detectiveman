# Mr.Detective
This desktop application has task to search computer information, the application design look like cmd with black and white color it will have a textbox acting as debug display under there 3buttons (investigate, interrogate, Refresh).
each button funtion:

investigate: it task is to scan and display the device the app operate within, 
-OS(Operating System), name/Type/version/Bloatware level/Driver support and OEM update policies all information about it or if there a dual booting,
-CPU(processor), Brand/type/name all information about it, either there one or multiple,
-GPU(Graphic Card), Brand/type/name/ram all information about, either there one or multiple,
-ram, total capacity/Type&speed/name,
-Storage(internal only), Type(NVMe M.2 SSD, SATA SSD, HDD)/Capacity/read Speed/How many of them,
-Display, Panel type(IPS / VA / TN / OLED)/Resolution/refresh rate,
-Battery calculate how long battery last, see how long it take for percentage to drop by one, then calculate how long for 100% to drop in hours, if battery is charging say can't because battery is plugged,
-number of application existing check the amount of application within the computer,

interrogate: when entering an exe file that user find issue it debug and display the error it facing if there no exe file selected it search for any malware either using key word or alternative methode.

Refresh: clear the debug display

add anything neccessary use (php, html, css, js, c#)
make multi-file like index.php, style.css, script.js ...etc

there could be multiple CPU, GPU, storage....etc
don't give it preset information make it read it from device
split task to multiple files
