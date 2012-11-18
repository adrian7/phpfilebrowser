<?php 
/**
 * PHPFileBrowser is a single-file php script that allows you to browse files, as you would do in a graphical file manager, on your web server. Please see <a href="https://github.com/adrian7/phpfilebrowser/blob/master/README.md">README</a>.
 * @author Adrian7 (http://adrian.silimon.eu/)
 * @version 0.7.3
 * @package PHPFileBrowser
 */

//--- Settings, plz don't ignore or you might create a security hole on your server ---//

//Here you tell the script to limit access only to users aware of this password. 'none' means no password.
define('ADMIN_PASS', 'none'); 

//Here you can put a custom format to display dates
define('DATE_FORMAT', 'Y-m-d H:i:s');

//Here you can put a virtual dir, (ex.: /~homedir) to properly display files under a virtual directory
define('SBVDIR', '');

//--- Settings, hope you did some changes... ---//

error_reporting(E_ALL ^ E_NOTICE);

session_start();

if ( isset($_POST['yourpass']) and  ( $_POST['yourpass'] == ADMIN_PASS ) ) $_SESSION['xfbrowserauth'] = 1;

define('SBFILE', basename(__FILE__));

define('SPATH', dirname(__FILE__));
define('SBURL', str_replace("/" . SBFILE, '', str_replace(( '?' . $_SERVER['QUERY_STRING']), "", $_SERVER['REQUEST_URI'])));

//force download files with these extensions, ignore if they may be displayed
$downloadableExts = array();

//an array of supported playable extensions by browsers
$playableExts = array('jpg', 'jpeg', 'gif', 'png', 'apng', 'svg', 'ico', 'wav', 'ogg', 'mp3', 'mp4', 'ogv', 'webm', 'flv', 'swf');

//an array of extensions you might wanna play using a flash swf player. See also flashCfg var below
$flashPlayableExts = array('flv', 'avi', 'mpeg');

//an array of files for which the source (contents) of the wile will be displayed
$sourceExts = array('txt', 'php', 'js', 'php5', 'htm', 'html', 'xml', 'css', 'md', 'ini', '.htaccess', 'java', 'rb', 'py', 'sql');

//globals
$isFile 		= FALSE;
$isHome		= FALSE;
$isDir			= FALSE;
$isSearch	= FALSE;
$isNotFound=FALSE;

//message types
define('ERROR_MSG'	, 'error');
define('WARN_MSG'	, 'warn');
define('INFO_MSG'	, 'info');

/**
 * Outputs a &lt;p&gt; message tag
 * @param string $message
 * @param string $msg_type optional additional element class
 */
function showmsg($message, $msg_type="warn"){
	?><p class="msg <?php echo $msg_type; ?>"><?php echo $message; ?></p><?php 
}

/**
 * Formats a file size to a human-readable format
 * @param number $size
 * @return string
 */
function fmt_filesize( $size ){
	if ($size > 1024) return ( round(($size / 1024), 2) . " KB" );
	if ($size > 1048576) return ( round(($size / 1048576), 2) . " MB" );
	if ($size > 1073741824) return ( round(($size / 1073741824), 2) . " GB" );
	
	return ($size . ' bytes');
}

/**
 * Formats a timestamp to a human-readable format
 * @param int $timestamp
 * @param string $format (optional), default is Y-m-d
 * @return string
 */
function fmt_date($timestamp, $format="Y-m-d"){
	return date($format, $timestamp);
}

/**
 * Generates an array of directories and files
 * @param string $path absolute path co the directory
 * @param string $subdirectories (optional)
 */
function dirtree($path, $subdirectories=TRUE){

	$path = rtrim($path, "/");
	
	if ( is_file($path) ) 	  $path = dirname($path);
	if ( !is_array($tree) ) $tree  = array();
	
	$k=0;
	
	if ( $handle = @opendir($path) ){

		while (FALSE !== ( $file = readdir($handle) ) ){ 
			
			if ( ( $file == '.' ) or ( $file == '..') ) continue;

			$node = ( $path . "/" . $file );
			
			$tree[$k] = array('name'=>basename($file), 'path'=>$node);
			
			if ( is_dir($node) and $subdirectories )
				$tree[$k]['nodes'] = dirtree($node, TRUE);
			
			$k++;
		}
		
	}

	return $tree;

}


function get_file_url($file){
	
	$proto = "http";
	
	$self 	 = str_replace(SPATH, "", $_SERVER['PHP_SELF']);
	$self 	 = str_replace(SBVDIR, "", $self);
	
	$selfdir = trim(str_replace(SBFILE, "", $self), "/");
	
	$file = str_replace(SPATH, "", $file);
	$file = ("/" . $selfdir . $file);
	
	if ( isset($_SERVER['HTTPS']) and ($_SERVER['HTTPS'] and $_SERVER['HTTPS'] != "off") ) $proto = "https";
	
	return ($proto . '://' . $_SERVER['SERVER_NAME'] . $file);
	
}

function breadcrumbs($rootname="/"){

	$dirname = urldecode($_GET['view']);

	if( $dirname == "." || $dirname == "/" ){
		$path= SPATH;
		$bdir= "";
	}else{
		$path = ( SPATH . str_replace(SBVDIR, "", $dirname) );
		$bdir= str_replace(SBURL, "", $dirname);
	}

		$chunks = explode("/", trim($dirname, "/")); ?>
				
		<div class="breadcrumbs">
				&nbsp;
				<a class="entry home" href="<?php echo SBFILE; ?>?view=/"><?php echo $rootname; ?></a>
				
				<?php 
				
				if ( $_GET['s'] ): ?>
				<a class="sep">&nbsp;</a><a class="entry" href="<?php echo SBFILE; ?>?s=<?php echo stripslashes( urlencode($_GET['s']) );?>"> Search results </a> 
				<?php endif;
				
				if ( count($chunks) ) foreach ($chunks as $c){ if (empty($c)) continue;
					$burl	 .= ("/" . $c); ?>
					
					<a class="sep">&nbsp;</a><a class="entry" href="<?php echo SBFILE; ?>?view=<?php echo urlencode($burl);?>"> <?php echo $c; ?></a> 
	
				<?php 				
				}
				
				?>
			<br class="clear" />
		</div>
			
	<?php 
}
	
