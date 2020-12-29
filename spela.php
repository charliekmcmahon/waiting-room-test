<?php
if(!isset($_COOKIE['queue'])) { header('Location: index.php?check_cookie'); exit; }
//list($ip, $port) = explode(':', $_SERVER['SERVER_NAME']);
//if(!$port) $ip='192.168.1.223';
//$kamera_adr = "http://$ip:8088";

$kamera_adr = "http://".trim(file_get_contents('status/robot_ip')).":8088/cam";
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
//session_start(); //Använd sessions
header('Cache-Control: no-cache');
$speltid = file_get_contents('status/speltid');
$queue = explode("\n", file_get_contents('status/queue')); //Läs in köfilen
$iparray = (array) json_decode(file_get_contents('status/ip_block'));
$ip_block = isset($iparray[$_SERVER["REMOTE_ADDR"]]) && $iparray[$_SERVER["REMOTE_ADDR"]]>(time()-3600);
	
if($ip_block) {
	$fh = fopen('status/återbesökare', 'a');
	fwrite($fh, $_COOKIE["queue"]."\n");
	fclose($fh);
}
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
$queue_pos = array_search($_COOKIE['queue'], $queue, true); //2=Aktuell spelare, 3=nästkommande spelare ..., false=Inte i kön
if($queue_pos != 2) {
	header('Location: queue.php');
	exit;
}
header('Content-Type: text/html; charset=utf8');
// Sniffa browser:
 preg_match('/Chrome\/(\d+\.\d+)/', $_SERVER['HTTP_USER_AGENT'], $d); $chrome = (float) $d[1];
 preg_match('/Opera[\/\s](\d+\.\d+)/', $_SERVER['HTTP_USER_AGENT'], $d); $opera = (float) $d[1];
 preg_match('/Gecko\/(\d+)/', $_SERVER['HTTP_USER_AGENT'], $d); $gecko = (int) $d[1];
 if(!$opera) { preg_match('/MSIE\s(\d+\.\d+)/', $_SERVER['HTTP_USER_AGENT'], $d); $msie = (float) $d[1]; }
 preg_match('/Safari\/(\d+(\.\d+)?)/', $_SERVER['HTTP_USER_AGENT'], $d); $safari = (float) $d[1];
 preg_match('/Konqueror\/(\d+\.\d+)/', $_SERVER['HTTP_USER_AGENT'], $d); $konqueror = (float) $d[1];
 preg_match('/AppleWebKit\/(\d+(\.\d+)?)/', $_SERVER['HTTP_USER_AGENT'], $d); $awk = (float) $d[1]; //bättre än safari enligt flera källor
 preg_match('/KHTML\/(\d+(\.\d+)?)/', $_SERVER['HTTP_USER_AGENT'], $d); $khtml = (float) $d[1];
?><!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:fb="http://www.facebook.com/2008/fbml" xml:lang="sv" lang="sv">


<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title>GameReality</title>
	
   <meta name="description" content="GameReality. En spelvärld med nya händelser varje dag. Tryck på knapparna W,A,S,D och pilarna höger/vänster och styr roboten." />
	<meta name="generator" content="Geany 0.18" />
	<link href="style.css" media="screen" rel="stylesheet" type="text/css" /> 
	<script type="text/JavaScript">/*<![CDATA[*/
var errT = false;	
function restart_webcam() {
	var img = document.getElementById('webcam').getElementsByTagName('img');
	img[0].src="<?php echo $kamera_adr; ?>/?action=snapshot&f="+imgCnt++;
}
function body_key(e) {
	if(!e) e = window.event;
	//alertarea('keyCode: '+e.keyCode+', which: '+e.which, 2000);
	switch(e.keyCode) {
		case 37: send_cmd('go_left'); e.preventDefault(); return false;
		case 39: send_cmd('go_right'); e.preventDefault(); return false;
		case 32: send_cmd('go_fwd'); e.preventDefault(); return false;
		case 38: send_cmd('go_fwd'); e.preventDefault(); return false;
		case 40: send_cmd('go_back'); e.preventDefault(); return false;
		case 87: send_cmd('go_fwd'); e.preventDefault(); return false;
		case 83: send_cmd('go_back'); e.preventDefault(); return false;
		case 68: send_cmd('go_right'); e.preventDefault(); return false;
		case 65: send_cmd('go_left'); e.preventDefault(); return false;			
		default:  break;
		
	}
}
	
  /*]]>*/</script>
