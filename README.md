phpfilebrowser
==============

~because sometimes that's all you need: a single-file php file browser.

Features
===
* browse files and folders
* search files
* syntax highlighting

Please set a password!
===
Leaving phpfilebrowser.php with the default settings may open a security hole on your server. 
So please take a moment to change ADMIN_PASS from 'none' to something else, before using it. 
See on line 5:
    
	define('ADMIN_PASS', 'none');

Change to:	
	
	define('ADMIN_PASS', 'mysecretword');
	
Supplemental security measures you can take also:  
* rename the file from `phpfilebrowser` to something else; 
* protect with .htpasswd the folder it resides;

Then upload it to your server and enjoy browsing it ;) .

    