//--- display directory function ---//
function display_dir(){ 

		if(!class_exists('RecursiveDirectoryIterator')) die("<h2>RecursiveDirectoryIterator class not available!</h2>");
		
		$dirname = urldecode($_GET['view']);
		
		if( $dirname == "." || $dirname == "/" ){
			$path= SPATH;
			$bdir= "";
		}else{
			$path = ( SPATH . str_replace(SBVDIR, "", $dirname) ); 
			$bdir= str_replace(SBURL, "", $dirname);
		} 
		
		$k=0;
		
		if ($handle = opendir($path) ){
 			while (false !== ($file = readdir($handle)) ){
			   	if ($file != "." && $file != ".."){

					$k++;

					$abspath = (rtrim($path) . "/" . $file);

					$csscls = ""; $size	  = "";$action = "view";
					
					if( is_dir($abspath) )   $csscls = "dir ";
					if( is_file($abspath) ){ 
						$csscls = "file "; $csscls.= get_file_ext($file); $size = (fmt_filesize( filesize($abspath) ) . " / "); 
						if ( is_downloadable_file($abspath) ) $action = "download";
					}
					
					if( empty($csscls) ) $csscls = "symlink ";

					$lastmod = fmt_date(@filemtime($abspath), DATE_FORMAT);
					
			    	$fileurl = ( rtrim($bdir, "/") .  "/" . str_replace(SPATH, "", $file) ); ?>
			    	
			    	<a class="<?php echo $csscls; ?>" href="<?php echo SBFILE; ?>?<?php echo $action; ?>=<?php echo urlencode($fileurl);?>"> 
			    		<span class="icon">&nbsp;</span><?php echo $file; ?><br/><span class="details"><?php echo $size; ?> <?php echo $lastmod; ?> </span>
			    	</a>
			    	
					<?php 
			   	}
			 }
			  
			closedir($handle);
		}
	
		
}
//--- display directory function ---//

function is_flash_playable($file){
	global $flashPlayableExts; return in_array( get_file_ext($file), $flashPlayableExts, TRUE );
}

function is_playable_file($file){
	global $playableExts; return in_array( get_file_ext($file), $playableExts, TRUE );
}

function is_source_file($file){
	global $sourceExts; return in_array( get_file_ext($file), $sourceExts, TRUE );
}

function is_downloadable_file($file){

	global $downloadableExts;
	
	if (in_array(get_file_ext($file), $downloadableExts, TRUE)) return TRUE;
	
	return !is_playable_file($file) and !is_source_file($file);
}

function is_video($file){
	$ext 		  	= get_file_ext($file);
	$supported = array('mp4', 'ogv', 'webm');
	
	return in_array($ext, $supported, TRUE);
}

function is_image($file){
	$ext 		  	= get_file_ext($file);
	$supported = array('jpg', 'jpeg', 'png', 'gif');
	
	return in_array($ext, $supported, TRUE);
}

function is_sound($file){
	$ext 		  	= get_file_ext($file);
	$supported = array('mp3', 'wav', 'ogg');

	return in_array($ext, $supported, TRUE);
}

function display_file($path=NULL){

	if ( empty($path) ) $path = ( SPATH . str_replace(SBVDIR, "", urldecode($_GET['view'])) );
	
	if( is_playable_file($path) ): /*play the image/embed/video/audio*/ 
		$fileurl = ( SBURL . urldecode($_GET['view']) );
		$type   = FALSE;	
		
		if ( is_image($path) ): $type='image'; ?>
			<img class="image" src="<?php echo $fileurl; ?>" align="middle" />
		<?php endif;
		
		if ( is_video($path) ):  $type='video'; ?>
			<video controls class="video">
				<source src="<?php echo $fileurl; ?>" type="video/<?php echo get_file_ext($path) == "ogv" ? "ogg" : get_file_ext($path) ; ?>" />
				Sorry, your browser does not supports this video format: <em><?php echo get_file_ext($path); ?></em>.
				<a href="<?php echo $fileurl; ?>">Download it here.</a>
			</video>
		<?php endif;
		
		if ( is_sound($path) ): $type='sound'; ?>
			<audio controls  class="audio">
			  	<source src="<?php echo $fileurl; ?>" type="audio/<?php echo get_file_ext($path); ?>" />
			 	Sorry, your browser does not supports this video format: <em><?php echo get_file_ext($path); ?></em>. 
			 	<a href="<?php echo $fileurl; ?>">Download it here.</a>
			</audio>
		<?php endif;
		
		if ( is_flash_playable($path) ): $type='flash'; ?>
			<embed src="http://www.gdd.ro/gdd/flvplayer/gddflvplayer.swf" flashvars="?&autoplay=false&sound=70&buffer=2&vdo=<?php echo urlencode(get_file_url($path)); ?>" width="100%" height="328" allowFullScreen="true" quality="best" wmode="transparent" allowScriptAccess="always"  pluginspage="http://www.macromedia.com/go/getflashplayer"  type="application/x-shockwave-flash"></embed>
		<?php endif;
		
		if ( empty($type) ): /*unknown format but still playable*/ ?>
			<iframe class="unknown" src="<?php echo $fileurl; ?>"></iframe>
		<?php endif;
		
	endif;
	
	if ( is_source_file($path) ): /*show the source*/ ?>
		<pre><code class="<?php echo get_source_highlight($path);?>"><?php echo htmlentities(file_get_contents($path)); ?></code></pre>
		<script>
		 	hljs.tabReplace = '    ';
		  	hljs.initHighlightingOnLoad();
		  </script>
	<?php endif;
}

function display_404(){
	?>
	
	<div class="not-found">
		<h3><em>404 - File not found</em></h3>
	</div>
	
	<?php 
}

//--- display file function ---//
function display(){

	global $isFile, $isDir, $isHome, $isSearch, $isNotFound;

	$path = ( SPATH . str_replace(SBVDIR, "", urldecode($_GET['view'])) ); 
	
	if ( $path == SPATH ) $isHome = TRUE;
	
	if( is_dir($path) ){ 	/*display directory listing*/ 
		$isDir = TRUE; display_dir();
	}

	 if ( is_file($path) ){ /*display single file */
	 	$isFile=TRUE; display_file();
	}
	
	if (!$isFile and !$isDir) { /*display 404*/
		$isNotFound = TRUE; display_404();
	}
}
//---  display file function ---//
	
function get_file_ext($file){
	$path = basename($file);
	return strtolower(substr($file, strrpos($file, ".")+1));
}
	
function is_raw_visible_file($file){

	$ext = get_file_ext($file);
		
	//--- images ---//
	if( $ext == 'jpg' or $ext == 'jpeg' or $ext == 'gif' or $ext == 'png' ) return TRUE;
		
	//--- adobe files ---//
	if ( $ext == 'pdf' or $ext == 'swf' or $ext =='flv' ) return TRUE;
		
	return FALSE;

}

function is_editable_file( $file ){
	$ext = get_file_ext($file);
	
	$editables = array('txt', 'text', 'md', 'php', 'js', 'css', 'html', 'phps', 'vb', 'xml', 'cfg', 'ini', 'htaccess', 'vbs', 'java', 'jsp', 'sql');
	
	return in_array($ext, $editables, TRUE);
	
}

