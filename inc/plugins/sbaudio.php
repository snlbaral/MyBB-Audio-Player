<?php
//disallow unauthorize access
if(!defined("IN_MYBB")) {
	die("You are not authorize to view this");
}

$plugins->add_hook('postbit', 'sbaudio_post');

//Plugin Information
function sbaudio_info()
{
	return array(
		'name' => 'MyBB Audio Player Plugin',
		'author' => 'Sunil Baral',
		'website' => 'https://github.com/snlbaral',
		'description' => 'This plugins convert html links inside defined [tag] tag into audio player',
		'version' => '1.0',
		'compatibility' => '18*',
		'guid' => '',
	);
}

//Activate Plugin
function sbaudio_activate()
{
	global $db, $mybb, $settings;

	//Admin CP Settings
	$sbaudio_group = array(
		'gid' => '',
		'name' => 'sbaudio',
		'title' => 'MyBB Audio Player Plugin',
		'description' => 'Settings for MyBB Audio Player',
		'disporder' => '1',
		'isdefault' =>  '0',
	);
	$db->insert_query('settinggroups',$sbaudio_group);
	$gid = $db->insert_id();

	//Enable or Disable
	$sbaudio_enable = array(
		'sid' => 'NULL',
		'name' => 'sbaudio_enable',
		'title' => 'Do you want to enable this plugin?',
		'description' => 'If you set this option to yes, this plugin will start working.',
		'optionscode' => 'yesno',
		'value' => '1',
		'disporder' => 1,
		'gid' => intval($gid),
	);

	$sbaudio_tag = array(
		'sid' => 'NULL',
		'name' => 'sbaudio_tag',
		'title' => 'Choose the BBCode Tag you want to use for this plugin.',
		'description' => 'This tag will be used to identify audio links inside [yourbelowgiventag]link[/yourbelowgiventag]',
		'optionscode' => 'text',
		'value' => 'sb',
		'disporder' => 1,
		'gid' => intval($gid),
	);

	$db->insert_query('settings',$sbaudio_enable);
	$db->insert_query('settings', $sbaudio_tag);
	rebuild_settings();

}

//Deactivate Plugin
function sbaudio_deactivate()
{
	global $db, $mybb, $settings;
	$db->query("DELETE from ".TABLE_PREFIX."settinggroups WHERE name IN ('sbaudio')");
	$db->query("DELETE from ".TABLE_PREFIX."settings WHERE name IN ('sbaudio_enable')");
	$db->query("DELETE from ".TABLE_PREFIX."settings WHERE name IN ('sbaudio_tag')");	
	rebuild_settings();
}

//Display Function
function sbaudio_post(&$post)
{
	global $db, $mybb, $templates, $settings;

	//Check if plugin is enabled
    if($settings['sbaudio_enable'] != 1)
    {
        return;
    }


	$tag = $settings['sbaudio_tag'];
	$default_tags = array('b','i','u','s','url','email','quote','code','img','color','size','font','align','list');
	//We are not interestd in conflicting with MyBB Default BBCode Tags.
	if(in_array($tag, $default_tags)) {
		return;
	}

	$str = $post['message'];
    $re = sprintf("/\[(%s)\](.+?)\[\/\\1\]/", preg_quote($tag));
    preg_match_all($re, $str, $matches);
    $matches = $matches[2];
	$matches_size = sizeof($matches);
	if($matches_size>0) {
		foreach ($matches as $match) {
			//Allowed Audio Tags
			if(strpos($match, "m4a") || strpos($match, "mp3") || strpos($match, "wav") || strpos($match, "aac") || strpos($match, "ogg") || strpos($match, "flac")) {
				$rand = rand();
				$title = basename($match);
				$post['message'] = str_replace("[".$tag."]".$match."[/".$tag."]", '<link rel="stylesheet" type="text/css" href="inc/plugins/MybbStuff/sbaudio/css/jplayer.pink.flag.css"><script type="text/javascript" src="jscripts/sbaudio.js"></script><div id="url" style="display:none">'.$match.'</div><div id="jquery_jplayer_'.$rand.'" class="jp-jplayer"></div><div id="jp_container_'.$rand.'" class="jp-audio" role="application" aria-label="media player"><div class="jp-type-single"><div class="jp-gui jp-interface"><div class="jp-volume-controls"><button class="jp-mute" role="button" tabindex="0">mute</button><button class="jp-volume-max" role="button" tabindex="0">max volume</button><div class="jp-volume-bar"><div class="jp-volume-bar-value"></div></div></div><div class="jp-controls-holder"><div class="jp-controls"><button class="jp-play" role="button" tabindex="0">play</button><button class="jp-stop" role="button" tabindex="0">stop</button></div><div class="jp-progress"><div class="jp-seek-bar"><div class="jp-play-bar"></div></div></div><div class="jp-current-time" role="timer" aria-label="time">&nbsp;</div><div class="jp-duration" role="timer" aria-label="duration">&nbsp;</div><div class="jp-toggles"><button class="jp-repeat" role="button" tabindex="0">repeat</button></div></div></div><div class="jp-details"><i class="fa fa-music"></i> &nbsp<div class="jp-title" style="display: inline-block;" aria-label="title">&nbsp;</div></div><div class="jp-no-solution"><span>Update Required</span>To play the media you will need to either update your browser to a recent version or update your <a href="https://get.adobe.com/flashplayer/" target="_blank">Flash plugin</a>.</div></div></div><script type="text/javascript">$(document).ready(function(){let url = "'.$match.'";let id = "'.$rand.'";let title = "'.$title.'";$("#jquery_jplayer_"+id).jPlayer({ready: function () {$(this).jPlayer("setMedia", {title: title,m4a: url,});},cssSelectorAncestor: "#jp_container_"+id,swfPath: "/js",supplied: "m4a, oga, mp3",useStateClassSkin: true,autoBlur: false,smoothPlayBar: true,keyEnabled: true,remainingDuration: true,toggleDuration: true});});</script>', $post['message']);
			}
		}
	}
}