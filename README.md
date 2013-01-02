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
[Screenshot 1](http://postimage.org/image/j5hvd1yub/) [Screenshot 2](http://postimage.org/image/5qheoks11/) [Screenshot 3](http://postimage.org/image/5qcoboydh/) [Screenshot 4](http://postimage.org/image/qf3fvrder/) [Screenshot 5](http://postimage.org/image/aimhn4t1h/)



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

    