function embed_file($file_url){
	
	$ext = get_file_ext($file);
	
	if ($ext == 'jpg' or $ext == 'jpeg' or $ext == 'gif' or $ext == 'png') //is an image ?>
		<img src="<?php $file_url;?>" align="middle" border="0" width="100%" />
	<?php 
	
	if ( $ext == 'pdf' or $ext == 'swf' or $ext =='flv' ) //is an iframe playable file ?>
	
	<?php 
}
	
function get_source_highlight($file){
		
	$ext = get_file_ext($file);	

		
		switch ($ext){
			case 'js'  : return 'javascript';
			case 'py'	: return 'python';
			case 'rb'	: return 'ruby';
			
			//--- don't highlight these files ---//
			case 'md': return 'no-highlight';
			case 'txt'	: return 'no-highlight';
			case 'pot': return 'no-highlight';
			case 'po'	: return 'no-highlight';
			
			default:return $ext;
		}
}
	
	
//--- search function ---//
function search(){ 

	global $isSearch; $isSearch = TRUE;
	
	if(!class_exists('RecursiveDirectoryIterator')) die("<h2>RecursiveDirectoryIterator class not available!</h2>"); $term = stripslashes( urldecode($_GET['s'] ) ); 
		
		$Tree = new RecursiveDirectoryIterator(SPATH);

		$count=0;
		
		foreach(new RecursiveIteratorIterator($Tree) as $dir=>$path) {

			//echo $path . '<br/>';//TODO search for directories too...			

			if ( strpos($path, "/..") ) $path = str_replace("/..", "", $path);

			$filename = basename($path);
			
			if( strpos(strtolower(trim($filename)), $term) === FALSE ) continue; else{ 
				
					$fileurl = ( str_replace(SPATH, "", $path) ); 
				
					$csscls = ""; $action = "view";

					if( is_dir($path) )   $csscls = "dir ";
					if( is_file($path) ){ $csscls = "file "; $csscls.= get_file_ext($path); if ( is_downloadable_file($path) ) $action = "download"; }
					
					if( empty($csscls) ) $csscls = "symlink "; ?>
			    	
			    	<a class="<?php echo $csscls; ?>" href="<?php echo SBFILE; ?>?<?php echo $action; ?>=<?php echo urlencode($fileurl);?>"> 
			    		<span class="icon">&nbsp;</span><?php echo basename($path); ?><br/>
			    		<span class="details"><?php echo fmt_filesize( @filesize($path) ) ?> / <?php echo fmt_date(@filemtime($path), DATE_FORMAT); ?> </span>
			    	</a>
			    	
					<?php 
			}

		}
	
}
//--- search function ---//


function fileicons_css(){ ?>
<style type="text/css">
<!--
/* file extensions icons */
.file .icon, .unknown .icon, li.file{
	background-image:url('http://cdn1.iconfinder.com/data/icons/crystalproject/64x64/mimetypes/txt.png');
}

a.png .icon, a.jpg .icon, a.jpeg .icon, a.gif .icon, li.file.png, li.file.gif, li.file.jpeg, li.file.jpg{
	background-image:url('http://cdn1.iconfinder.com/data/icons/fs-icons-ubuntu-by-franksouza-/128/image-png.png');
}

a.php .icon, a.phps .icon, a.php5 .icon, li.file.php, li.file.phps, li.file..php5{
	background-image:url('http://cdn1.iconfinder.com/data/icons/fs-icons-ubuntu-by-franksouza-/128/gnome-mime-application-x-php.png');
}

a.js .icon, li.file.js{
	background-image:url('http://cdn1.iconfinder.com/data/icons/fs-icons-ubuntu-by-franksouza-/128/application-x-javascript.png');
}

a.zip .icon, a.gz .icon, a.rar .icon, a.tar .icon, li.file.zip, li.file.gz, li.file.rar, li.file.tar{
	background-image:url('http://cdn1.iconfinder.com/data/icons/oxygen/64x64/apps/utilities-file-archiver.png');
}

a.htm .icon, a.html .icon, a.shtml .icon, li.file.htm, li.file.html, li.file.shtml{
	background-image:url('http://cdn1.iconfinder.com/data/icons/fs-icons-ubuntu-by-franksouza-/128/gnome-mime-text-html.png');
}

.psd .icon, li.file.psd{
	background-image:url('http://cdn1.iconfinder.com/data/icons/fs-icons-ubuntu-by-franksouza-/128/image-x-psd.png');
}

.swf .icon, li.file.swf{
	background-image:url('http://cdn1.iconfinder.com/data/icons/Futurosoft%20Icons%200.5.2/128x128/mimetypes/swf.png');
}

.avi .icon, .mov .icon, .mp4 .icon, .divx .icon, .wmv .icon, .flv .icon, .ogv .icon, .webm .icon, li.file.avi, li.file.mov, li.file.mp4, li.file.divx, li.file.wmv, li.file.flv, li.file.ogv, li.file.webm{
	background-image:url('http://cdn1.iconfinder.com/data/icons/fs-icons-ubuntu-by-franksouza-/128/video-webm.png');
}

.mp3 .icon, .pcm .icon, .midi .icon, .ogg .icon, .au .icon, li.file.mp3, li.file.pcm, li.file.midi, li.file.ogg, li.file.au{
	background-image:url('http://cdn1.iconfinder.com/data/icons/fs-icons-ubuntu-by-franksouza-/128/audio-x-vorbis+ogg.png');
}
/* file extensions icons */
-->
</style>
<?php 
}

function dirtree_css($class="dirtree"){ ?>
<style type="text/css">
	<!--
	
	* {
		padding: 0;
		margin: 0;
	}
	
	html, body{
		height: 100%;
		font-size: 1em;
		font-family: Arial, Helvetica, sans-serif;
	}
	
	a{
		color:#333;
		text-decoration:none;
	}
	
	 ul.<?php echo $class;?>, ul.<?php echo $class;?> ul {
    	 list-style-type: none;
    	 list-style-type: none;
     	margin: .2em auto;
     	padding: 0;
  	 }
   
   	ul.<?php echo $class;?> ul {
     	margin-left: 10px;
  	}
  	
  	ul.<?php echo $class;?>{
		margin-left:2%;
	}
	
	ul.<?php echo $class;?>.sub{
		margin-left:0;
	}

   	ul.<?php echo $class;?> li {
    	margin: 0;
     	padding-left:18px;
     	background-repeat:no-repeat;
     	background-position:top left;
     	background-size:16px;
   	}
   	
   	ul.<?php echo $class;?> li a:hover{
   		color: #000;
   		font-weight:bold;
   	}
   	
   	ul.<?php echo $class;?> li.selected > a{
   		font-weight:bold;
   	}
  
  	li.dir {
  		background-image:url('http://cdn1.iconfinder.com/data/icons/fatcow/16x16_0440/folder.png');
  	}
  	
  	li.file {
  		background-image:url('http://cdn1.iconfinder.com/data/icons/oxygen/16x16/mimetypes/document.png');
  	}
  	
  	li.closed ul li{ display:none; }
	-->
</style>
<?php 
}

