<?php
//session_start(); //Använd sessions
header('Cache-Control: no-cache');
$speltid = file_get_contents('status/speltid');
$queue = explode("\n", file_get_contents('status/queue')); //Läs in köfilen
if(!$queue[1]) {//Om aktuell spelare inte aktiverat sig
 if(isset($queue[2]) && $queue[2]==$_COOKIE['queue']) {
	$queue[1]=true;
	file_put_contents('status/queue', implode("\n", $queue));
 }
 elseif($queue[0] < time()+$speltid-5) $queue[0] = 0; //Om spelaren som står på tur inte varit aktiv inom 5 sek så kastas hen ut i nedanstående
}
if($queue[0] <= time()) { //Uppdatera kön ifall tiden har förflutit
	unset($queue[2]);
	$queue = array_values($queue);
	$queue[1] = isset($queue[2]) && $queue[2]==$_COOKIE['queue']? 1: 0;
	$queue[0] = time()+$speltid;
	file_put_contents('status/queue', implode("\n", $queue));
}
$queue_pos = array_search($_COOKIE['queue'], $queue, true); //1=Aktuell spelare, 2=nästkommande spelare ..., false=Inte i kön
$wait = $queue_pos > 2? $queue[0]-time()+($queue_pos-3)*$speltid: 0;
if(isset($_SERVER['QUERY_STRING'])) switch($_SERVER['QUERY_STRING']) {
 case 'queue': echo $wait; exit;
 case 'time': echo isset($queue[2]) && $queue[2]==$_COOKIE['queue'] && $queue[0]>time()? $queue[0]-time(): 0; exit;
 
 case 'exit':
	if($queue_pos) {
	 unset($queue[$queue_pos]);
	 if($queue_pos == 2) {$queue[0]=time()+$speltid+4; $queue[1]=0;} //Nästa spelare får lite extratid, eftersom hen får börja oväntat
	 file_put_contents('status/queue', implode("\n", $queue));
	}
	header("Location: index.php");
	exit;
 case 'new':
	if($queue_pos) {
	 unset($queue[$queue_pos]);
	 $queue = array_values($queue);
	 if($queue_pos == 2) {$queue[0]=time()+$speltid+4; $queue[1]=0;} //Nästa spelare får lite extratid, eftersom hen får börja oväntat
	 $queue_pos = false;
	}
/*
	$queue_pos = count($queue);
	setcookie('queue', $queue[]=md5(microtime()));
	setcookie('queueStart', $wait = $queue[0]-time()+($queue_pos-3)*$speltid);
	file_put_contents('status/queue', implode("\n", $queue));
	header("Location: queue.php");
	exit;
*/
	if(!isset($_COOKIE['queue'])) setcookie('queue', md5(microtime()));
	header("Location: queue.php?new2");
	exit;
	
 case 'new2':
	if($queue_pos) {
		// Här måste något vara fel...
		header("Location: queue.php"); //Minst dåliga åtgärden?
		exit;
	}
	elseif(!isset($_COOKIE['queue'])) { header('Location: index.php?check_cookie'); exit; }
	$queue_pos = count($queue);
	if($queue_pos > 6) { header('Location: index.php?queue_full'); exit; }
	$queue[] = $_COOKIE['queue'];
	setcookie('queueStart', $wait = $queue[0]-time()+($queue_pos-3)*$speltid);
	file_put_contents('status/queue', implode("\n", $queue));
	header("Location: queue.php");
	exit;
	
	
	
	
}
if(!isset($_COOKIE['queue'])) { header('Location: index.php?check_cookie'); exit; }
if(!$queue_pos) { //Se till att spelaren finns i kön
	// Om man kommer hit så har något gått fel...
	header("Location: queue.php?new");
	exit;
//	$queue_pos = count($queue);
//	$kölapp =  md5(time());
//	setcookie('queue', $kölapp);}
//	$queue[]=$kölapp;
//	file_put_contents('status/queue', implode("\n", $queue));
	//$_SESSION['start_time'] = $wait = $queue[0]-time()+($queue_pos-3)*$speltid;
//	setcookie('queueStart', $wait = $queue[0]-time()+($queue_pos-3)*$speltid);
	
}
//if($queue_pos==2){ echo 'först i kön. hoppar till spela.php'; exit;}
$starttid = $_COOKIE['queueStart']? $_COOKIE['queueStart']: $wait;
if($queue_pos==2){ header("Location: spela.php"); exit;}
// Sniffa browser:
 preg_match('/Chrome\/(\d+\.\d+)/', $_SERVER['HTTP_USER_AGENT'], $d); $chrome = (float) $d[1];
 preg_match('/Opera[\/\s](\d+\.\d+)/', $_SERVER['HTTP_USER_AGENT'], $d); $opera = (float) $d[1];
 preg_match('/Gecko\/(\d+)/', $_SERVER['HTTP_USER_AGENT'], $d); $gecko = (int) $d[1];
 if(!$opera) { preg_match('/MSIE\s(\d+\.\d+)/', $_SERVER['HTTP_USER_AGENT'], $d); $msie = (float) $d[1]; }
 preg_match('/Safari\/(\d+(\.\d+)?)/', $_SERVER['HTTP_USER_AGENT'], $d); $safari = (float) $d[1];
 preg_match('/Konqueror\/(\d+\.\d+)/', $_SERVER['HTTP_USER_AGENT'], $d); $konqueror = (float) $d[1];
 preg_match('/AppleWebKit\/(\d+(\.\d+)?)/', $_SERVER['HTTP_USER_AGENT'], $d); $awk = (float) $d[1]; //bättre än safari enligt flera källor
 preg_match('/KHTML\/(\d+(\.\d+)?)/', $_SERVER['HTTP_USER_AGENT'], $d); $khtml = (float) $d[1];
