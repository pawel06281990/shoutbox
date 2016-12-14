<?php
if (!defined("IN_FUSION")) { header("Location: ../../index.php"); exit; }
if (isset($_GET['id']) && !isNum($_GET['id'])) fallback(FUSION_SELF);
if (isset($_GET['shout']) && !preg_match("#(add|edit|delete)#iu", $_GET['shout'])) fallback(FUSION_SELF);
if (isset($_GET['shout'])) {
$shout = $_GET['shout']; 
} else {
$shout = '';
}
if (isset($_GET['id'])){
$id = $_GET['id'];
} else {
$id = '';
}
define("SBX_EDIT_USER", $settings['sbx_edit_user']);
function displaysmileys2($textarea, $close="", $form="chatform") {
global $smiley_cache;
	$smileys = ""; $i = 0;
		if (!isset($smiley_cache)) cache_smileys();
			if (is_array($smiley_cache)) {
				$max_width = 400; $width = 0;
				foreach ($smiley_cache as $smiley) {
					if ($form == "chatform") {
						$img = getimagesize(IMAGES."smiley/".$smiley['smiley_image']);
							if ($width + $img[0] >= $max_width) {
								$smileys .= "<br>\n";
								$width = $img[0];
							} else {
								$width = $width + $img[0];
							}
					}
					$smileys .= "<img src='".IMAGES."smiley/".$smiley['smiley_image']."' alt='".$smiley['smiley_text']."' onClick=\"addText2('".$textarea."', '".$smiley['smiley_code']."', '');".(!empty($close) ? " overlayclose('$close');" : "")."\">\n";
				}
			}
	return $smileys;
}
echo"<style>
.sb-messages-wrapper .sb-messages {} 
.sb-messages-wrapper .nano-pane {
	background: #888;
	} 
.sb-messages-wrapper .nano-slider {
	background: #111;
	}
	</style>
	