function dirtree_html($tree, $class="dirtree", $selected=FALSE, $k=0){ 

	if ($k == 0):/*called for the first time*/ ?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8" />
<?php dirtree_css($class); ?>
<?php fileicons_css(); ?>
</head>
<body>
	<?php endif; if ( is_array($tree) and count($tree) ): $k = $k*2048; ?>
	<ul class="<?php echo $class; ?>">
  		<?php foreach ($tree as $node): $liclass = 'file'; $k++;
  		
	  		if ( ( $liclass == 'file' ) and is_downloadable_file($node['path']) )
	  			$nodeurl = ( SBFILE . '?download=' . urlencode( str_replace(SPATH, "", $node['path']) ) );
	  		else
	  			$nodeurl = ( SBFILE . '?view=' . urlencode( str_replace(SPATH, "", $node['path']) ) );
  		
  				if ( is_dir($node['path']) ) 
  					$liclass='dir'; 
  				else 
  					$liclass.= (" " .get_file_ext($node['path']));
  				
  				if ($selected == $node['path'] ) $liclass.=" selected"; 
  				
  				if ( strpos($selected, $node['path']) === FALSE ) $liclass.=" closed";
  		?>
  		<li class="<?php echo $liclass; ?>" id="node-<?php echo $k; ?>">
  			<a href="#node-<?php echo $k; ?>" rel="<?php echo $nodeurl; ?>"> <?php echo stripslashes($node['name']); ?> </a>
  			<?php if ( isset($node['nodes']) and count($node['nodes']) ) dirtree_html($node['nodes'], "sub " . $class, $selected, $k); ?>
  		</li>
  		<?php endforeach;?>
	</ul>
	
<?php endif; if ( $k < 2048 ): /*calles for the first time*/ ?>	
	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
	<script type="text/javascript">
	<!--
	$(document).ready(function(){

		$('li.file a').click(function(e){
			e.preventDefault();
			window.parent.location.href=$(this).attr('rel');
		});
		
		$('li.dir a').click(function(e){//tree dir click action

			e.preventDefault();
			
			var parentLI = $(this).parent('li').get(0);
			var parentID = ( '#'+$(parentLI).attr('id') );
			
			if ( $(parentID).hasClass('closed') ){
				$(this).removeClass('closed');
				$(parentID + ' > ul > li.closed').slideToggle();
			}
			else{
				$(this).addClass('closed');
				$(parentID + ' > ul > li').slideToggle();
			}

			return false;
		});
	});
	//-->
	</script>
</body>
</html>
	<?php endif;
}

//--- $_GET actions ---//
if ( isset($_GET['download']) ) { //file and folder download action

	if ( (ADMIN_PASS != 'none') and empty($_SESSION['xfbrowserauth']) ) die("...");

	$path	 = stripslashes( urldecode($_GET['download']) );
	$path = trim($path, "/");

	if ( is_file($path) ) {

		$download = basename($path);

		//$filetype = get_file_mime_type($path);
		$size	  	  = @filesize($path);

		//header("Content-type: $filetype"); //oups! we don't know the file type
		header("Content-length: $size");
		header("Content-disposition: attachment; filename=$download");

		if ( @readfile($path) ) exit();
		else
			showmsg("Could not read the file $path", MSG_ERROR);

	}

	if ( is_dir($path) and class_exists('ZipArchive') ){

		$files 	= array();
		$dirpath	= ( SPATH . "/" . $path );

		if ( $handle = opendir($dirpath) ){
			while (FALSE !== ($file = readdir($handle)) ){ //TODO recurse into subdirectories
				$filepath = ( $dirpath . "/" . $file ); if ( is_file($filepath) ) $files[] = $filepath;
			}
		}

		if ( count( $files ) ){

			$zfile = tempnam(SPATH, "zip");
			$zip 	= new ZipArchive();

			if ( @$zip->open($zfile, ZipArchive::OVERWRITE) ){

				foreach ($files as $f) @$zip->addFile($f, basename($f));

				$zip->close();

				$size 		  = filesize($zfile);
				$filename = ( basename($path) . ".zip" );

				header('Content-Type: application/zip');
				header('Content-Length: ' . $size );
				header('Content-Disposition: attachment; filename="' . $filename .  '"');

				if ( @readfile($zfile) ){
					@unlink($zfile); exit();
				}
				else
					showmsg("<strong>Error:</strong> Could not read the file $zfile", MSG_ERROR);

			}
			else showmsg("<strong>Error:</strong> Could not create temporary file!", ERROR_MSG);
		}
		else showmsg("<strong>Error:</strong> the directory is empty!", ERROR_MSG);
	}

	showmsg("<strong>Error:</strong> Could not download file $path", ERROR_MSG);

}


if ( isset($_GET['delete']) ) { //file delete action (folders can't be deleted)

	if ( (ADMIN_PASS != 'none') and empty($_SESSION['xfbrowserauth']) ) die("...");

	$path	 = stripslashes( urldecode($_GET['delete']) );
	$path = trim($path, "/");

	if ( is_file($path) )
		if ( @unlink(SPATH . "/" . $path) ); else showmsg("<strong>Error:</storng> Could not delete the file $path", ERROR_MSG);
	else
		showmsg("<strong>Warning:</storng> Could not delete a directory", WARN_MSG);

	//redirect to parent folder

	$redirect = ( SBFILE . "?view=" . urlencode( "/" . rtrim( str_replace(basename($path), "", $path) , "/")));

	header("Location: $redirect");
}


if ( isset($_GET['tree']) ) { //outputs the folder tree 

	$cachelife = 7200;
	$cachefile = "cache.dirtree";
	$cacheexp = FALSE;

	if ( (ADMIN_PASS != 'none') and empty($_SESSION['xfbrowserauth']) ) die("...");

	$path	 = stripslashes( urldecode($_GET['path']) );
	$path = trim($path, "/");
	$path = (SPATH . "/" .$path );

	$opath = $path;

	if ( is_file($path) ) $path = dirname($path);

	$parent = SPATH;

	if ( file_exists($cachefile) and ( ( time() - filemtime($cachefile) ) < $cachelife ) ){
		$tree = @file_get_contents($cachefile);
		$tree = @unserialize($tree);
	}
	else{
		$tree = dirtree($parent, TRUE);
		@unlink($cachefile);
		$cacheexp = TRUE;
	}

	if ( count($tree) ) dirtree_html($tree, "dirtree", $opath);

	if ($cacheexp ) {
		@file_put_contents($cachefile, @serialize($tree));
	}

	exit();

}