//  <object width="640" height="505"><param name="movie" value="http://www.youtube.com/v/U7kpkbL6vQE?fs=1&amp;hl=sv_SE"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.youtube.com/v/U7kpkbL6vQE?fs=1&amp;hl=sv_SE" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="640" height="505"></embed></object>
//list($ip, $port) = explode(':', $_SERVER['SERVER_NAME']);
//if(!$port) $ip='192.168.1.223';
//$kamera_adr = "http://$ip:8088";
$kamera_adr = "http://".file_get_contents('status/robot_ip').":8088/cam";
function webcamklient($typ) {
	global $kamera_adr;
	switch($typ) {
		case 'mjpg':
			echo '<img width="640" height="480" src="',$kamera_adr,'"/>';
			break;
	//	case 'java':
	//		echo '<applet code="com.charliemouse.cambozola.Viewer" archive="',$kamera_adr,'/cambozola.jar" width="640" height="480">',
	//					'<param name="url" value="',$kamera_adr,'/?action=stream"/>Test</applet>';
	//		break;
	//	case 'javascript':
	//		echo '<img style="position:absolute;left:0px;top:0px;" width="640" height="480" src="',$kamera_adr,'/?action=snapshot" ',
	//		'onload="if(errT) clearTimeout(errT); errT = setTimeout(restart_webcam,1000); this.style.zIndex=1;nextSibling.style.zIndex=0;nextSibling.src=\'',$kamera_adr,'/?action=snapshot&amp;f=\'+imgCnt++" ',
	//		'onerror="restart_webcam()"/>';
	//		echo '<img style="position:absolute;left:0px;top:0px;" width="640" height="480" ', 
	//		'onload="clearTimeout(errT); errT = setTimeout(restart_webcam,1000); this.style.zIndex=1;this.previousSibling.style.zIndex=0;previousSibling.src=\'',$kamera_adr,'/?action=snapshot&amp;f=\'+imgCnt++" ',
	//		'onerror="restart_webcam()"/>';
	//		break;
		default:
			echo '<img width="640" height="480" src="',$kamera_adr,'"/>';
	}
}
if(isset($_GET['webcamklient'])) { webcamklient($_GET['webcamklient']); exit; }
header('Content-Type: text/html; charset=utf8');
?><!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:fb="http://www.facebook.com/2008/fbml" xml:lang="sv" lang="sv">


<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title>GameReality</title>
   <meta name="description" content="GameReality. A real world game environment with new events every day. Press keys  W,A,S,D and arrows right/left to control the robot." />
	
	<meta name="generator" content="Geany 0.18" />
	<link href="style.css" media="screen" rel="stylesheet" type="text/css" />
</head>

<body><div id="container">
<div id="top">
<h1><a href="http://www.gamereality.se/" target="_top">GameReality 2019</a>
</h1>


