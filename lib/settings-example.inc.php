<?php
/***************************************************************************
	*                                         				                                
	*   This program is free software; you can redistribute it and/or modify  	
	*   it under the terms of the GNU General Public License as published by  
	*   the Free Software Foundation; either version 2 of the License, or	    	
	*   (at your option) any later version.
	*
	***************************************************************************/

/****************************************************************************

	Unicode Reminder メモ

	server specific settings

 ****************************************************************************/
 
	//Replace localhost to you own domain site	

 	//relative path to the root directory
	if (!isset($rootpath)) $rootpath = './';

	//default used language
	if (!isset($lang)) $lang = 'pl';
	
	//default used style
	if (!isset($style)) $style = 'stdstyle';

	//pagetitle
	if (!isset($pagetitle)) $pagetitle = 'Geocaching Opencaching Polska';

	//site name
	 $site_name = 'localhost';

	//id of the node 4 for local development
	 $oc_nodeid = 4;
	
	 //OC Waypoint for your site for example OX 
	 $GLOBALS['oc_waypoint'] = 'OP';
	
        //name of the cookie 
        $opt['cookie']['name'] = 'oc';
        $opt['cookie']['path'] = '/';
        $opt['cookie']['domain'] = '.localhost';
                                
        //name of the cookie
        if (!isset($cookiename)) $cookiename = 'oc';
        if (!isset($cookiepath)) $cookiepath = '/';
        if (!isset($cookiedomain)) $cookiedomain = '.locahost';

	// Coordinates hidden for not-logged-ins?
	global $hide_coords;
	$hide_coords = false;
	
	// scores range
	$MIN_SCORE = 0;
	$MAX_SCORE = 4;
		
	// display online users on footer pages off=0 on=1
	$onlineusers=1;
        // wlaczenie blokady liczby max zakladanych skrzynek typu owncache (by Marcin stryker)
	$GLOBALS['owncache_limit'] = '1';
	    
	
	//block register new cache before first find xx nuber caches value -1 off this feature
	$NEED_FIND_LIMIT=10;
	
	$NEED_APPROVE_LIMIT = 3;
	
	//Debug?
	if (!isset($debug_page)) $debug_page = false;
	$develwarning = '';
	
	//site in service? Set to false when doing bigger work on the database to prevent error's
	if (!isset($site_in_service)) $site_in_service = true;
	
	//if you are running this site on a other domain than staging.opencaching.de, you can set
	//this in private_db.inc.php, but don't forget the ending /
	$absolute_server_URI = 'http://localhost/';
	
	// EMail address of the sender
	if (!isset($emailaddr)) $emailaddr = 'noreply@localhost';
	
	// location for dynamically generated files
	$dynbasepath = '/var/www/ocpl-data/';
	$dynstylepath = $dynbasepath . 'tpl/stdstyle/html/';

	// location of cache images
	if (!isset($picdir)) $picdir = $dynbasepath . 'images/uploads';
	if (!isset($picurl)) $picurl = 'http://localhost/images/uploads';

	// Thumbsize
	$thumb_max_width = 175;
	$thumb_max_height = 175;
	// Small thumbsize
	$thumb2_max_width = 64;
	$thumb2_max_height = 64;

		// location of cache mp3 files
	if (!isset($mp3dir)) $mp3dir = $dynbasepath . 'mp3';
	if (!isset($mp3url)) $mp3url = 'http://localhost/mp3';

	// maximal size of mp3 for PodCache 5 Mb ?
	if (!isset($maxmp3size)) $maxmp3size = 5000000;
	
	// allowed extensions of images
	if (!isset($mp3extensions)) $mp3extensions = ';mp3;';	
	
	
	
	// default coordinates for cachemap, set to your country's center of gravity
	$country_coordinates = "52.5,19.2";
	// zoom at which your whole country/region is visible
	$default_country_zoom = 6;

	// Main page map parameters (customize as needed)
	$main_page_map_center_lat = 52.13;
	$main_page_map_center_lon = 19.20;
	$main_page_map_zoom = 5;
	$main_page_map_width = 250;
	$main_page_map_height = 260;

	// maximal size of images
	if (!isset($maxpicsize)) $maxpicsize = 152400;
	
	// allowed extensions of images
	if (!isset($picextensions)) $picextensions = ';jpg;jpeg;gif;png;';
	
	// news settings
	$use_news_approving = true;
	$news_approver_email = 'rr@localhost';
	
	//local database settings
	$dbpconnect = false;
	$dbserver = 'localhost';
	$dbname = 'ocpl';
	$dbusername = 'ocdbu';
	$dbpasswd = 'PassworD';
	$opt['db']['server'] = 'localhost';
	$opt['db']['name'] = 'ocpl';
        $opt['db']['username'] = 'ocdbu';
        $opt['db']['password'] = 'PassworD';
        

	$tmpdbname = 'test';

	// warnlevel for sql-execution
	$sql_errormail = 'rt@localhost';
	$sql_warntime = 1;

	// replacements for sql()
	$sql_replacements['db'] = $dbname;
	$sql_replacements['tmpdb'] = 'test';

	// safemode_zip-binary
	$safemode_zip = '/var/www/ocpl/bin/phpzip.php';
	$zip_basedir = $dynbasepath . 'download/zip/';
	$zip_wwwdir = '/download/zip/';

	// Your own Google map API key
	$googlemap_key = "";
	$googlemap_type = "G_MAP_TYPE"; // alternativ: _HYBRID_TYPE
	

	$dberrormail = 'rt@localhost';

	// user_id of admin who have more options than COG users to remove all logs or other more options in admin_users.php 
	$super_admin_id = '';

	$cachemap_mapper = "lib/mapper-okapi.php";

	//links to forum page on oc site
	$forum_url = 'http://forum.opencaching.pl';

	//links to wiki page on oc site
	$wiki_url  = 'http://wiki.opencaching.pl';
	$rules_url = 'http://wiki.opencaching.pl/index.php/Regulamin_OC_PL';
	$cache_params_url = 'http://wiki.opencaching.pl/index.php/Parametry_skrzynki';
	$rating_desc_url = 'http://wiki.opencaching.pl/index.php/Oceny_skrzynek';

	$contact_mail = 'ocpl (at) localhost';
	// E-mail address group of people from OC Team who solve problems, verify cache
	$octeam_email = 'cog@localhost';
	
	// signature of e-mails send by system
  	$octeamEmailsSignature = "Pozdrawiamy, Zespół www.opencaching.pl";
  
    	// watchlist config:
  	$watchlistMailfrom = 'watch@opencaching.pl';
  
    	// email of GeoKrety developer (used in GeoKretyApi.php for error notifications)
    	$geoKretyDeveloperEmailAddress = 'stefaniak@gmail.com';

	// New caches outside country where server is: 
	$SiteOutsideCountryString = 'poland_outside';
	$countryParamNewcacherestPhp = " 'PL' ";
	
	/* power Trail module switch and settings */
  
	  // true - swithed on; false - swithed off
	  $powerTrailModuleSwitchOn = true;
	  
	  // minimum cache count for power trail to be public displayed 
	  // (PT having less than $powerTrailMinimumCacheCount ) are visible only to owners.
	  $powerTrailMinimumCacheCount = array(
			'current' => 25,
			'old' => array (
				1 => array (
					'dateFrom' => '1970-01-01 01:00',
					'dateTo' => '2013-10-29 23:59:59',
					'limit' => 5,
				),
// if limit change in future, just uncomment and place here current limit and period of time				
//				2 => array (
//					'dateFrom' => '2013-10-30 00:00:00',
//					'dateTo' => '20??-??-?? 23:59:59',
//					'limit' => 25,
//				),
			),
	  );
	  
	  
	  // minimum cahes Found count of user, to alow user set new Power Trail
	  // user who found less than $powerTrailUserMinimumCacheFoundToSetNewPowerTrail can't create new PT
	  $powerTrailUserMinimumCacheFoundToSetNewPowerTrail = 500;
	  
	  // link to FAQ/info of power trail module
	  $powerTrailFaqLink = 'http://info.opencaching.pl/node/13';
	  
  /* end of power Trail module switch and settings */

		$BlogSwitchOn = true;	// enables/disables linkage to blog in index.php 

	// OC specific email addresses for international use.
	$mail_cog = 'cog@opencaching.pl';
	$mail_rt = 'rt@opencaching.pl';
	$mail_rr = 'rr@opencaching.pl';
	$mail_oc = 'ocpl@opencaching.pl';


	//Short sitename for international use.
	$short_sitename = 'OC PL';

	// Contact data definition START
	/*
		Possible array entries are listed below. All the entries are optional.
		+ groupName
			HTML header with a group name. Group name can be either raw, html code;
			or a reference to the translation file.
		+ emailAddress
			E-mail address, which will be printed just below the groupName.
		+ groupDescription 
			Group description is an actual text of the group, which is placed under the groupName 
			and e-mail. This entry can be in one of the following types/formats:
			  - an array - if so, each array entry is processed as one of those two types below;
			  - raw, html code;
			  - reference to the translation file.
		+ subgroup
			A nested array of the same structure. HTML headers for nested groups 
			are one level lower.
		+ other_keys
			They are used to substitute {other_keys} references in both groupName and
			groupDescription. Those keys do not propagate to subgroups.
	
	*/
	
	// Configuration for OC.PL contact page
	// Translated to Polish and English only :/
	$contactDataPL = array(
		array (
			'groupName' => 'contact_pl_about_title',
			'groupDescription' => array(
				'contact_pl_about_description_1',
				'contact_pl_about_description_2'
			)
		),
		array (
			'groupName' => 'OpenCaching PL Team',
			'subgroup' => array (
				array (
					'groupName' => 'Rada Rejsu',
					'groupDescription' => 'contact_pl_rr_description',
					'emailAddress' => 'rr at opencaching.pl',
					'link' => 'http://forum.opencaching.pl/viewtopic.php?f=19&t=6297'
				),
				array (
					'groupName' => 'Rada Techniczna',
					'groupDescription' => 'contact_pl_rt_description',
					'emailAddress' => 'rt at opencaching.pl',
					'link' => 'https://code.google.com/p/opencaching-pl/people/list'
				),
				array (
					'groupName' => 'Centrum Obsługi Geocachera',
					'groupDescription' => 'contact_pl_cog_description',
					'emailAddress' => 'cog at opencaching.pl',
					'link' => 'http://forum.opencaching.pl/viewtopic.php?f=19&t=6297'
				),
			),	
		),
		array(
			'groupName' => 'contact_pl_other_title',
			'groupDescription' => 'contact_pl_other_description'
		),
		array(
			'groupName' => 'contact_ocpl_title',
			'groupDescription' => array(
				'contact_ocpl_description_1',
				'contact_ocpl_description_2',
				'contact_ocpl_description_3',
			)
		)

	);

	// Configuration from OC.DE contact page
	// This is only a template, to be translated/updated for OC.NL
	$contactDataDE = array(
		array (
			'groupName' => 'Allgemeine Fragen zu Opencaching.de und zum Thema Geocaching',
			'groupDescription' => array(
				'Für Fragen rund um Opencaching und zum Thema Geocaching ist das <a href="http://wiki.opencaching.de/">Opencaching-Wiki</a> eine gute Anlaufstelle. Weitere Informationen zum Geocaching gibt es auf <a href="http://www.geocaching.de">www.geocaching.de</a>.',
				'Wenn du ein spezielles Problem hast und darauf keine Antwort findest, kannst du dir unter <a href="http://forum.opencaching-network.org">forum.opencaching-network.org</a> ein passendes Forum raussuchen und dich dort erkundigen.'
			)
		),
		array(
			'groupName' => 'Bedienung der Website, Anregungen und Kritik',
			'groupDescription' => 'Hierfür gibt es ein eigenes Unterforum auf <a href="http://forum.opencaching-network.org/index.php?board=33.0">forum.opencaching-network.org</a>. Dort findest du auch weitere Informationen, falls du in unserem Team mitmachen möchtest.'
		),
		array(
			'groupName' => 'Sonstiges',
			'groupDescription' => array(
				'Sollten die oben genannten Möglichkeiten nicht ausreichen oder die Betreiber von <i>opencaching.de</i> direkt kontaktiert werden, kannst du auch eine Email an <a href="mailto:contact@opencaching.de">contact@opencaching.de</a> schreiben.',
				'Bitte werde nicht ungeduldig wenn nicht sofort eine Antwort kommt, <i>opencaching.de</i> wird von Freiwilligen betreut, die leider nicht immer und sofort zur Verfügung stehen können.',
			)
		)

	);
	// 
	$contactData = $contactDataPL;
	// Contact data definition END
	
?>