</head>

<body onkeydown="body_key(event)"><div id="container">
<div id="knappanel"></div>



<div id="top">
<p>Push keys W,A,S,D and arrows to control the robot. </p>
</div>

<div id="fb-root"></div>

<div id="scenen">
<div id="webcamcontainer">

<div id="imgtopleft" onclick="send_cmd('go_4')" title="Längst fram till vänster"></div>
<div id="imgtopright" onclick="send_cmd('go_5')" title="Längst fram till höger"></div>
<div id="imgleft" onclick="send_cmd('go_2')" title="Till vänster"></div>
<div id="imgright" onclick="send_cmd('go_3')" title="Till höger"></div>
<div id="imgback" onclick="send_cmd('go_back')" title="tillbaka"></div>
<div id="alertarea"></div>

<div id="webcam">
<?php webcamklient($gecko>0? 'mjpg': 'javascript'); ?>
</div>


  <div id="progress_frame">
   <div id="progress"></div>
   <input id="progress_info" value="Var god vänta..."/>
  </div>
  

<div id="arrowbox">
	<div id="goup" onclick="send_cmd('go_fwd');"></div>
	<div id="godown" onclick="send_cmd('go_back');"></div>
	<div id="goleft" onclick="send_cmd('go_left');"></div>
	<div id="goright" onclick="send_cmd('go_right');"></div>
</div>  



<button style="float:left;clear:right;margin-bottom:5px;" onclick="send_cmd('close'); window.location.href='queue.php?exit'" style="border-color:red;">Avbryt</button>
  
  
<!-- 
  <div id="luckarea" xonclick="luckoppning(event.target)">
<?php
	$imorgon = min(25, date('d')+1);
	$imorgon = 25;
	for($i=1; $i<$imorgon; $i++) {
		$p = 'top:'.(floor(($i-1)/6)*75).'px;left:'.((($i-1)%6)*110).'px;';
		//echo '<div class="lucka" style="background-image:url(images/',$i,'.png);',$p,'"><div>', $i, '</div></div> ';
		echo '<div class="lucka" style="background-image:url(images/',$i,'.png);',$p,'"><div onclick="luckoppning(this, ', $i, ')">', $i, '</div></div> ';
	}
	for($i=$imorgon; $i<25; $i++) {
		$p = 'top:'.(floor(($i-1)/6)*75).'px;left:'.((($i-1)%6)*110).'px;';
		echo '<div class="lukket" style="',$p,'">', $i, '</div> ';
	}
?></div>
-->  

  
</div>


</div>




  <script type="text/JavaScript">/*<![CDATA[*/
  var preloadImg = new Image(100,25); 
  preloadImg.src="images/glutta.png"; 
function on_resize() { //Se till att ytan ovanför bilden inte visas i onödan
	var h = Math.min(120, Math.max(0, window.innerHeight-640))+'px';
	if(h.match(/^\d+px$/)) document.getElementById('top').style.height = h;
}
window.onresize = on_resize;
on_resize();
 