<div class='panel'>
<div class='panel-title'><a href='#' target='blank'><s>ShoutBox - wersja mobilna</s></a></div>
<p class='sb-not-selectable'>".$locale['136'] ."</p>
<noscript><p style='color: red; font-weight: bold;'>Uwaga! JavaScript w Twojej przeglądarce jest wyłączony lub nieobsługiwany. Shoutbox nie będzie bez niego działał, a wiadomości nie będą automatycznie odświeżane.</p></noscript>";
openside("");
if (iMEMBER || $settings['guestposts'] == "1") {
	$result = dbquery("SELECT * FROM ".$db_prefix."shoutbox WHERE shout_id='".$id."'");
	if (dbrows($result)) {
		$sdata = dbarray($result);
	}
	if (isset($_POST['post_shout']) && isset($shout)) {
		$flood = false;
		if (iMEMBER) {
			$shout_name = $userdata['user_id'];
		} else {
			$shout_name = trim(stripinput($_POST['shout_name']));
			$shout_name = preg_replace("(^[0-9]*)", "", $shout_name);
			if (isNum($shout_name)) {
		  		$shout_name = "";
		  	}
		}
		$shout_message = str_replace("\n", " ", $_POST['shout_message']);
    	$shout_message = preg_replace("/^(.{255}).*$/", "$1", $shout_message);
    	$shout_message = str_replace("[", " [", $shout_message);
    	$shout_message = preg_replace("/([^\s]{21})/", "$1\n", $shout_message);
    	$shout_message = trim(stripinput(censorwords($shout_message)));
    	$shout_message = str_replace("\n", "<br>", $shout_message);
		if ($shout_name != "" && $shout_message != "") {
			$result = dbquery("SELECT MAX(shout_datestamp) AS last_shout FROM ".$db_prefix."shoutbox WHERE shout_ip='".USER_IP."'");
			if (!iSUPERADMIN && dbrows($result) > 0) {
				$data = dbarray($result);
				if ((time() - $data['last_shout']) < $settings['flood_interval']) {
					$flood = true;
					$result = dbquery("INSERT INTO ".$db_prefix."flood_control (flood_ip, flood_timestamp) VALUES ('".USER_IP."', '".time()."')");
					if (dbcount("(flood_ip)", "flood_control", "flood_ip='".USER_IP."'") > 4) {
						if (iMEMBER) $result = dbquery("UPDATE ".$db_prefix."users SET user_status='1' WHERE user_id='".$userdata['user_id']."'");
					}
				}
			}
			if (!$flood) {
				$lin = FUSION_QUERY;
				if ($shout =='add') {
					$result = dbquery("INSERT INTO ".$db_prefix."shoutbox (shout_name, shout_message, shout_datestamp, shout_ip) VALUES ('$shout_name', '$shout_message', '".time()."', '".USER_IP."')");
					if (EPS && iMEMBER) {
						$przydzial = dbarray(dbquery("SELECT point_ammount from ".DB_PREFIX."eps_points WHERE point_id='2'"));
						$result = dbquery("UPDATE ".DB_PREFIX."users SET points_normal=points_normal+".($przydzial['point_ammount'])." WHERE user_id='".$userdata['user_id']."'");
					}
				} elseif ($shout == 'edit' AND checkrights("S") OR SBX_EDIT_USER AND iMEMBER AND $sdata['shout_name'] == $userdata['user_id']) {
					$result = dbquery("UPDATE ".$db_prefix."shoutbox SET shout_message='$shout_message' WHERE shout_id='".$id."'");
				}
				$lin = str_replace("&amp;shout=$shout&amp;id=$id", '', $lin);
            $lin = str_replace("shout=$shout&amp;id=$id", '', $lin);

				if($lin != '') redirect(FUSION_SELF."?".$lin);
				else redirect(FUSION_SELF.$lin);
			}
		}
	} // end: if (isset($_POST['post_shout']) && isset($shout))
	if (isset($shout) AND isset($id) AND isNum($id)	AND checkrights("S")	OR isset($shout) AND isset($id)	AND isNum($id) AND !checkrights("S") AND iMEMBER AND $sdata['shout_name'] == $userdata['user_id'] AND SBX_EDIT_USER) {
		if ($shout == 'edit') {
			$shout_message = str_replace("<br>", "", $sdata['shout_message']);
			$shout_message = str_replace(" [", "[", $shout_message);
		} else if ($shout == 'delete') {
			if(EPS){
         	$sh_user = dbarray(dbquery("SELECT shout_name FROM ".$db_prefix."shoutbox WHERE shout_id='".$id."'"));
            if (isNUM($sh_user['shout_name'])) {
            	$przydzial = dbarray(dbquery("SELECT point_ammount from ".DB_PREFIX."eps_points WHERE point_id='2'"));
               $result = dbquery("UPDATE ".DB_PREFIX."users SET points_normal=points_normal-".($przydzial['point_ammount'])." WHERE user_id='".$sh_user['shout_name']."'");
			   $result = dbquery("DELETE from ".$db_prefix."shoutbox WHERE shout_id='".$id."'");
            }
         }
			$lin = FUSION_QUERY;
			$lin = str_replace("&amp;shout=$shout&amp;id=$id", '', $lin);
         $lin = str_replace("shout=$shout&amp;id=$id", '', $lin);

			if($lin != '') redirect(FUSION_SELF."?".$lin);
			else redirect(FUSION_SELF.$lin);
		}
	} else {
		$id = 0;
		$shout = 'add';
		$shout_message = '';
		$shout_name = '';
	}
	if (iMEMBER){
		$c_count = dbcount("(id)", "cautions", "user_id=".$userdata['user_id']);
		$caution_conf = dbarray(dbquery("SELECT * from ".$db_prefix."cautions_config"));
	}
	if ((iMEMBER)&&($c_count > $caution_conf['shoutbox'])) {
		echo"<span style='font-weight: bold;' class='kolor'>Pisanie wiadomości nie jest dla Ciebie dostępne. Powód:</span><br><span class='sb-text kolor'>".$caution_conf['shoutbox_info']."</span><br><br>";
	} else {
		if ($shout == 'add') {
     		echo "<form method='post' action='".FUSION_SELF.(FUSION_QUERY ? "?".FUSION_QUERY."&amp;shout=$shout&amp;id=$id" : "?shout=add&amp;id=$id")."'>";
		} else {
		   echo "<form method='post' action='".FUSION_SELF.(FUSION_QUERY ? "?".FUSION_QUERY : "?shout=$shout&amp;id=$id")."'>";
		}
		echo "<div class='sb-header'>";
					if (iGUEST) {
						echo $locale['121']."<br>
						<input type='text' name='shout_name' value='$shout_name' class='textbox' maxlength='30' style='width:140px;'><br>
						".$locale['122']."<br>\n";
					}
          
					echo"<span id='bled'></span>
					<textarea id='ShoutBox' class='sb-input'  name='shout_message' rows='4' class='textbox'>$shout_message</textarea>
					 <div class='sb-files-wrapper'></div>
					 <div><button type='submit' name='post_shout' id='sb_send' class='sb-btn'>Wyślij</button></div>";
					 ?>
					 <div class='sb-menu-buttons' ><button type='button' onclick="window.location.href='javascript:show_hide(ShowHide2)'" id='sb_emoticons'  class='sb-btn '>emotki</button><button type='button' onclick="window.location.href='javascript:show_hide(ShowHide7)'" class='sb-btn'>bbcode</button><button type='button' onclick="window.location.href='javascript:show_hide(ShowHide10)'" class='sb-btn '>opcje</button></div>
                     <?php
					 echo"<div id='ShowHide2'>";
							echo displaysmileys2("shout_message");
							echo "</div>
							<script type='text/javascript'>show_hide(ShowHide2)</script>
							<div id='ShowHide7'>
							<center>
								<input type='button' value='b' class='button' style='font-weight:bold;width:25px;' onClick=\"addText2('shout_message', '[b]', '[/b]');\">
								<input type='button' value='i' class='button' style='font-style:italic;width:25px;' onClick=\"addText2('shout_message', '[i]', '[/i]');\">
								<input type='button' value='u' class='button' style='text-decoration:underline;width:25px;' onClick=\"addText2('shout_message', '[u]', '[/u]');\">
							</center><br>
					</div>
					<script type='text/javascript'>show_hide(ShowHide7)</script>
					<div id='ShowHide10'>
					<span class='kolor'>opcję w krótce</span>
					</div>
					<script type='text/javascript'>show_hide(ShowHide10)</script>
	</form>\n";
		
	}
	echo "<br></div>";
} else {
  echo "<span class='kolor'><center>".$locale['125']."</center></span><br>\n";
} // end: if (iMEMBER || $settings['guestposts'] == "1")

