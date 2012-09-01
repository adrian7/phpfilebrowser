<?php 

//--- Settings, plz don't ignore or you might create a security hole on your server ---//

define('ADMIN_PASS', 'none'); //Here you tell the script to limit access only to users aware of this password. 'none' means no password.

define('SBVDIR', '');//Here you can put a virtual dir, (ex.: /~homedir) to properly display files under a virtual directory

//--- Settings, hope you did some changes... ---//

session_start();

if ( isset($_POST['yourpass']) and  ( $_POST['yourpass'] == ADMIN_PASS ) ) $_SESSION['xfbrowserauth'] = 1;

define('SBFILE', basename(__FILE__));

define('SPATH', dirname(__FILE__));
define('SBURL', str_replace("/" . SBFILE, '', str_replace(( '?' . $_SERVER['QUERY_STRING']), "", $_SERVER['REQUEST_URI'])));


if ( isset($_GET['f']) ) { //file download action
	
	if ( (ADMIN_PASS != 'none') and empty($_SESSION['xfbrowserauth']) ) die("...");
	
	$path		   = stripslashes( urldecode($_GET['f']) );
	$download = basename($path);
	
	//$filetype = get_file_mime_type($path);
	$size	  	  = @filesize($path);
	
	//header("Content-type: $filetype");
	header("Content-length: $size");
	header("Content-disposition: attachment; filename=$download");
	
	readfile($path); die();
}

function fmt_filesize( $size ){
	if ($size > 1024) return ( round(($size / 1024), 2) . " KB" );
	if ($size > 1048576) return ( round(($size / 1048576), 2) . " MB" );
	if ($size > 1073741824) return ( round(($size / 1073741824), 2) . " GB" );
	
	return ($size . ' bytes');
}

function breadcrumbs($rootname="ROOT"){

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
		
		if ($handle = opendir($path) ){
 			while (false !== ($file = readdir($handle)) ){
			   	if ($file != "." && $file != ".."){

					$abspath = (rtrim($path) . "/" . $file);

					$csscls = "";
					$size	  = "";
					
					if( is_dir($abspath) )   $csscls = "dir ";
					if( is_file($abspath) ){ $csscls = "file "; $csscls.= get_file_ext($file); $size = (fmt_filesize( filesize($abspath) ) . " / "); }
					
					if( empty($csscls) ) $csscls = "symlink ";

					$lastmod = date("Y-m-d", @filemtime($abspath));
					
			    	$fileurl = ( rtrim($bdir, "/") .  "/" . str_replace(SPATH, "", $file) );
			    	
			    	?>
			    	
			    	<a class="<?php echo $csscls; ?>" href="<?php echo SBFILE; ?>?view=<?php echo urlencode($fileurl);?>"> 
			    		<span class="icon">&nbsp;</span><?php echo $file; ?> <span class="details"><?php echo $size; ?> <?php echo $lastmod; ?> </span>
			    	</a>
			    	
					<?php 
			   	}
			 }
			  
			closedir($handle);
		}
	
		
}
//--- display directory function ---//
	