var start_time = <?php echo $queue[0]-time(); ?>; //Den totala speltiden när sidan laddades
var progress = document.getElementById("progress");
var progress_info = document.getElementById("progress_info");
progress.style.right = '0'; 
progress.style.backgroundPosition = 'right'; 
var time = new Date(); //Aktuell tid
var time_end  = <?php echo $queue[0]-time(); ?>*1000+time.getTime(); //Beräknad tid när spelandet avbryts
var time_check = time.getTime()+1000; //Nästa gång tiden kollas mot servern
//var infoHttpObj = new XMLHttpRequest();
function server_response() { //Denna funktion anropas var gång servern svarar
	//alertarea('x', 150);
	if (this.readyState==4){
	 if(this.responseText.match(/^\d+$/)) {
	  var d = this.responseText*1000+time.getTime()-time_end;
	  if(true || d > -5000000 && d < 500000) {
		  time_end += d; //Tillåt inga extremdiffar
	  }
	  time_check = time.getTime()+4000; //Kolla mot servern om fyra sekunder
	 }
	 else if(this.responseText.length) alertarea(this.responseText, 10000);
	}
}
//var aktiva_cmd = {};
//aktiva_cmd['a'] = '123';
function infoTimer() {
	//alertarea('x', 150);
	time = new Date();
	updateInfo(time_end-time.getTime());
	if(time.getTime() < time_end) {
	 if(time.getTime() > time_check) {
		time_check = time.getTime()+6000;
	//	alertarea('skall... ', 2000);
	//var infoHttpObj = new XMLHttpRequest();
		var infoHttpObj = new XMLHttpRequest();
	  infoHttpObj.onreadystatechange=server_response;
	  infoHttpObj.open("GET","queue.php?time",true);
	  try {infoHttpObj.send(null);}
	  catch(e){}
	 }
	 /*t=*/ setTimeout("infoTimer()",300); //uppdatera tidräknaren igen om 300ms
	}
	else { //Klockan har nått 0
		send_cmd('close');
		window.location.href="/index.php";
	}
//	for(var http in aktiva_cmd) {
//		if(http.readyState < 4) continue;
//		alertarea(http.responseText, 150);
//	}
}
infoTimer(); //Starta loopen
function updateInfo(tid) {
	if(tid > 0) {
	progress.style.width = Math.round(0.123*tid/start_time+1)+'px';
	tid = Math.round(tid/1000);
	var enh=' sek';
	if(tid > 90) {
	 enh=' min';
	 tid = Math.round(tid/60);
	}
	progress_info.value = tid+enh+' speltid kvar '; 
	}
	//else window.location.href="tackochhej.php";
}
// ==================== STYRSYSTEMET: ========================
function httpObj() {
	return new XMLHttpRequest();
}
var imgCnt = 0; //Bildräknare för unika bilder
var servoLeft = 62;
var status = document.getElementById("infoarea");
function debug(obj) {
	var t = "";
	for(var x in obj) t += x+', ';
	alert(t);
}
function send_cmd(cmd) {
	var http = new XMLHttpRequest();
	http.t = new Date().getTime();
	http.cmd = last_cmd = cmd+'/'+http.t;
	http.textlength = 0;
	
	http.open('GET', 'cmd.php/'+http.cmd, true);
	//http.open('GET', 'cmd.php/'+http.cmd, false);
	//debug(http);
//	http.onload = function() { 
	//	alertarea('svar', 1000); return;
	//alertarea('status '+this.readyState, 1000);
//	}
	
	
	
	
	http.onreadystatechange = function() { 
		//alertarea('status '+this.readyState, 1000);
		if(this.readyState < 3) return;
		//delete aktiva_cmd[this.cmd];
		
		
		//alertarea('svar', 1000); return;
		try {
			var newtext = this.responseText.substr(this.textlength).split(/\n+/);
			this.textlength = this.responseText.length;
		}
		catch(e) {
			var newtext ='';
			this.textlength =0;
			return;
		}
		for(var i=0; i<newtext.length; i++) if(newtext[i].match(/[^\s]/)) {
			var msg = newtext[i].replace(/\s/, '\n').split(/\n/);
			msg[1] = msg[1].replace(/\s*$/, '');
			switch(msg[0]) {
				case '': break;
				case 'Warning:':
				case '<b>Warning</b>:':
				case 'alert': alertarea(msg[1], msg[1].length*40+3000); break;
				case '1':
				case 'info': document.getElementById('msg1').innerHTML = msg[1]; break;
				case '2':
				case 'tips': document.getElementById('imghit').title = msg[1]; break;
				case 'mellannivåklickytor': mellanklickrutor(msg[1]); break;
				//case 'old_exit': location.href = "http://valslaget.se/tackochhej.php?slag="+msg[1]; break;
				//case 'exit':  exit("http://valslaget.se/tackochhej.php?slag="+msg[1]); break;
				case 'audio':
					if(window.parent.audio) window.parent.audio.location = location.protocol+'//'+location.host+'/audio.php?url='+encodeURIComponent(msg[1]);
					break;
				case 'stänglucka':
					//alert('Skall stänga lucka'+msg[1]);
					var luckor = document.getElementsByClassName('lucka');
					//alert('Antal luckor: '+luckor.length);
					for(var i=0; i<luckor.length; i++) if(luckor[i].textContent == msg[1]) {
						//alert('Har hittat lucka '+luckor[i].textContent);
						luckor[i].firstChild.style.backgroundImage = null;
					}
					
					
					//var lucka = Array.filter( document.getElementsByClassName('lucka'), function(elem){  
					//	return elem.textContent == msg[1];  
					//})[0];
					//alert('...');
					//lucka.firstChild.style.backgroundImage = null;
					//alert('Stängde lucka'+lucka.textContent);
					break;
				//default: alertarea('Meddelande '+msg[0]+' har ingen hanterare', 2000);
				default: console.log(msg);
			}
		}
	}
	http.send(null);
	//aktiva_cmd[http.cmd] = http;
}
send_cmd('start'); //Försätt teatern i startläge
function alertarea(msg, tid) {
	var aa = document.getElementById('alertarea');
	aa.style.display = 'block';
	aa.innerHTML = msg;
	setTimeout(function() {
		document.getElementById('alertarea').style.display = 'none';
	}, tid);
}
function screenshot() { //Inte i bruk för närvarande, men en trevlig funktion var det. Kanske att återinföra igen.
	var http = new XMLHttpRequest();
	http.open('GET', 'cmd.php/snapshot', true);
	http.onreadystatechange = function(){
		if(this.readyState < 4) return;
		document.getElementById('statusbild').src='images/status.jpg?'+Math.random();
	}
	http.send(null);
}
function setWebcam(value) {
	var http = new XMLHttpRequest();
	http.open('GET', '?webcamklient='+encodeURIComponent(value), false);
	http.send(null);
	document.getElementById('webcam').innerHTML = http.responseText;
}
/*
//Lite facebook-kod:
window.fbAsyncInit = function() {FB.init({appId: '128949067127332', status: true, cookie: true, xfbml: true}); };
      (function() {
        var e = document.createElement('script');
        e.type = 'text/javascript';
        e.src = document.location.protocol +
          '//connect.facebook.net/sv_SE/all.js';
        e.async = true;
        document.getElementById('fb-root').appendChild(e);
      }());
*/
function luckoppning(lucka) {
	var t = lucka.textContent === undefined ? lucka.innerText: lucka.textContent;
//	alert(t);
	if(t.length > 3) return; //utanförluckanklick
	if(lucka.className == 'lukket') return; //Inte öppingsbar ännu.
	
	send_cmd(t);
	lucka.style.backgroundImage = "url('images/öppen.png')";
	lucka.style.color = 'blue';
//	setTimeout(function() {lucka.style.backgroundImage = null;}, 20000); //Automatisk luckstängning
}
function mellanklickrutor(aktiv) {
	try {
		document.getElementById('imgleft').style.display = aktiv? null: 'none';
		document.getElementById('imgright').style.display = aktiv? null: 'none';
	}
	catch(e) {
		//alert('Din förbannade browser verkar sakna css-egenskapen "display"');
		return;
	}
	
}
	
	
function debug(obj) {
	var t = '';
	for(var x in obj) t += x+', ';
	alertarea(t, 200);
}
//document.getElementsByTagName('body')[0].setAttribute("onkeydown",'body_key(event)');
window.focus();
  /*]]>*/</script>
 



 </body>
</html>