if ( isset($_GET['edit']) ) { //output the file edit window //TODO

	if ( (ADMIN_PASS != 'none') and empty($_SESSION['xfbrowserauth']) ) die("...");

	$path	 = stripslashes( urldecode($_GET['edit']) );
	$path = trim($path, "/");
	$path = ( SPATH . "/" . $path );

	if ( is_file($path) )
		die("Editing = " . $path); //TODO
	else
		display_404();
}

if ( isset($_GET['blank']) ) { //outputs a blank page
	die();
}

if ( isset($_GET['cookie']) ) { //sets the value of a cookie; value &v=21, default 1; expire &e=x days, default 1 day

	$life 		= isset($_GET['e']) ? intval($_GET['e']) : 1;
	$expire 	= time() + ( 86400 * $life );
	$value	= isset($_GET['v']) ? trim($_GET['v']) : 1;

	if ( @setcookie( trim($_GET['cookie']), $value, $expire ) )
		echo 1;
	else 
		echo 0;
	
	die();
}

if ( isset($_GET['loading']) ){ //outputs a loading gif image

	$width = isset($_GET['w']) ? intval($_GET['w']) : 128;

	?>
	<style type="text/css">
	<!--
		img {width:30%; height:auto; margin-top:25%;}
	-->
	</style>
	<p align="center">
		<img width="<?php echo $width; ?>" border="0" src="data:image/gif;base64,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" />
	</p>
	<?php 
	
	die();
}

//--- actions ---//

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8" />
<meta name="author" content="Adrian7 (http://adrian.silimon.eu/)" />
<link href="http://fonts.googleapis.com/css?family=Source+Sans+Pro:400,600&subset=latin,latin-ext" rel="stylesheet" type="text/css">
<link rel="stylesheet" href="http://yandex.st/highlightjs/7.0/styles/default.min.css">
<link rel="stylesheet" href="http://ajax.aspnetcdn.com/ajax/jquery.ui/1.9.0/themes/smoothness/jquery-ui.css">
<script src="http://yandex.st/highlightjs/7.0/highlight.min.js"></script>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
 <script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.8.23/jquery-ui.min.js"></script>
<style type="text/css">
* {
	padding: 0;
	margin: 0;
}

html, body{
	height: 100%;
}

* html .wrap {
	height: 100%;
}

.wrap{
	float:left;
	margin:0px auto;
	width:100%;
	min-height:100%;
	height: 100%;
}