//--- display file function ---//
function display_file(){

	$path = ( SPATH . str_replace(SBVDIR, "", urldecode($_GET['view'])) ); 
		
	if( is_dir($path) ): /*display directory listing*/ 
		display_dir();
	else: ?>
			
		<?php if( is_raw_visible_file($path) ): ?>
			<iframe src="<?php echo ( SBURL . urldecode($_GET['view']) ); ?>"></iframe>
		<?php else: ?>
			<pre><code class="<?php echo get_source_highlight($path);?>"><?php echo htmlentities(file_get_contents($path)); ?></code></pre>
			
			<script>
		  		hljs.tabReplace = '    ';
		  		hljs.initHighlightingOnLoad();
		  	</script>
		<?php endif;?>
	<?php endif;
	
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

function is_downloadable_file($file){

	$ext = get_file_ext($file);
	
	//archives
	if ( $ext == "zip" or $ext=='gz' or $ext == 'tar' or $ext == 'rar' or $ext == 'jar' or $ext == '7z') return TRUE;
	
	//special files
	
	return FALSE;
	
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

	if(!class_exists('RecursiveDirectoryIterator')) die("<h2>RecursiveDirectoryIterator class not available!</h2>"); $term = stripslashes( urldecode($_GET['s'] ) ); 
		
		$Tree = new RecursiveDirectoryIterator(SPATH);

		$count=0;
		
		foreach(new RecursiveIteratorIterator($Tree) as $path) {
			
			$filename = basename($path);
			
			if( strpos(strtolower(trim($filename)), $term) === FALSE ) continue; else{ 
				
					$fileurl = ( str_replace(SPATH, "", $path) ); 
				
					$csscls = "";

					if( is_dir($path) )   $csscls = "dir ";
					if( is_file($path) ){ $csscls = "file "; $csscls.= get_file_ext($path); }
					
					if( empty($csscls) ) $csscls = "symlink ";

			    	//$fileurl = ( rtrim($bdir, "/") .  "/" . str_replace(SPATH, "", $file) );
			    	
			    	?>
			    	
			    	<a class="<?php echo $csscls; ?>" href="<?php echo SBFILE; ?>?view=<?php echo urlencode($fileurl);?>"> 
			    		<span class="icon">&nbsp;</span><?php echo basename($path); ?>
			    		<span class="details">2kb / 20 May 1988 </span>
			    	</a>
			    	
					<?php 
			}

		}
	
}
//--- search function ---//

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8" />
<meta name="author" content="Adrian7 (http://adrian.silimon.eu/)" />
<link href="http://fonts.googleapis.com/css?family=Source+Sans+Pro:400,600&subset=latin,latin-ext" rel="stylesheet" type="text/css">
<link rel="stylesheet" href="http://yandex.st/highlightjs/7.0/styles/default.min.css">
<script src="http://yandex.st/highlightjs/7.0/highlight.min.js"></script>
<style type="text/css">
body, button, input{
	font-size:100%;
	font-family:'Source Sans Pro', Arial, Helvetica, sans-serif;
	margin:0px;
	padding:0px;
	background: #59B0F7;
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
	height:auto;
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

.search input{
	display:inline-block;
	background: #FAFAFA;
	background-image: url('http://cdn1.iconfinder.com/data/icons/simplegrey/search.png');
	background-size:1em auto;
	background-repeat: no-repeat;
	background-position:96% center;
	box-shadow: inset 1px 1px 3px #888;
	border:none;
	border-radius: 2px;
	padding:2px 4px;
	font-size: 1em;
	height:1.3em;
	font-style:italic;
}

#navbar .quicknav{
	margin:0.52em 0.2em;
	float:left;
	width:6.5%;
}

.quicknav a{
	height:1.4em;
	width:1.4em;
	display:inline-block;
	float:left;
	background-size:1.4em;
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

.browser a.dir .icon{
	background-image:url('http://cdn1.iconfinder.com/data/icons/crystalproject/64x64/filesystems/folder_yellow.png');
}

.browser a.dir:hover .icon{
	background-image:url('http://cdn1.iconfinder.com/data/icons/crystalproject/64x64/filesystems/folder_blue.png');
}

#view{
	margin:0px auto;
	background:#FBFBFB;
	margin-top:-2px;
	width:100%;
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
	margin:1.5em;
	width:auto;
}

.browser pre, .browser iframe{
	box-shadow: 2px 2px 10px #888;
	border:1px solid #CDCDCD;
	border-radius: 0.5em;
	word-wrap:break-word;
}

.browser iframe{
	padding:2em;
	width:94%;
	min-height:300px;
	height:400px;
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
	min-width: 100px;
	width:auto;
	text-align:left;
	border: 1px solid transparent;
	border-radius: 0.5em;
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
	float:left;
	width:auto;
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

.browser a.file .icon, .browser a.unknown .icon{
	background-image:url('http://cdn1.iconfinder.com/data/icons/crystalproject/64x64/mimetypes/txt.png');
}

.browser a.png .icon, .browser a.jpg .icon, .browser a.jpeg .icon, .browser a.gif .icon{
	background-image:url('http://cdn1.iconfinder.com/data/icons/crystalproject/128x128/filesystems/image.png');
}

.browser a.php .icon, .browser a.phps .icon, .browser a.php5 .icon{
	background-image:url('http://cdn1.iconfinder.com/data/icons//CrystalClear/128x128/mimetypes/php.png');
}

.browser a.js .icon{
	background-image:url('http://cdn1.iconfinder.com/data/icons/Futurosoft%20Icons%200.5.2/128x128/mimetypes/js.png');
}

.browser a.htm .icon, .browser a.html .icon, .browser a.shtml .icon{
	background-image:url('http://cdn1.iconfinder.com/data/icons/Futurosoft%20Icons%200.5.2/128x128/mimetypes/html.png');
}

.browser a.psd .icon{
	background-image:url('http://cdn1.iconfinder.com/data/icons/Futurosoft%20Icons%200.5.2/128x128/mimetypes/psd.png');
}

.browser a.swf .icon{
	background-image:url('http://cdn1.iconfinder.com/data/icons/Futurosoft%20Icons%200.5.2/128x128/mimetypes/swf.png');
}

.browser a.avi .icon, .browser a.mov .icon, .browser a.mp4 .icon, .browser a.divx .icon, .browser a.wmv .icon, .browser a.flv .icon, .browser a.ogv .icon, 
.browser a.webm .icon{
	background-image:url('http://cdn1.iconfinder.com/data/icons/crystalproject/128x128/mimetypes/video.png');
}

.browser a.mp3 .icon, .browser a.pcm .icon, .browser a.midi .icon, .browser a.ogg .icon, .browser a.au .icon{
	background-image:url('http://cdn1.iconfinder.com/data/icons/realistiK-new/128x128/mimetypes/sound.png');
}
</style>
</head>
<body>
<div id="main">

	<!-- Authentication part -->
	<?php if( ( ADMIN_PASS != 'none' ) and ( empty($_SESSION['xfbrowserauth']) ) ): ?>
	<div id="login">
		<form method="post">
			<label for="yourpass">Password:</label> 
			<input type="password" name="yourpass" id="yourpass" size="45" value="">  
			<button type="submit"> Enter </button>
		</form>
	</div>
	<?php die(); endif;?>
	
	<!-- Authentication part -->

	
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
		
		<div id="view">
			<div class="browser">
				<?php if( isset($_GET['view']) and strlen($_GET['view']) ) display_file(); else {
					
						if( strlen($_GET['s']) ) 
							search();
						else 
							display_file("/");
						
					}
					
				?>
				<br class="clear" />
			</div>
			<br class="clear" />
		</div>
	
	<br class="clear" />
	
</div>

<!-- 

 -->

<div id="credits" class="fixed">
	powered by <a href="#link">PHPFileBrowser</a>
</div>

</body>
</html>