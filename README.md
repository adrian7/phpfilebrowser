PHPFileBrowser
==============

~because sometimes that's all you need: an one-file php file browser.

Features
===
* browse files and folders
* search files and folders
* tree-view
* password-protected (optionally but recommended)
* syntax highlighting for source files (php, py, rb, html, js, vb)
* download current file/folder (folder as zip, not recursive)
* move files and folders
* file upload (ajax uploader with drag-and-drop support)
* contextual (right-click) menu with download, cut and delete actions

Screenshots
===
<a href="https://raw.github.com/adrian7/phpfilebrowser/master/screenshots/phpfilebrowser-screen-1.png" target="_blank"><img src="https://raw.github.com/adrian7/phpfilebrowser/master/screenshots/phpfilebrowser-screen-1.png" width="50" /></a>
<a href="https://raw.github.com/adrian7/phpfilebrowser/master/screenshots/phpfilebrowser-screen-2.png" target="_blank"><img src="https://raw.github.com/adrian7/phpfilebrowser/master/screenshots/phpfilebrowser-screen-2.png" width="50" /></a>
<a href="https://raw.github.com/adrian7/phpfilebrowser/master/screenshots/phpfilebrowser-screen-3.png" target="_blank"><img src="https://raw.github.com/adrian7/phpfilebrowser/master/screenshots/phpfilebrowser-screen-3.png" width="50" /></a>
<a href="https://raw.github.com/adrian7/phpfilebrowser/master/screenshots/phpfilebrowser-screen-4.png" target="_blank"><img src="https://raw.github.com/adrian7/phpfilebrowser/master/screenshots/phpfilebrowser-screen-4.png" width="50" /></a>
<a href="https://raw.github.com/adrian7/phpfilebrowser/master/screenshots/phpfilebrowser-screen-5.png" target="_blank"><img src="https://raw.github.com/adrian7/phpfilebrowser/master/screenshots/phpfilebrowser-screen-5.png" width="50" /></a>



Tested on
===
* Chrome 20.01
* Firefox 15.0.1


Please set a password!
===
Leaving phpfilebrowser.php with the default settings may open a security hole on your server. 
So please take a moment to change ADMIN_PASS from 'none' to something else, before using it. 
See on line 5:
    
	define('ADMIN_PASS', 'none');

Change to:	
	
	define('ADMIN_PASS', 'mysecretpassword');
	
Supplemental security measures you can take also:  

- rename the file from `phpfilebrowser` to something else; 
- protect with .htpasswd the folder it resides;

Then upload it to your server and enjoy browsing it **;)**

    