<a href="http://www.bredbandskoll.net/ip-adress-lank" ><img src="http://www.bredbandskoll.net/ip-adress-gron-2rader-stor.php" border="0" ></a>



<p> A real world game environment with new events every day. Press keys  W,A,S,D and arrows right/left to control the robot.
</p>
</div>

<!--
<div style="position:absolute; top: 0; right: 0;border: outset 1px; color:black; background: white;padding:7px;text-align:right;">
	Problem med bilden?<br/> testa någon av dessa:<br/>
<input type="image" src="images/MJPG.png" onclick="setWebcam('mjpg');" title="Visa kamerabild som MJPG-bild"/>
<input type="image" src="images/Duke.png" onclick="setWebcam('java');" title="Använd Javabaserad kameravisning"/>
<input type="image" src="images/js.png" onclick="setWebcam('javascript');" title="Använd Javascriptbaserad kameravisning"/>
</div>
-->


<div id="scenen">


<div id="webcamcontainer">
<div id="webcam"><?php webcamklient($gecko>0? 'mjpg': 'javascript'); ?></div>

   <div id="progress_frame">
    <div id="progress"></div>
    <input id="progress_info" value="Var god vänta..."/>
   </div>
   
   
<br>

   
	<button style="float:left;clear:right;" onclick="window.location.href='queue.php?exit'" style="border-color:red;">Avbryt</button>
   </div>
  </div>
  
  <script type="text/JavaScript">/*<![CDATA[*/
var start_time = <?php echo json_encode($starttid); ?>; //Den totala kötiden när köandet började
var progress = document.getElementById("progress");
var progress_info = document.getElementById("progress_info");
progress.style.left = '0'; 
progress.style.backgroundPosition = 'left'; 
var time = new Date(); //Aktuell tid
var time_end  = <?php echo $queue[0]-time()+($queue_pos-3)*$speltid; ?>*1000+time.getTime(); //Beräknad tid när spelandet kan börja
var time_check = time.getTime()+1000; //Nästa gång tiden kollas mot servern
var infoHttpObj = new XMLHttpRequest();
var imgCnt = 0; //Bildräknare för unika bilder
var errT;
function infoTimer() { //Funkar denna bättre?
	time = new Date();
	updateInfo(time_end-time.getTime());
	if(time.getTime() < time_end+900) {
	 if(time.getTime() > time_check) {
	  var infoHttpObj = new XMLHttpRequest();
	  infoHttpObj.onreadystatechange=server_response;
	  infoHttpObj.open("GET","queue.php?queue",true);
	  try {infoHttpObj.send(null);}
	  catch(e){}
	 }
	 setTimeout("infoTimer()",300); //uppdatera tidräknaren igen om 300ms
	}
	else window.location.href="spela.php"; //Klockan har nått 0
}
infoTimer(); //Starta loopen
function server_response() { //Denna funktion anropas var gång servern svarar
	if (this.readyState==4){
	 if(this.responseText.match(/^\d+$/)) {
	  if(this.responseText*1) {
	   var d = this.responseText*1000+time.getTime()-time_end;
	   if(d > -50000000 && d < 500000) time_end += d; //Tillåt inga extremdiffar
	   time_check = Math.min(time.getTime()+4000, time_end);
	  }
	  else {
	   updateInfo(0);
	   time_end=time.getTime();
	   window.location.href="spela.php";
	  }
	 }
	}
}
function setWebcam(value) {
	var http = new XMLHttpRequest();
	http.open('GET', '?webcamklient='+encodeURIComponent(value), false);
	http.send(null);
	document.getElementById('webcam').innerHTML = http.responseText;
}
function restart_webcam() {
	var img = document.getElementById('webcam').getElementsByTagName('img');
	img[0].src="<?php echo $kamera_adr; ?>/?action=snapshot&f="+imgCnt++;
}
function updateInfo(tid) {
 	if(tid < 0) tid =0;
	if(tid > 0) {
	progress.style.width = (124-0.123*tid/start_time)+'px';
	tid = Math.round(tid/1000);
	var enh=' sek';
	if(tid > 90) {
	 enh=' min';
	 tid = Math.round(tid/60);
	}
	progress_info.value = 'Max '+tid+enh+' queue';
	}
	//else window.location.href="tackochhej.php";
}
  /*]]>*/</script>
 </body>
</html>