$result = dbquery("
			SELECT * FROM ".DB_PREFIX."shoutbox
			LEFT JOIN ".DB_PREFIX."users ON ".DB_PREFIX."shoutbox.shout_name=".DB_PREFIX."users.user_id
			ORDER BY shout_datestamp DESC LIMIT 0,".$settings['numofshouts']
			);
echo "
		<link type='text/css' href='".INFUSIONS."shoutbox_panel/style/jquery.jscrollpane.css' rel='stylesheet' media='all' />
<script src='http://ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js' type='text/javascript'></script>
<script type='text/javascript' src='".INFUSIONS."shoutbox_panel/script/jquery.mousewheel.js'></script>
<script type='text/javascript' src='".INFUSIONS."shoutbox_panel/script/jquery.jscrollpane.js'></script>
			
			<script language='javascript' type='text/javascript'>
$(function()
{
	var api = $('.scroll-pane').jScrollPane(
		{
			showArrows:true,
			maintainPosition: false
		}
	).data('jsp');
	
	$('#do-ajax').bind(
		'click',
		function()
		{
			api.getContentPane().load(
				'ajax_content.html',
				function()
				{
					api.reinitialise();
				}
			);
			return false;
		}
	);
});
</script>
<style>
.scroll-pane
			{
				width: 100%;
				height: 200px;
				overflow: auto;
			}
</style>

		<div class='scroll-pane'>	";

if (dbrows($result) > 0) {
	$gouest_opt = dbarray(dbquery("SELECT * FROM ".$db_prefix."colors WHERE user_level=0"));
	while ($data = dbarray($result)) {
		
		echo "
		<span class='shoutboxname'>";
		$user = "<span style='color:#".$data['user_color']."'>".$data['user_prefix'].$data['user_name']."</span>";
		if ($data['user_name']) {
			echo "<a href='".BASEDIR.'profile.php?lookup='.$data['user_id']."' class='side'>$user</a><br>\n";
		} else {
			echo "<span style='color:#".$gouest_opt['user_color']."'>".$gouest_opt['user_prefix'].$data['shout_name']."</span>\n";
		}
      echo "</span></legend>";
		if (checkrights("S") AND $shout != "edit"){
      	echo "[ <a href='".FUSION_SELF.(FUSION_QUERY ? "?".FUSION_QUERY."&amp;shout=delete&amp;id=".$data['shout_id'] : "?shout=delete&amp;id=".$data['shout_id'])."'>".$locale['133']."</a> | <a href='".FUSION_SELF.(FUSION_QUERY ? "?".FUSION_QUERY."&amp;shout=edit&amp;id=".$data['shout_id'] : "?shout=edit&amp;id=".$data['shout_id'])."'>".$locale['134']."</a> ]<br><font style='font-style:italic;font-family:Tahoma;font-size:8;color:#777777;'>IP: ".$data['user_ip']."</font><br>";
      } elseif (SBX_EDIT_USER AND iMEMBER AND $data['shout_name'] == $userdata['user_id'] AND $id == "" AND !checkrights("S")) {
      	echo "[ <a href='".FUSION_SELF.(FUSION_QUERY ? "?".FUSION_QUERY."&amp;shout=edit&amp;id=".$data['shout_id'] : "?shout=edit&amp;id=".$data['shout_id'])."'>".$locale['134']."</a> ]<br>";
      } else {
      	echo "\n";
      }
		echo "
			<span class='shoutboxdate' style='font-size:8;'>
				".$locale['135']." ".showdate("shortdate", $data['shout_datestamp'])."
			</span><br>";
		$mes = str_replace(" [", "[", $data['shout_message']);
		$mes = nl2br(parseubb(parsesmileys($mes)));
		echo "<p><span class='shoutbox'>".$mes ."</span></p><br>\n"; 
	}
	echo"
	</div>";
} else {
      echo "<div align='left'>".$locale['127']."</div>\n";
} // end: if (dbrows($result) > 0) {
if (iMEMBER) {
	echo "
		<hr />
		<center>
			<img border='0' src='".THEME."images/bullet.gif'> 
			<a href='".INFUSIONS."shoutbox_panel/shoutbox_archive.php' class='side'>".$locale['126']."</a> 
			<img border='0' src='".THEME."images/bulletb.gif'>
		</center>\n";
} else {
	echo "<div align='left'></div>
	</div>";
}

closeside();
?>