body{
	font-size:1em;
	font-family:'Source Sans Pro', Arial, Helvetica, sans-serif;
	margin:0px;
	padding:0px;
	
	background-color: #59B0F7;
	
	background-image: linear-gradient(top, #59B0F7 7%, #2DA0ED 54%, #178AD6 77%, #0470C2 95%);
	background-image: -o-linear-gradient(top, #59B0F7 7%, #2DA0ED 54%, #178AD6 77%, #0470C2 95%);
	background-image: -moz-linear-gradient(top, #59B0F7 7%, #2DA0ED 54%, #178AD6 77%, #0470C2 95%);
	background-image: -webkit-linear-gradient(top, #59B0F7 7%, #2DA0ED 54%, #178AD6 77%, #0470C2 95%);
	background-image: -ms-linear-gradient(top, #59B0F7 7%, #2DA0ED 54%, #178AD6 77%, #0470C2 95%);
	
	background-image: -webkit-gradient(
		linear,
		left top,
		left bottom,
		color-stop(0.07, #59B0F7),
		color-stop(0.54, #2DA0ED),
		color-stop(0.77, #178AD6),
		color-stop(0.95, #0470C2)
	);
	
	background-repeat:no-repeat;
}

.info, .warn, .error{
	border: 1px solid;
	border-top:none;
    margin: 10px 0px;
    padding:10px;
    background-repeat: repeat;
	border-radius: .3em;
	display:block;
	position: absolute;
	margin-top:0px;
	top:0px;
	left:10%;
	width:80%;
	border-top-left-radius: 0px;
	border-top-right-radius:0px;
	
	box-shadow: 2px 2px 8px #999;
}

.info{
    /*color: #00529B;*/
    background-color: #BDE5F8;
}

.warn {
    /*color: #9F6000;*/
    background-color: #FEEFB3;
}

.error {
    /*color: #D8000C;*/
    background-color: #FFBABA;
}

a, img{
	border:0;
	outline:0;
	outline:none;
	text-decoration:none;
}

button, input{
	font-size:100%;	
	font-family:'Source Sans Pro', Arial, Helvetica, sans-serif;
}

.clear{
	clear: both;
}

a{
	color:#333;
	text-decoration:none;
}

a:hover{
	color:blue;
}

#main{
	margin:0px auto;
	float:left;
	height:100%;
	width:100%;
}

#navbar{
	width:100%;
	height:6%;
	min-height:41px;
	margin:0;
	padding: 0;
	
  border-bottom: 1px solid rgba(0,0,0,0.8);

   box-shadow: 0 2px 6px rgba(0,0,0,0.5), inset 0 1px rgba(255,255,255,0.3), inset 0 10px rgba(255,255,255,0.2), inset 0 10px 20px rgba(255,255,255,0.25), inset 0 -15px 30px rgba(0,0,0,0.3);
   -o-box-shadow: 0 2px 6px rgba(0,0,0,0.5), inset 0 1px rgba(255,255,255,0.3), inset 0 10px rgba(255,255,255,0.2), inset 0 10px 20px rgba(255,255,255,0.25), inset 0 -15px 30px rgba(0,0,0,0.3);
   -webkit-box-shadow: 0 2px 6px rgba(0,0,0,0.5), inset 0 1px rgba(255,255,255,0.3), inset 0 10px rgba(255,255,255,0.2), inset 0 10px 20px rgba(255,255,255,0.25), inset 0 -15px 30px rgba(0,0,0,0.3);
   -moz-box-shadow: 0 2px 6px rgba(0,0,0,0.5), inset 0 1px rgba(255,255,255,0.3), inset 0 10px rgba(255,255,255,0.2), inset 0 10px 20px rgba(255,255,255,0.25), inset 0 -15px 30px rgba(0,0,0,0.3);
}

#navbar .breadcrumbs{
	background: #FAFAFA;
	margin:0.52em;
	margin-left:0em;
	padding: 2px;
	width:70%;
	float:left;
	height:auto;
	font-size: 1em;
	box-shadow: inset 1px 1px 3px #888;
	border-radius: 2px;
}

.breadcrumbs a{
	background: none;
	margin:0;
	padding:0;
}

.breadcrumbs a.entry{
	padding: 1px 3px;
}

.breadcrumbs a.entry:hover{
	background: rgba(237,237,237,.5);
	color:#000;
}

.breadcrumbs .sep{
	background-image:url('http://cdn1.iconfinder.com/data/icons/picol/icons/arrow_sans_right_32.png');
	background-repeat:no-repeat;
	background-size:90%;
	background-position:center center;
	width:1em;
	height:100%;
	display:inline-block;
	margin:0em;
	opacity:0.7;
}

#navbar .search{
	margin:0.52em;
	margin-left:0em;
	padding:0;
	width:8%;
	float:left;
	height:auto;
	font-size: 1em;
}

input[type=text], input[type=password], textarea, select, input[type=file], a.button{
	display:inline-block;
	background: #FAFAFA;
	box-shadow: inset 1px 1px 3px #888;
	border:none;
	border-radius: 2px;
	padding:2px 4px;
	font-size: 100%;
	height:1.3em;
	font-style:italic;
}

button, input[type=button], input[type=submit], a.button{
	display: inline-block;
	white-space: nowrap;
	background-color: #ccc;
	background-image: -webkit-gradient(linear, left top, left bottom, from(#eee), to(#ccc));
	background-image: -webkit-linear-gradient(top, #eee, #ccc);
	background-image: -moz-linear-gradient(top, #eee, #ccc);
	background-image: -ms-linear-gradient(top, #eee, #ccc);
	background-image: -o-linear-gradient(top, #eee, #ccc);
	background-image: linear-gradient(top, #eee, #ccc);
	filter: progid:DXImageTransform.Microsoft.gradient(startColorStr='#eeeeee', EndColorStr='#cccccc');
	border: 1px solid #777;
	padding: .2em .8em;
	margin: 0.5em;
	font: normal 100% Arial, Helvetica;
	text-decoration: none;
	color: #333;
	text-shadow: 0 1px 0 rgba(255,255,255,.8);
	-moz-border-radius: .2em;
	-webkit-border-radius: .2em;
	border-radius: .2em;
	-moz-box-shadow: 0 0 1px 1px rgba(255,255,255,.8) inset, 0 1px 0 rgba(0,0,0,.3);
	-webkit-box-shadow: 0 0 1px 1px rgba(255,255,255,.8) inset, 0 1px 0 rgba(0,0,0,.3);
	box-shadow: 0 0 1px 1px rgba(255,255,255,.8) inset, 0 1px 0 rgba(0,0,0,.3);
	
	cursor: pointer;
}

button:hover, input[type=button]:hover, input[type=submit]:hover, a.button:hover{
	background-color: #ddd;        
	background-image: -webkit-gradient(linear, left top, left bottom, from(#fafafa), to(#ddd));
	background-image: -webkit-linear-gradient(top, #fafafa, #ddd);
	background-image: -moz-linear-gradient(top, #fafafa, #ddd);
	background-image: -ms-linear-gradient(top, #fafafa, #ddd);
	background-image: -o-linear-gradient(top, #fafafa, #ddd);
	background-image: linear-gradient(top, #fafafa, #ddd);
	filter: progid:DXImageTransform.Microsoft.gradient(startColorStr='#fafafa', EndColorStr='#dddddd');
}

button:active, input[type=button]:active, input[type=submit]:active, a.button:active{
	-moz-box-shadow: 0 0 4px 2px rgba(0,0,0,.3) inset;
	-webkit-box-shadow: 0 0 4px 2px rgba(0,0,0,.3) inset;
	box-shadow: 0 0 2px 2px rgba(0,0,0,.3) inset;
	position: relative;
	top: 1px;
}

.search input{
	background-image: url('http://cdn1.iconfinder.com/data/icons/simplegrey/search.png');
	background-size:1em auto;
	background-repeat: no-repeat;
	background-position:96% center;
	font-size: 1em;
}

#navbar .quicknav{
	margin:0.52em 0.2em;
	float:left;
	width:6.5%;
}

.quicknav a{
	height:28%;
	width:28%;
	display:inline-block;
	float:left;
	background-size:auto 100%;
	background-repeat:no-repeat;
	background-position: center center;
	margin:0em 0.1em;
}

.quicknav a.next{
	background-image:url('http://cdn1.iconfinder.com/data/icons/musthave/32/Next.png');
}

.quicknav a.prev{
	background-image:url('http://cdn1.iconfinder.com/data/icons/musthave/32/Previous.png');
}

.quicknav a.home{
	background-image:url('http://cdn1.iconfinder.com/data/icons/crystalproject/128x128/apps/agt_home.png');
}

a.dir .icon{
	background-image:url('http://cdn1.iconfinder.com/data/icons/crystalproject/64x64/filesystems/folder_yellow.png');
}

a.dir:hover .icon{
	background-image:url('http://cdn1.iconfinder.com/data/icons/crystalproject/64x64/filesystems/folder_blue.png');
}

#view{
	margin:0px auto;
	background:#FBFBFB;
	margin-top:-2px;
	width:100%;
	height:93.8%;
	min-height:300px;
	clear:both;
	float:left;
	
	background-image: linear-gradient(top, #FFFFFF 100%, #FAFAFA 81%, #FBFBFB 73%, #F2F2F2 100%);
	background-image: -o-linear-gradient(top, #FFFFFF 100%, #FAFAFA 81%, #FBFBFB 73%, #F2F2F2 100%);
	background-image: -moz-linear-gradient(top, #FFFFFF 100%, #FAFAFA 81%, #FBFBFB 73%, #F2F2F2 100%);
	background-image: -webkit-linear-gradient(top, #FFFFFF 100%, #FAFAFA 81%, #FBFBFB 73%, #F2F2F2 100%);
	background-image: -ms-linear-gradient(top, #FFFFFF 100%, #FAFAFA 81%, #FBFBFB 73%, #F2F2F2 100%);
	
	background-image: -webkit-gradient(
		linear,
		left top,
		left bottom,
		color-stop(1, #FFFFFF),
		color-stop(0.81, #FAFAFA),
		color-stop(0.73, #FBFBFB),
		color-stop(1, #F2F2F2)
	);
}

#view .browser{
	float:none;
	margin:1.5em auto;
	min-width:400px;
	width:auto;
	min-height:300px;
	height:82%;
	text-align:center;
	vertical-align:middle;
}

.browser pre, .browser iframe, .browser img, .browser video, .browser audio{
	box-shadow: 2px 2px 10px #888;
	border:1px solid #CDCDCD;
	border-radius: 0.5em;
	word-wrap:break-word;
}

.browser pre{
	width:90%;
	min-height:100%;
	height:111%; /*because of the padding applied to the other stuff*/
	overflow:auto;
	float:none;
	margin-left:5%;
}

.browser pre code{
	text-align:left;
}

.browser iframe, .browser img, .browser video, .browser audio{
	padding:2em;
	width:94%;
	min-height:100%;
}

.browser img, .browser video, .browser audio{
	min-height:100px;
	width:auto;
	height:100%;
	clear:both;
}

.browser audio{
	min-width:500px;
	height:auto;
	min-height:100px;
}

.browser pre code{
	border-radius: 0.3em;
}

.browser a{
	display: inline-block;
	margin-right: 1.6em;
	margin-bottom: 1em;
	float:left;
	padding: 0.5em;
	height:auto;
	min-width: 50px;
	width: 14%;
	min-height: 50px;
	height:3%;
	text-align:left;
	border: 1px solid transparent;
	border-radius: 0.5em;
	word-wrap:break-word;
	overflow:hidden;
}

.browser a:hover{
	border-color: lightBlue;
	background: rgba(237,237,237,.5);
}

.browser a .icon{
	width:2em;
	height:2em;
	float:left;
	margin-right: 0.2em;
	margin-bottom: 0.1em;
	display:block;
	background-size:2em auto;
	background-position:center center;
	background-repeat:no-repeat;
}

.browser a .details{
	font-size:small;
	display:inline-block;
	float:none;
	width:auto;
}

/*Tree*/
.tree{
	width: auto;
	float: left;
	position: fixed;
	z-index: 999;
	width: <?php echo isset($_COOKIE['treewidth']) ? intval($_COOKIE['treewidth']) : '240'; ?>px;
	left:-<?php echo isset($_COOKIE['treewidth']) ? intval($_COOKIE['treewidth']) : '240'; ?>px;
	top:6%;
	height:94%;
	background: #FFF;
	box-shadow: 2px 2px 4px #BBB;
	border-right:2px #59b0f7 solid;
	
	background-image: linear-gradient(left , #E0E0E0 1%, #FAF7FA 38%, #FFFFFF 100%);
	background-image: -o-linear-gradient(left , #E0E0E0 1%, #FAF7FA 38%, #FFFFFF 100%);
	background-image: -moz-linear-gradient(left , #E0E0E0 1%, #FAF7FA 38%, #FFFFFF 100%);
	background-image: -webkit-linear-gradient(left , #E0E0E0 1%, #FAF7FA 38%, #FFFFFF 100%);
	background-image: -ms-linear-gradient(left , #E0E0E0 1%, #FAF7FA 38%, #FFFFFF 100%);
	
	background-image: -webkit-gradient(
		linear,
		left top,
		right top,
		color-stop(0.01, #E0E0E0),
		color-stop(0.38, #FAF7FA),
		color-stop(1, #FFFFFF)
	);
}

.tree iframe{
	width: 99.35%;
	height:100%;
	display:block;
}

.tree.visible{
	left:0px;
}

/* File menu */
.filemenu{
	position:fixed;
	z-index:999;
	left:0px;
	top:30%;
	background-color: none;
	border-left:3px #59b0f7 solid;
}

.filemenu ul{
	list-style-type:none;
	height:auto;
}

.filemenu a{
	margin:0px;
	border:1px #59b0f7 solid;
	border-top-right-radius:.3em;
	border-bottom-right-radius:.3em;
	display:block;
	width:32px;
	height:32px;
	background-color:#FAFAFA;
	background-size:90% auto;
	background-position:center center;
	background-repeat: no-repeat;
	box-shadow: 2px 2px 4px #BBB;
}

.filemenu a:hover{
	width:40px;
	height:40px;
}

.filemenu .tree-show a{
	background-image:url('http://cdn1.iconfinder.com/data/icons/fatcow/32x32/folders_explorer.png');	
}

.filemenu .open-browser a{
	background-image: url('http://cdn1.iconfinder.com/data/icons/webset/48/ie.png');
}

.filemenu .download-file a{
	background-image: url('http://cdn1.iconfinder.com/data/icons/woothemesiconset/32/arrow_down.png');
}

.filemenu .edit-file a{
	background-image: url('http://cdn1.iconfinder.com/data/icons/pixelo/32/edit.png');
}

.filemenu .upload-file a{
	background-image: url('http://cdn1.iconfinder.com/data/icons/woothemesiconset/32/blue_arrow_up.png');
}

.filemenu .delete-file a{
	background-image: url('http://cdn1.iconfinder.com/data/icons/fugue/bonus/icons-32/cross.png');
}

.not-found{
	background: url('http://cdn1.iconfinder.com/data/icons/Free-Icons-Shimmer-01-Creative-Freedom/256/warning.png') no-repeat center center;
	padding-top:280px;
	font-size:2.3em;
	font-weight:bold;
}

.fixed{
	position:fixed;
	width:auto;
	height:auto;
	padding:4px;
	background: #FFF;
}

#credits{
	bottom:0px;
	right:5px;
	font-size: smaller;
	box-shadow: -2px -2px 8px #999;
	border-top-left-radius: 3px;
	border-top-right-radius: 3px;
}

#login{
	background: #FAFAFA;
	padding:0.5em;
	width: 45%;
	margin:0px auto;
	margin-top:5%;
	
	border: .1em solid #888;
	border-radius: .2em;
	box-shadow: 4px 3px 8px #999;
}
</style>
<?php fileicons_css(); ?>
<script type="text/javascript">
var treewidth 	= 0;
var treePos		= 0;
var animspeed	= 900;
var currentPath = '<?php echo isset($_GET['view']) ? urlencode($_GET['view']) : "/" ; ?>';
var loadingUrl	   = '<?php echo SBFILE; ?>?loading=1';
var treeframeLd= false;

function emToPx(ems){
	//16px = 1em 

	return round(ems * 1/16);
}

function confirm_delete(link){
	if( confirm("Are you sure you want to delete this file?") ) 
		window.location.href = link;
}

function animate_tree(){
	if( $('.tree').hasClass('visible') ){
		$('.tree').animate({left: '-' + treewidth + 'px'}, animspeed, function(){ $(this).removeClass('visible'); });
		$('.filemenu').animate({left: 0}, animspeed);
	}else{
		$('.tree').animate({left: 0}, animspeed, function(){ 
			$(this).addClass('visible'); 

			if ( treeframeLd == false){
				$('.tree iframe').attr('src', '<?php echo SBFILE; ?>?tree=1&path=' + currentPath);
				treeframeLd = true;
			}

			$(this).resizable({handles:'e', delay:'10', resize:function(){
				treewidth = $('.tree').width();
				$('.filemenu').animate({left: treewidth + 'px'}, 10);
				$('.tree').css({top: treePos.top + 'px'});
			},
			stop: function(){
				//set up a cookie, so next time we know the preffered size
				$.get('<?php echo SBFILE; ?>', {cookie: 'treewidth', v: $('.tree').width(), e:100});
			}});
		});
		$('.filemenu').animate({left: treewidth + 'px'}, animspeed);
	}
}

$(document).ready(function(){

	treewidth = $('.tree').width();
	treePos	  = $('.tree').offset();
	
	//--- arrange all files in a nice grid ----//
	
	var maxwidth = 100;
	var maxheight= 100;
	
	$('.browser a').each(function(){
		if( $(this).height() > maxheight) maxheight = $(this).height();
		if( $(this).width() > maxwidth) maxwidth = $(this).width();
	});
	//--- slide up messages ---//
	if( $('p.msg').length > 0 ) setTimeout("$('p.msg').slideUp('fast')", 2000);
	//--- slide up messages ---//

});
</script>
</head>
<body>

<div class="wrap">

	<div id="main">
	
		<!-- Authentication part -->
		<?php if( ( ADMIN_PASS != 'none' ) and ( empty($_SESSION['xfbrowserauth']) ) ): ?>
		<div id="login" align="center">
			
			<img alt="logo" align="middle" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAFoAAABaCAYAAAFPr3GUAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAA2RpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuMC1jMDYwIDYxLjEzNDc3NywgMjAxMC8wMi8xMi0xNzozMjowMCAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wTU09Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9tbS8iIHhtbG5zOnN0UmVmPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvc1R5cGUvUmVzb3VyY2VSZWYjIiB4bWxuczp4bXA9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC8iIHhtcE1NOk9yaWdpbmFsRG9jdW1lbnRJRD0ieG1wLmRpZDo5QTk1RDI5RUU1RjlFMTExQUNFOUIxMzVDRjEzNEQyQSIgeG1wTU06RG9jdW1lbnRJRD0ieG1wLmRpZDpCOTJEN0RDN0Y5RTUxMUUxQjIwMEE4RTRBMDA4ODNGMyIgeG1wTU06SW5zdGFuY2VJRD0ieG1wLmlpZDpCOTJEN0RDNkY5RTUxMUUxQjIwMEE4RTRBMDA4ODNGMyIgeG1wOkNyZWF0b3JUb29sPSJBZG9iZSBQaG90b3Nob3AgQ1M1IFdpbmRvd3MiPiA8eG1wTU06RGVyaXZlZEZyb20gc3RSZWY6aW5zdGFuY2VJRD0ieG1wLmlpZDo5QTk1RDI5RUU1RjlFMTExQUNFOUIxMzVDRjEzNEQyQSIgc3RSZWY6ZG9jdW1lbnRJRD0ieG1wLmRpZDo5QTk1RDI5RUU1RjlFMTExQUNFOUIxMzVDRjEzNEQyQSIvPiA8L3JkZjpEZXNjcmlwdGlv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">
			
			<h2>PHPFileBrowser</h2>
		
			<?php if (count($_POST)) :?><p class="msg warn">Wrong password!</p><?php endif;?>
		
			<form method="post">
				<label for="yourpass">Password:</label> &nbsp;&nbsp;
				<input type="password" name="yourpass" id="yourpass" size="35" value="">  
				<button type="submit"> Enter </button>
			</form>
			<p>&nbsp;</p>
			<p align="center">
				<em>- Credits* -</em><br/><br/>
				<a href="http://adrian.silimon.eu/" target="_blank">Adrian7 - the geek, for developing this awesome project</a><br/>
				<a href="http://www.iconfinder.com/" target="_blank">iconfinder.com - for providing the cdn and icons</a><br/>
				<br/><small>* so you know who made it happen. Happy browsing!</small>
			</p>
		</div>
		<?php die(); endif;?>
		
		<!-- Authentication part -->
	
		<!-- Navigation & Search -->
		<div id="navbar">
			
			<div class="quicknav">
				<a class="prev" href="javascript:history.go(-1)"  title="previous page">&nbsp;</a>
				<a class="next" href="javascript:history.go(+1)" title="next page">&nbsp;</a>
				<a class="home" href="<?php echo (SBURL .'/'. SBFILE); ?>" title="main page">&nbsp;</a>
			</div>
			
			<?php breadcrumbs(); ?>
				
			<div class="search" class="fixed">
				<form name="finder-form" id="finder-form" method="get" target="_self">
					<input placeholder="filename..." type="text" name="s" id="s" size="20" maxlength="255" value="<?php echo stripslashes( urldecode($_GET['s'] )); ?>"/>	
				</form>
			</div>
				
			<br class="clear" />
		</div>
		<!-- Navigation & Search -->
		
		<!-- Display (View) part -->	
		<div id="view">
		
			<?php if(ADMIN_PASS == 'none' and empty($_GET)): /*show a warning for password reset*/ ?>
				<p class="msg warn">Please change your ADMIN_PASS to something else!</p>
			<?php endif;?>
		
			<div class="browser">
				<?php if( isset($_GET['view']) and strlen($_GET['view']) ): display(); else : if( strlen($_GET['s']) ) search(); else display("/"); endif; ?>
				<br class="clear" />
			</div>
			<br class="clear" />
		
		</div>
		
		<div class="tree">
			<iframe id="frame-tree" frameborder="0" height="100" width="100" scrolling="auto" src="<?php echo SBFILE; ?>?loading=1"></iframe>
		</div>
		
		<div class="filemenu">
			<ul>
				<li class="tree-show"><a title="show directory tree" href="#" onclick="animate_tree();">&nbsp;</a></li>
				<li class="open-browser"><a title="open in browser" href="<?php echo get_file_url( $_GET['view']);?>" target="_blank">&nbsp;</a></li>
				<?php if ( !$isSearch ): ?>
				<li class="download-file"><a title="download current file/folder" href="<?php echo ( SBFILE . '?download=' . urlencode($_GET['view']) );?>">&nbsp;</a></li>
				<?php endif; ?>
				<?php if ( $isFile and is_editable_file( urldecode($_GET['view']) ) ): ?>
					<li class="edit-file"><a title="edit file">&nbsp;</a></li>
				<?php endif; ?>
				<?php if ( $isFile ): ?>
					<li class="delete-file"><a title="delete file" onclick="confirm_delete('<?php echo ( SBFILE . '?delete=' . urlencode($_GET['view']) );?>');" href="#">&nbsp;</a></li>
				<?php endif;?>
				<li class="upload-file"><a title="upload file" href="#">&nbsp;</a></li>
			</ul>
		</div>
		
		<br class="clear" />
		
	</div>
	<!-- Display (View) part -->
	
	<div id="credits" class="fixed">powered by <a href="https://github.com/adrian7/phpfilebrowser" target="_blank">PHPFileBrowser</a></div>
	
</div>
</body>
</html>