<?php

use Utils\Database\XDb;
use Utils\Database\OcDb;
use Utils\Gis\Gis;
use lib\Objects\GeoCache\GeoCacheCommons;
use lib\Objects\GeoCache\GeoCacheLog;
use lib\Objects\GeoCache\GeoCache;
use lib\Objects\User\User;
use lib\Objects\GeoKret\GeoKretLog;
use lib\Controllers\MeritBadgeController;
use Utils\Generators\Uuid;
use lib\Controllers\LogEntryController;

/* todo:
  create and set up 4 template selector with wybor_WE wybor_NS.

/**
 * This function returns 1 if cache contains any geokret
 */
function isGeokretInCache($cacheid)
{

    $res = XDb::xSql(
        "SELECT wp_oc, wp_gc, wp_nc, wp_ge, wp_tc
        FROM caches WHERE cache_id = ? LIMIT 1", $cacheid);

    $cache_record = XDb::xFetchArray($res);

    // get cache waypoint
    $cache_wp = '';
    if ($cache_record['wp_oc'] != '')
        $cache_wp = $cache_record['wp_oc'];
    else if ($cache_record['wp_gc'] != '')
        $cache_wp = $cache_record['wp_gc'];
    else if ($cache_record['wp_nc'] != '')
        $cache_wp = $cache_record['wp_nc'];
    else if ($cache_record['wp_ge'] != '')
        $cache_wp = $cache_record['wp_ge'];
    else if ($cache_record['wp_tc'] != '')
        $cache_wp = $cache_record['wp_tc'];

    $gkNum = XDb::xMultiVariableQueryValue(
        "SELECT COUNT(*) FROM gk_item
        WHERE id IN (
            SELECT id FROM gk_item_waypoint
            WHERE wp = :1
            )
            AND stateid<>1 AND stateid<>4
            AND stateid <>5 AND typeid<>2", 0, $cache_wp);

    if($gkNum == 0){
        return 0;
    } else {
        return 1;
    }
}

//prepare the templates and include all neccessary
global $rootpath;
require_once('./lib/common.inc.php');


$no_tpl_build = false;
//Preprocessing
if ($error == false) {
    //cacheid
    $cache_id = 0;
    if (isset($_REQUEST['cacheid'])) {
        $cache_id = $_REQUEST['cacheid'];
    }

    //user logged in?
    if ($usr == false) {
        $target = urlencode(tpl_get_current_page());
        tpl_redirect('login.php?target=' . $target);
    } else {
        //set here the template to process
        $tplname = 'log_cache';
        $view->loadJquery();

        require($stylepath . '/log_cache.inc.php');
        require_once($rootpath . 'lib/caches.inc.php');
        require($stylepath . '/rating.inc.php');

        if (!isset($cache_user_id)) {
            $cache_user_id = 0;
        }

        $cachename = '';
        if ($cache_id != 0) {
            //get cachename
            $rs = XDb::xSql("SELECT * FROM `caches` WHERE `cache_id`= ? LIMIT 1", $cache_id);

            if ( $record = XDb::xFetchArray($rs) ) {
                $geoCache = new GeoCache();
                $geoCache->loadFromRow($record);
                $user = new User(['userId' => $_SESSION['user_id']]);

                // only OC Team member and the owner allowed to make logs to not published caches
                if ($record['user_id'] == $usr['userid'] || ($record['status'] != 5 && $record['status'] != 4 && $record['status'] != 6) || $usr['admin']) {
                    $cachename = htmlspecialchars($record['name'], ENT_COMPAT, 'UTF-8');
                    $tpl_subtitle = $cachename . ' - ';
                    $cache_user_id = $record['user_id'];
                    $use_log_pw = (($record['logpw'] == NULL) || ($record['logpw'] == '')) ? false : true;
                    if ($use_log_pw)
                        $log_pw = $record['logpw'];
                    $wp_gc = $record['wp_gc'];
                    $wp_nc = $record['wp_nc'];
                    $cache_type = $record['type'];
                }
                else {
                    $cache_id = 0;
                }
            } else {
                $cache_id = 0;
            }
        }

        if ($cache_id != 0) {
            $all_ok = false;

            if (isset($_SESSION["lastLogSendTime"]) && isset($_SESSION["lastLogDateTime"])) {
                if (!compareTime($_SESSION["lastLogSendTime"], "PT1H")) { //if last logging time is greater than one hour from now
                    $proposedDateTime = new DateTime("now"); // lastLogDateTime is overdue
                }
                else {
                    $proposedDateTime = $_SESSION["lastLogDateTime"];
                }
            }
            else {
                $proposedDateTime = new DateTime("now");
            }

            $log_text = isset($_POST['logtext']) ? ($_POST['logtext']) : '';
            $log_type = isset($_POST['logtype']) ? ($_POST['logtype'] + 0) : -2;
            $log_date_min = isset($_POST['logmin']) ? ($_POST['logmin'] + 0) : $proposedDateTime->format('i');
            $log_date_hour = isset($_POST['loghour']) ? ($_POST['loghour'] + 0) : $proposedDateTime->format('H');
            $log_date_day = isset($_POST['logday']) ? ($_POST['logday'] + 0) : $proposedDateTime->format('d');
            $log_date_month = isset($_POST['logmonth']) ? ($_POST['logmonth'] + 0) : $proposedDateTime->format('m');
            $log_date_year = isset($_POST['logyear']) ? ($_POST['logyear'] + 0) : $proposedDateTime->format('Y');
            $top_cache = isset($_POST['rating']) ? $_POST['rating'] + 0 : 0;

            // mobilne by Łza
            $wybor_NS = isset($_POST['wybor_NS']) ? $_POST['wybor_NS'] : 0;
            $wsp_NS_st = isset($_POST['wsp_NS_st']) ? $_POST['wsp_NS_st'] : null;
            $wsp_NS_min = isset($_POST['wsp_NS_min']) ? $_POST['wsp_NS_min'] : null;
            $wybor_WE = isset($_POST['wybor_WE']) ? $_POST['wybor_WE'] : 0;
            $wsp_WE_st = isset($_POST['wsp_WE_st']) ? $_POST['wsp_WE_st'] : null;
            $wsp_WE_min = isset($_POST['wsp_WE_min']) ? $_POST['wsp_WE_min'] : null;

            $is_top = XDb::xMultiVariableQueryValue(
                "SELECT COUNT(`cache_id`) FROM `cache_rating`
                WHERE `user_id`= :1 AND `cache_id`= :2 ", 0, $usr['userid'], $cache_id );

            // check if user has exceeded his top5% limit
            $user_founds = XDb::xMultiVariableQueryValue(
                "SELECT `founds_count` FROM `user` WHERE `user_id`=:1 LIMIT 1", 0, $usr['userid']);

            $user_tops = XDb::xMultiVariableQueryValue(
                "SELECT COUNT(`user_id`) FROM `cache_rating` WHERE `user_id`= :1 ", 0, $usr['userid']);

            if ($is_top == 0) { //not-yet-recommended

                if ( ($user_founds * GeoCacheCommons::RECOMENDATION_RATIO / 100) < 1) { // user doesn't have enough founds to recommend anything
                    $top_cache = 0;
                    $recommendationsNr = 100 / GeoCacheCommons::RECOMENDATION_RATIO - $user_founds;
                    $rating_msg = mb_ereg_replace('{recommendationsNr}', "$recommendationsNr", $rating_too_few_founds);

                } elseif ($user_tops < floor($user_founds * GeoCacheCommons::RECOMENDATION_RATIO / 100)) {
                    // this user can recommend this cache
                    if ($cache_user_id != $usr['userid']) {
                        if ($top_cache)
                            $rating_msg = mb_ereg_replace('{chk_sel}', ' checked', $rating_allowed . '<br />' . $rating_stat);
                        else
                            $rating_msg = mb_ereg_replace('{chk_sel}', '', $rating_allowed . '<br />' . $rating_stat);
                    }
                    else {
                        $rating_msg = mb_ereg_replace('{chk_dis}', ' disabled', $rating_own . '<br />' . $rating_stat);
                    }
                    $rating_msg = mb_ereg_replace('{max}', floor($user_founds * GeoCacheCommons::RECOMENDATION_RATIO / 100), $rating_msg);
                    $rating_msg = mb_ereg_replace('{curr}', $user_tops, $rating_msg);
                } else {
                    // user needs more caches for next recomendation
                    $top_cache = 0;
                    $recommendationsNr = ((1+$user_tops) * 100 / GeoCacheCommons::RECOMENDATION_RATIO ) - $user_founds;
                    $rating_msg = mb_ereg_replace('{recommendationsNr}', "$recommendationsNr", $rating_too_few_founds);

                    $rating_msg .= '<br />' . $rating_maxreached;
                }
            } else {
                if ($cache_user_id != $usr['userid']) {
                    $rating_msg = mb_ereg_replace('{chk_sel}', ' checked', $rating_allowed . '<br />' . $rating_stat);
                } else {
                    $rating_msg = mb_ereg_replace('{chk_dis}', ' disabled', $rating_own . '<br />' . $rating_stat);
                }
                $rating_msg = mb_ereg_replace('{max}', floor($user_founds * GeoCacheCommons::RECOMENDATION_RATIO / 100), $rating_msg);
                $rating_msg = mb_ereg_replace('{curr}', $user_tops, $rating_msg);
            }

            if ($cache_type != 6) {
                tpl_set_var('rating_message', mb_ereg_replace('{rating_msg}', $rating_msg, $rating_tpl));
            } else {
                tpl_set_var('rating_message', "");
            }

            $is_scored_query = XDb::xMultiVariableQueryValue(
                "SELECT count(*) FROM scores WHERE user_id= :1 AND cache_id= :2",
                0, $usr['userid'], $cache_id);

            if ( $is_scored_query == 0 && $usr['userid'] != $record['user_id']) {
                $color_table = array("#DD0000", "#F06464", "#DD7700", "#77CC00", "#00DD00");

                $score = '';
                $line_cnt = 0;

                for ($score_radio = 1 /*$MIN_SCORE*/; $score_radio <= 5 /*$MAX_SCORE*/; $score_radio++) {

                    if (($line_cnt == 2)) {
                        $break_line = "<br>";
                        $break_line = "";
                    } else {
                        $break_line = "";
                    };
                    if (isset($_POST['r']) && $score_radio == $_POST['r'])
                        $checked = ' checked="true"';
                    else
                        $checked = "";

                    $score.= '
                        <label><input type="radio" style="vertical-align: top" name="r" id="r' . $line_cnt . '" value="' . $score_radio . '" onclick="clear_no_score ();"' . $checked . '><b><span style="color:' . $color_table[$line_cnt]. '" id="score_lbl_' . $line_cnt . '">' . ucfirst(tr(GeoCacheCommons::CacheRatingTranslationKey($score_radio))) . '</span></b></label>&nbsp;&nbsp;' . $break_line;
                    $line_cnt++;
                }

                if (isset($_POST['r']) && $_POST['r'] == -10) {
                    $checked = ' checked="true"';
                } else {
                    $checked = "";
                }

                $score .= '<br><label><input type="radio" style="vertical-align: top" name="r" id="r' . $line_cnt . '" value="-10"' . $checked . ' onclick="encor_no_score ();" /><span id="score_lbl_' . $line_cnt . '">' . tr('do_not_rate') . '</span></label>';

                $score_header = tr('rate_cache');
                if ($log_type == 1) {
                    $display = "table-row";
                } else {
                    $display = "none";
                }
            }
            else {
                $score = "";
                $score_header = "";
                $display = "none";
            }
            tpl_set_var('score', $score);
            tpl_set_var('score_header', $score_header);
            tpl_set_var('display', $display);
            tpl_set_var('score_note_innitial', tr('log_score_note_innitial'));
            tpl_set_var('score_note_thanks', tr('log_score_note_thanks'));
            tpl_set_var('score_note_encorage', tr('log_score_note_encorage'));
            tpl_set_var('Do_reset_logform', tr('Do_reset_logform'));
            tpl_set_var('log_reset_button', tr('log_reset_button'));

            // check if geokret is in this cache
            if (isGeokretInCache($cache_id)) {
                tpl_set_var('log_geokret', "<br /><img src=\"images/gk.png\" class=\"icon16\" alt=\"\" title=\"GeoKrety\" align=\"middle\" />&nbsp;<b>" . tr('geokret_log') . " <a href='http://geokrety.org/ruchy.php'>geokrety.org</a></b>");
            } else
                tpl_set_var('log_geokret', "");

            /* GeoKretApi selector for logging Geokrets using GeoKretyApi */
            $dbConWpt = OcDb::instance();
            $s = $dbConWpt->paramQuery(
                "SELECT `secid` FROM `GeoKretyAPI` WHERE `userID` =:user_id LIMIT 1",
                array('user_id' => array('value' => $usr['userid'], 'data_type' => 'integer')));

            if ( $databaseResponse = $dbConWpt->dbResultFetchOneRowOnly($s) ) {

                tpl_set_var('GeoKretyApiNotConfigured', 'none');
                tpl_set_var('GeoKretyApiConfigured', 'block');

                $secid = $databaseResponse['secid'];

                $rs = $dbConWpt->paramQuery(
                    "SELECT `wp_oc` FROM `caches` WHERE `cache_id` = :cache_id LIMIT 1",
                    array('cache_id' => array('value' => $cache_id, 'data_type' => 'integer')));

                $cwpt = $dbConWpt->dbResultFetchOneRowOnly($rs);
                $cache_waypt = $cwpt['wp_oc'];

                $GeoKretSelector = new GeoKretyApi($secid, $cache_waypt);
                $GKSelect = $GeoKretSelector->MakeGeokretSelector($cachename);
                $GKSelect2 = $GeoKretSelector->MakeGeokretInCacheSelector($cachename);

                tpl_set_var('GeoKretApiSelector', $GKSelect);
                tpl_set_var('GeoKretApiSelector2', $GKSelect2);
            } else {
                tpl_set_var('GeoKretyApiNotConfigured', 'block');
                tpl_set_var('GeoKretyApiConfigured', 'none');
                tpl_set_var('GeoKretApiSelector', '');
            }


            if (isset($_POST['submit']) && !isset($_POST['version2'])) {
                $_POST['submitform'] = $_POST['submit'];
                $log_text = iconv("ISO-8859-1", "UTF-8", $log_text);
            }


            // check input
            require_once($rootpath . 'lib/class.inputfilter.php');
            $myFilter = new InputFilter($allowedtags, $allowedattr, 0, 0, 1);
            $log_text = $myFilter->process($log_text);


            //setting tpl messages if they should be not visible.
            tpl_set_var('lat_message', '');
            tpl_set_var('lon_message', '');

            //validate data
            if (is_numeric($log_date_month) && is_numeric($log_date_day) && is_numeric($log_date_year) && is_numeric($log_date_hour) && is_numeric($log_date_min)) {
                $date_not_ok = (checkdate($log_date_month, $log_date_day, $log_date_year) == false || $log_date_hour < 0 || $log_date_hour > 23 || $log_date_min < 0 || $log_date_min > 60);
                if ($date_not_ok == false) {
                    if (isset($_POST['submitform'])) {
                        if (mktime($log_date_hour, $log_date_min, 0, $log_date_month, $log_date_day, $log_date_year) >= time()) {
                            $date_not_ok = true;
                        } else {
                            $date_not_ok = false;
                        }
                    }
                }
            } else {
                $date_not_ok = true;
            }

            if ($cache_type == 6) { // (type 6 - Event cache)
                switch ($log_type) {
                    case 1:
                    case 2:
                        $logtype_not_ok = true;
                        break;
                    default:
                        $logtype_not_ok = false;
                        break;
                }
            } else {
                switch ($log_type) {
                    case 7:
                    case 8:
                        $logtype_not_ok = true;
                        break;
                    default:
                        $logtype_not_ok = false;
                        break;
                }
            }

            if ($log_type < 0)
                $logtype_not_ok = true;


            if ($log_type == 4) {
                //warring: if coords are wrong, return true (this is not my idea...)
                $coords_not_ok = validate_coords($wsp_NS_st, $wsp_NS_min, $wsp_WE_st, $wsp_WE_min, $wybor_WE, $wybor_NS, tr('lxg07'));
            } else {
                $coords_not_ok = false;
            }


            // not a found log? then ignore the rating
            $founds = XDb::xMultiVariableQueryValue(
                "SELECT count(*) as founds FROM `cache_logs`
                WHERE `deleted`=0 AND user_id= :1 AND cache_id= :2 AND type='1'",
                0, $usr['userid'], $cache_id);

            if ( $founds == 0)
                if ($log_type != 1 && $log_type != 7 /* && $log_type != 3 */) {
                    $top_cache = 0;
                }

            $pw_not_ok = false;
            if (isset($_POST['submitform'])) {
                $all_ok = ($date_not_ok == false) && ($logtype_not_ok == false) && ($coords_not_ok == false);

                if (($all_ok) && ($use_log_pw) && ($log_type == 1 || $log_type == 7)) {
                    if (isset($_POST['log_pw'])) {
                        if (mb_strtolower($log_pw) != mb_strtolower($_POST['log_pw'])) {
                            $pw_not_ok = true;
                            $all_ok = false;
                        }
                    } else {
                        $pw_not_ok = true;
                        $all_ok = false;
                    }
                }
            }
            $mark_as_rated = false;
            if (isset($_POST['submitform']) && ($log_type == 1 || $log_type == 7)) {

                if (!isset($_POST['r'])) {
                    $_POST['r'] = -15;
                };

                // fix
                if ($log_type == 7 && $usr['userid'] == $record['user_id']) {
                    $_POST['r'] = -10;
                }
                if ($_POST['r'] != -10 && $_POST['r'] != -15) {
                    $_POST['r'] = GeoCache::ScoreFromRatingNum(intval($_POST['r'])); // convert ratingNum to Score
                    $mark_as_rated = true;
                }

                if ($_POST['r'] == -10 || $_POST['r'] == -15 || ($_POST['r'] >= -3 && $_POST['r'] <= 3)) {
                    $score_not_ok = false;
                } else {
                    $score_not_ok = true;
                    $all_ok = false;
                }
            } else {
                $score_not_ok = false;
            }
            if (isset($_POST['submitform']) && ($all_ok == true)) {
                if (isset($_POST['r']) && $_POST['r'] >= -3 && $_POST['r'] <= 3 && $mark_as_rated == true) { //mark_as_rated to avoid possbility to set rate to 0,1,2 then change to Comment log type and actually give score (what could take place before!)!
                    // oceniono skrzynkę

                    $is_scored_query = XDb::xMultiVariableQueryValue(
                        "SELECT count(*) FROM scores WHERE user_id= :1 AND cache_id= :2 ",
                        -1, $usr['userid'], $cache_id);

                    if ( $is_scored_query == 0 && $usr['userid'] != $record['user_id']) {

                        XDb::xSql(
                            "UPDATE caches SET score=(score*votes+" . XDb::xEscape(floatval($_POST['r'])) . ")/(votes+1), votes=votes+1
                            WHERE cache_id= ?", $cache_id);

                        XDb::xSql(
                            "INSERT INTO scores (user_id, cache_id, score) VALUES( ? , ? , ? )",
                            $usr['userid'], $cache_id, $_POST['r']);
                    }
                } else {
                    // nie wybrano opcji oceny
                }
                $log_date = date('Y-m-d H:i:s', mktime($log_date_hour, $log_date_min, 0, $log_date_month, $log_date_day, $log_date_year));
                $log_uuid = Uuid::create();

                $logDateTime = new DateTime($log_date);
                if (!compareTime($logDateTime, "PT1H")) { //if logging time is older then now-one_hour
                    $_SESSION["lastLogDateTime"] = $logDateTime; //we store the time
                    $_SESSION["lastLogSendTime"] = new DateTime("now");
                }
                else {
                    unset($_SESSION["lastLogSendTime"]); //next time we log with "now" datetime
                    unset($_SESSION["lastLogDateTime"]);
                }

                //add logentry to db
                if ($log_type < 0) {
                    // nie wybrano typu logu
                }
                // if comment is empty, then do not insert data into db
                elseif (!($log_type == 3 && $log_text == "")) {
                    if ($log_type == 1) {
                        /* GeoKretyApi: call method logging selected Geokrets  (by Łza) */
                        processGeoKrety($logDateTime, $user, $geoCache);

                        $text_html = 2;  // see https://github.com/opencaching/opencaching-pl/issues/1218
                        $text_htmledit = 1;

                        // This query INSERT cache_log entry ONLY IF such entry NOT EXISTS
                        XDb::xSql(
                            "INSERT INTO `cache_logs` (
                                `cache_id`, `user_id`, `type`, `date`, `text`,
                                `text_html`, `text_htmledit`, `date_created`, `last_modified`,
                                `uuid`, `node`)
                            SELECT ?, ?, ?, ?, ?, ? ,? ,NOW(), NOW(), ?, ?
                            FROM  `cache_logs`
                            WHERE NOT EXISTS (
                                SELECT * FROM `cache_logs`
                                WHERE `type`=1 AND `user_id` = ?
                                    AND `cache_id` = ?
                                    AND `deleted` = '0')
                            LIMIT 1",
                                $cache_id, $usr['userid'], $log_type, $log_date, $log_text,
                                $text_html, $text_htmledit, $log_uuid, $oc_nodeid, $usr['userid'], $cache_id
                            );

                    } else {
                        XDb::xSql(
                            "INSERT INTO `cache_logs` (`cache_id`, `user_id`, `type`, `date`, `text`, `text_html`,
                                         `text_htmledit`, `date_created`, `last_modified`, `uuid`, `node`)
                            VALUES ( ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), ?, ?)",
                            $cache_id, $usr['userid'], $log_type, $log_date, $log_text, 2, 1, $log_uuid, $oc_nodeid);
                    }

                    // insert to database.
                    if ($log_type == GeoCacheLog::LOGTYPE_MOVED &&
                            ($cache_type == GeoCache::TYPE_MOVING || $cache_type == GeoCache::TYPE_OWNCACHE)
                        ) {

                        ini_set('display_errors', 1);
                        // error_reporting(E_ALL);
                        // id of last SQL entry
                        $last_id_4_mobile_moved = XDb::xLastInsertId();

                        // converting from HH MM.MMM to DD.DDDDDD
                        $wspolrzedneNS = $wsp_NS_st + round($wsp_NS_min, 3) / 60;
                        if ($wybor_NS == 'S')
                            $wspolrzedneNS = -$wspolrzedneNS;
                        $wspolrzedneWE = $wsp_WE_st + round($wsp_WE_min, 3) / 60;
                        if ($wybor_WE == 'W')
                            $wspolrzedneWE = -$wspolrzedneWE;

                        // if it is first log "cache mooved" then move start coordinates from table caches
                        // to table cache_moved and create log type cache_moved, witch description
                        // "depart point" or something like this.

                        $is_any_cache_movedlog = XDb::xMultiVariableQueryValue(
                            "SELECT COUNT(*) FROM `cache_moved` WHERE `cache_id` = :1 ", 0, $cache_id);

                        if ($is_any_cache_movedlog == 0) {
                            $tmp_move_query = XDb::xSql(
                                "SELECT `user_id`, `longitude`, `latitude`, `date_hidden` FROM `caches` WHERE `cache_id` = ? ", $cache_id);
                            $tmp_move_data = XDb::xFetchArray($tmp_move_query);

                            // create initial log in cache_logs and copy coords to table caches
                            $init_log_desc = tr('log_mobile_init');
                            $init_log_latitude = $tmp_move_data['latitude'];
                            $init_log_longitude = $tmp_move_data['longitude'];
                            $init_log_userID = $tmp_move_data['user_id'];
                            $init_log_date = $tmp_move_data['date_hidden'];

                            $init_log_uuid = Uuid::create();

                            XDb::xSql(
                                "INSERT INTO `cache_logs` (
                                    `id`, `cache_id`, `user_id`, `type`, `date`, `text`, `text_html`, `text_htmledit`,
                                    `date_created`, `last_modified`, `uuid`, `node`)
                                VALUES ('', ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), ?, ?)",
                                $cache_id, $init_log_userID, 4, $init_log_date, $init_log_desc, 0, 0, $init_log_uuid, $oc_nodeid);

                            // print $init_log_longitude; exit;
                            XDb::xSql(
                                "INSERT INTO `cache_moved`(
                                    `cache_id`, `user_id`, `log_id`, `date`, `longitude`, `latitude`, `km`)
                                VALUES ( ?, ?, LAST_INSERT_ID(), ?, ?, ?, '0')",
                                $cache_id, $init_log_userID, $init_log_date, $init_log_longitude, $init_log_latitude);

                        }

                        // insert into table cache_moved
                        XDb::xSql(
                            "INSERT INTO `cache_moved`(`cache_id`, `user_id`, `log_id`, `date`, `longitude`, `latitude`,`km`)
                            VALUES (?, ?, ?, ?, ?, ?, 0)",
                            $cache_id, $usr['userid'], $last_id_4_mobile_moved, $log_date, $wspolrzedneWE, $wspolrzedneNS);
                        LogEntryController::recalculateMobileMovesByCacheId($cache_id);
                    }
                    // mobilne by Łza - koniec
                    //inc cache stat and "last found"
                    $rs = XDb::xSql(
                        "SELECT `founds`, `notfounds`, `notes`, `last_found` FROM `caches`
                        WHERE `cache_id`= ? ", $cache_id );

                    $record = XDb::xFetchArray($rs);
                    $last_found = '';
                    if ($log_type == 1 || $log_type == 7) {
                        $dlog_date = mktime($log_date_hour, $log_date_min, 0, $log_date_month, $log_date_day, $log_date_year);
                        if ($record['last_found'] == NULL) {
                            $last_found = ', `last_found`=\'' . XDb::xEscape(date('Y-m-d H:i:s', $dlog_date)) . '\'';
                        } elseif (strtotime($record['last_found']) < $dlog_date) {
                            $last_found = ', `last_found`=\'' . XDb::xEscape(date('Y-m-d H:i:s', $dlog_date)) . '\'';
                        }
                    }
                    if ($log_type == 1 || $log_type == 2 || $log_type == 3 || $log_type == 7 || $log_type == 8) {
                        recalculateCacheStats($cache_id, $cache_type, $last_found);
                    }

                    //inc user stat
                    $rs = XDb::xSql(
                        "SELECT `log_notes_count`, `founds_count`, `notfounds_count` FROM `user`
                        WHERE `user_id`= ? ", $usr['userid']);
                    $record = XDb::xFetchArray($rs);

                    if ($log_type == 1 || $log_type == 7) {
                        XDb::xSql("UPDATE `user` SET founds_count=founds_count+1  WHERE `user_id`= ? ", $usr['userid']);
                    } elseif ($log_type == 2) {
                        XDb::xSql("UPDATE `user` SET notfounds_count=notfounds_count+1 WHERE `user_id`= ? ", $usr['userid']);
                    } elseif ($log_type == 3) {
                        XDb::xSql("UPDATE `user` SET log_notes_count=log_notes_count+1 WHERE `user_id`= ? ", $usr['userid']);
                    }

                    // update cache_status
                    $cache_status = XDb::xMultiVariableQueryValue(
                        "SELECT `log_types`.`cache_status` FROM `log_types` WHERE `id`= :1 LIMIT 1", 0, $log_type);

                        if ($cache_status != 0) {
                            $rs = XDb::xSql(
                                "UPDATE `caches` SET `last_modified`=NOW(), `status`= ?
                                WHERE `cache_id`= ? ", $cache_status, $cache_id);
                        }

                    // update top-list
                    if ($log_type == 1 || $log_type == 3) {
                        if ($top_cache == 1)
                            XDb::xSql("INSERT IGNORE INTO `cache_rating` (`user_id`, `cache_id`) VALUES(?, ?)", $usr['userid'], $cache_id);
                        else
                            XDb::xSql("DELETE FROM `cache_rating` WHERE `user_id`=? AND `cache_id`=?", $usr['userid'], $cache_id);
                    }

                    //call eventhandler
                    require_once($rootpath . 'lib/eventhandler.inc.php');
                    event_new_log($cache_id, $usr['userid'] + 0);
                }

                $badgetParam = "";

                if ($config['meritBadges']){
                    if ($log_type == GeoCacheLog::LOGTYPE_FOUNDIT ||
                        $log_type == GeoCacheLog::LOGTYPE_ATTENDED ){

                        $ctrlMeritBadge = new MeritBadgeController;
                        
                        $changedLevelBadgesIds = $ctrlMeritBadge->updateTriggerLogCache($cache_id, $usr['userid']);
                        $titledIds= $ctrlMeritBadge->updateTriggerTitledCache($cache_id, $usr['userid']);
                        
                        if ( $changedLevelBadgesIds != "" && $titledIds!= "")
                            $changedLevelBadgesIds .= ",";

                        $changedLevelBadgesIds .= $titledIds;
                        
                        if ( $changedLevelBadgesIds != "" )
                            $badgetParam = "&badgesPopupFor=" . $changedLevelBadgesIds;
                        
                        $ctrlMeritBadge->updateTriggerRecommendationAuthor($cache_id);
                    }
                }
                //redirect to viewcache
                $no_tpl_build = true;
                tpl_redirect('viewcache.php?cacheid=' . $cache_id . $badgetParam);
            }
            else {

                $founds = XDb::xMultiVariableQueryValue(
                    "SELECT count(*) as founds FROM `cache_logs`
                    WHERE `deleted`=0 AND user_id= :1 AND cache_id= :2
                        AND type = '1'", 0, $usr['userid'], $cache_id);

                $rs = XDb::xSql("SELECT status, type FROM `caches` WHERE cache_id= ? LIMIT 1", $cache_id);
                $res2 = XDb::xFetchArray($rs);

                $db = OcDb::instance();
                $s = $db->multiVariableQuery(
                    "SELECT count(*) as eventAttended FROM `cache_logs`
                    WHERE `deleted`=0 AND user_id=:1 AND cache_id=:2 AND type = '7'",
                    $usr['userid'], $cache_id);

                $eventAttended = $db->dbResultFetchOneRowOnly($s);

                /*                 * **************
                 * build logtypeoptions
                 *
                 * value  = Info
                 *
                 * -2=  Chose log type;
                  1 = Found;
                  2 = Didn't found';
                  3 = Write a note;
                  4 = Moved;
                  5 = Needs maintenace;
                  6 = Made service;
                  7 = Attended;
                  8 = Will attend;
                  9 = Archived;
                  10 = Ready to find;
                  11 = Temporarily unavailable;
                  12 = OC Team Comment;
                 *
                 *
                 * cache types: $res2['type' ]
                 * 1    Other
                 * 2    Trad.
                 * 3    Multi
                 * 4    Virt.
                 * 5    ICam.
                 * 6    Event
                 * 7    Quiz
                 * 8    Moving
                 * 9    Podcast
                 * 10   own-cache
                 *
                 * cache statuses:  $res2['status']
                 *  1   Ready for search
                 *  2   Temporarily unavailable
                 *  3   Archived
                 *  4   Hidden by approvers to check
                 *  5   Not yet available
                 *  6   Blocked by COG
                 *
                 */
                $logtypeoptions = '';

                // setting selector neutral
                if ($log_type < 0) {
                    //-2 = Chose log type
                    if ($res2['status'] != 4)
                        $logtypeoptions .= '<option value="-2" selected="selected" disabled="disabled">' . tr('wybrac_log') . '</option>';
                    tpl_set_var('display', "none");
                }

                foreach ($log_types AS $type) {
                    // do not allow 'finding' or 'not finding' own or archived cache (events can be logged) $res2['status'] == 2 || $res2['status'] == 3

                    if ($res2['type'] != 6 && ($usr['userid'] == $cache_user_id || $founds > 0 || $res2['status'] == 4 || $res2['status'] == 6)) {
                        //3 = Write a note;
                        if ($usr['admin'] == true && $res2['status'] == 4)
                            $logtypeoptions .= '<option selected="selected" value="3">' . tr('lxg08') . '</option>' . "\n";
                        else
                            $logtypeoptions .= '<option value="3">' . tr('lxg08') . '</option>' . "\n";

                        //4 = Moved
                        if ($res2['type'] == 8 || $res2['type'] == 10) {
                            $logtypeoptions .= '<option value="4">' . tr('lxg09') . '</option>' . "\n";
                        }

                        //5 = Needs maintenace
                        if ($usr['userid'] != $cache_user_id) {
                            $logtypeoptions .= '<option value="5">' . tr('lxg10') . '</option>' . "\n";
                        }
                        $logtypeoptions .= '<option value="6">' . tr('made_service') . '</option>' . "\n";

                        //12 = OC Team Comment
                        if ($usr['admin'] == true) {
                            $logtypeoptions .= '<option value="12">' . tr('lxg11') . '</option>' . "\n";
                        }

                        // service log by Łza
                        // if curently logged user is a cache owner and cache status is "avilable"
                        // then add log type option "temp. unavailable";
                        //11 = Temporarily unavailable
                        if ($usr['userid'] == $cache_user_id && $res2['status'] == 1) {
                            $logtypeoptions .= '<option value="11">' . tr("log_type_temp_unavailable") . '</option>' . "\n";
                        }
                        // if curently logged user is a cache owner and cache status is "temp. unavailable"
                        // then add log type option "avilable"
                        // 10 = Ready to find
                        if (($usr['userid'] == $cache_user_id) && ($res2['status'] == 2 )) {
                            $logtypeoptions .= '<option value="10">' . tr("log_type_available") . '</option>' . "\n";
                        }
                        break;
                    }



                    // skip if permission=O and not owner
                    if ($type['permission'] == 'O' && $usr['userid'] != $cache_user_id && $type['permission'])
                        continue;


                    // if virtual, webcam or own = archived -> allow only comment log type
                    if (($cache_type == 4 || $cache_type == 5 || $cache_type == 10) && $res2['status'] == 3) {
                        if ($type['id'] != 3) {
                            continue;
                        }
                    }

                    if ($cache_type == 6) {
                        // if user logged event as attended before, do not display logtype 'attended'
                        if ($eventAttended['eventAttended'] == 1 && $type['id'] == 7)
                            continue;
                        if ($usr['admin']) {
                            if ($type['id'] == 1 || $type['id'] == 2 || $type['id'] == 4 || $type['id'] == 5 || $type['id'] == 9 || $type['id'] == 10 || $type['id'] == 11) {
                                continue;
                            }
                        } else {
                            if ($type['id'] == 1 || $type['id'] == 2 || $type['id'] == 4 || $type['id'] == 5 || $type['id'] == 9 || $type['id'] == 10 || $type['id'] == 11 || $type['id'] == 12) {
                                continue;
                            }
                        }
                    } else {
                        if ($cache_type == 8) {
                            if ($usr['admin']) {
                                // skip will attend/attended if the cache no event
                                if ($type['id'] == 7 || $type['id'] == 8 || $type['id'] == 9 || $type['id'] == 10 || $type['id'] == 11) {
                                    continue;
                                }
                            } else {
                                if ($type['id'] == 7 || $type['id'] == 8 || $type['id'] == 9 || $type['id'] == 10 || $type['id'] == 11 || $type['id'] == 12) {
                                    continue;
                                }
                            }
                        } else {
                            // skip will attend/attended/Moved  if the cache no event and Mobile
                            if ($usr['admin']) {
                                if ($type['id'] == 4 || $type['id'] == 7 || $type['id'] == 8 || $type['id'] == 9 || $type['id'] == 10 || $type['id'] == 11) {
                                    continue;
                                }
                            } else {
                                if ($type['id'] == 4 || $type['id'] == 7 || $type['id'] == 8 || $type['id'] == 9 || $type['id'] == 10 || $type['id'] == 11 || $type['id'] == 12) {
                                    continue;
                                }
                            }
                        }
                    }

                    if (isset($type[$lang])){
                        $lang_db = $lang;
                    } else {
                        $lang_db = "en";
                    }

                    if ($type['id'] == $log_type) {
                        $logtypeoptions .= '<option value="' . $type['id'] . '" selected="selected">' . htmlspecialchars($type[$lang_db], ENT_COMPAT, 'UTF-8') . '</option>' . "\n";
                    } else {
                        $logtypeoptions .= '<option value="' . $type['id'] . '">' . htmlspecialchars($type[$lang_db], ENT_COMPAT, 'UTF-8') . '</option>' . "\n";
                    }
                }


                //set tpl vars
                tpl_set_var('cachename', $cachename); //TODO: do we need htmlspecialchars here ?!
                tpl_set_var('cacheid', htmlspecialchars($cache_id, ENT_COMPAT, 'UTF-8'));
                tpl_set_var('logmin', htmlspecialchars($log_date_min, ENT_COMPAT, 'UTF-8'));
                tpl_set_var('loghour', htmlspecialchars($log_date_hour, ENT_COMPAT, 'UTF-8'));
                tpl_set_var('logday', htmlspecialchars($log_date_day, ENT_COMPAT, 'UTF-8'));
                tpl_set_var('logmonth', htmlspecialchars($log_date_month, ENT_COMPAT, 'UTF-8'));
                tpl_set_var('logyear', htmlspecialchars($log_date_year, ENT_COMPAT, 'UTF-8'));
                tpl_set_var('logtypeoptions', $logtypeoptions);
                tpl_set_var('reset', tr('reset'));
                tpl_set_var('submit', $submit);
                tpl_set_var('date_message', '');
                tpl_set_var('top_cache', $top_cache);
                tpl_set_var('bodyMod', ' onload="chkMoved()"');

                tpl_set_var('wsp_NS_st', $wsp_NS_st);
                tpl_set_var('wsp_NS_min', $wsp_NS_min);
                tpl_set_var('wsp_WE_st', $wsp_WE_st);
                tpl_set_var('wsp_WE_min', $wsp_WE_min);
                tpl_set_var('$wybor_WE', $wybor_WE);
                tpl_set_var('$wybor_NS', $wybor_NS);

                tpl_set_var('logtext', htmlspecialchars($log_text, ENT_COMPAT, 'UTF-8'), true);

                if ($use_log_pw == true) {
                    if ($pw_not_ok == true) {
                        tpl_set_var('log_pw_field', $log_pw_field_pw_not_ok);
                    } else {
                        tpl_set_var('log_pw_field', $log_pw_field);
                    }
                } else {
                    tpl_set_var('log_pw_field', '');
                }

                if ($date_not_ok == true) {
                    tpl_set_var('date_message', $date_message);
                }

                tpl_set_var('coords_not_ok', ' ');
                if (isset($coords_not_ok)) {
                    if ($coords_not_ok == true) {
                        tpl_set_var('coords_not_ok', $error_coords_not_ok);
                    }
                }

                if ($score_not_ok == true) {
                    tpl_set_var('score_message', $score_message);
                } else
                    tpl_set_var('score_message', '');

                if (($log_type < 0) && (isset($_POST['logtype'])))
                    tpl_set_var('log_message', $log_not_ok_message);
                else
                    tpl_set_var('log_message', '');

            }
        } // end if( cache_id != 0 )
        else {
            tpl_errorMsg('log_cache', "Improper cache id param!");
        }
    }
}
if ($no_tpl_build == false) {
    //make the template and send it out
    tpl_set_var('language4js', $lang);
    tpl_BuildTemplate(false);
}

function validate_coords($lat_h, $lat_min, $lon_h, $lon_min, $lonEW, $latNS, $error_coords_not_ok)
{


    if ($lat_h == '') {
        tpl_set_var('lat_message', $error_coords_not_ok);
        $error = true;
    }
    if ($lat_min == '') {
        tpl_set_var('lat_message', $error_coords_not_ok);
        $error = true;
    }
    if ($lon_h == '') {
        tpl_set_var('lon_message', $error_coords_not_ok);
        $error = true;
    }
    if ($lon_min == '') {
        tpl_set_var('lon_message', $error_coords_not_ok);
        $error = true;
    }
    if (@$error)
        return $error;

    //check coordinates
    $error = false;
    if ($lat_h != '' || $lat_min != '') {
        if (!mb_ereg_match('^[0-9]{1,2}$', $lat_h)) {
            tpl_set_var('lat_message', $error_coords_not_ok);
            $error = true;
            $lat_h_not_ok = true;
        } else {
            if (($lat_h >= 0) && ($lat_h < 90)) {
                $lat_h_not_ok = false;
            } else {
                tpl_set_var('lat_message', $error_coords_not_ok);
                $error = true;
                $lat_h_not_ok = true;
            }
        }

        if (is_numeric($lat_min)) {
            if (($lat_min >= 0) && ($lat_min < 60)) {
                $lat_min_not_ok = false;
            } else {
                tpl_set_var('lat_message', $error_coords_not_ok);
                $error = true;
                $lat_min_not_ok = true;
            }
        } else {
            tpl_set_var('lat_message', $error_coords_not_ok);
            $error = true;
            $lat_min_not_ok = true;
        }

        $latitude = $lat_h + round($lat_min, 3) / 60;
        if ($latNS == 'S')
            $latitude = -$latitude;

        if ($latitude == 0) {
            tpl_set_var('lon_message', $error_coords_not_ok);
            $error = true;
            $lat_min_not_ok = true;
        }
    } else {
        $latitude = NULL;
        $lat_h_not_ok = true;
        $lat_min_not_ok = true;
        $error = true;
    }

    if ($lon_h != '' || $lon_min != '') {
        if (!mb_ereg_match('^[0-9]{1,3}$', $lon_h)) {
            tpl_set_var('lon_message', $error_coords_not_ok);
            $error = true;
            $lon_h_not_ok = true;
        } else {
            if (($lon_h >= 0) && ($lon_h < 180)) {
                $lon_h_not_ok = false;
            } else {
                tpl_set_var('lon_message', $error_coords_not_ok);
                $error = true;
                $lon_h_not_ok = true;
            }
        }

        if (is_numeric($lon_min)) {
            if (($lon_min >= 0) && ($lon_min < 60)) {
                $lon_min_not_ok = false;
            } else {
                tpl_set_var('lon_message', $error_coords_not_ok);
                $error = true;
                $lon_min_not_ok = true;
            }
        } else {
            tpl_set_var('lon_message', $error_coords_not_ok);
            $error = true;
            $lon_min_not_ok = true;
        }

        $longitude = $lon_h + round($lon_min, 3) / 60;
        if ($lonEW == 'W')
            $longitude = -$longitude;

        if ($longitude == 0) {
            tpl_set_var('lon_message', $error_coords_not_ok);
            $error = true;
            $lon_min_not_ok = true;
        }
    } else {
        $longitude = NULL;
        $lon_h_not_ok = true;
        $lon_min_not_ok = true;
        $error = true;
    }

    $lon_not_ok = $lon_min_not_ok || $lon_h_not_ok;
    $lat_not_ok = $lat_min_not_ok || $lat_h_not_ok;



    /*
      if ($lon_not_ok == false) print "lon_ok<br>";
      if ($lat_not_ok == false) print "lat_ok<br>";

      exit;
     */

    return ($error);
}

function debug($label, $array)
{
    print "<pre>$label ===============================";
    print_r($array);
    print "===========================================</pre>";
}

/**
 * after add a log it is a good idea to full recalculate stats of cache, that can avoid
 * possible errors which used to appear when was calculated old method.
 *
 * TODO: (regarding issue #138)
 * 1. recalculate last_found from DB
 * 2. make this method a library method or so
 * 3. use this method in other places, where such recalculation is needed
 *
 * by Andrzej Łza Woźniak, 12-2013
 */
function recalculateCacheStats($cacheId, $cacheType, $lastFoundQueryString)
{
    if ($cacheType == 6) { // event (no idea who developed so irracional rules, not me!)
        $query = "
            UPDATE `caches`
            SET `founds`   = (SELECT count(*) FROM `cache_logs` WHERE `cache_id` =:1 AND TYPE =7 AND `deleted` =0 ),
                `notfounds`= (SELECT count(*) FROM `cache_logs` WHERE `cache_id` =:1 AND TYPE =8 AND `deleted` =0 ),
                `notes`= (SELECT count(*) FROM `cache_logs` WHERE `cache_id` =:1 AND TYPE =3 AND `deleted` =0 )
            $lastFoundQueryString
            WHERE `cache_id` =:1
        ";
    } else {
        $query = "
            UPDATE `caches`
            SET `founds`   = (SELECT count(*) FROM `cache_logs` WHERE `cache_id` =:1 AND TYPE =1 AND `deleted` =0 ),
                `notfounds`= (SELECT count(*) FROM `cache_logs` WHERE `cache_id` =:1 AND TYPE =2 AND `deleted` =0 ),
                `notes`= (SELECT count(*) FROM `cache_logs` WHERE `cache_id` =:1 AND TYPE =3 AND `deleted` =0 )
            $lastFoundQueryString
            WHERE `cache_id` =:1
        ";
    }

    $db = OcDb::instance();
    $db->multiVariableQuery($query, $cacheId);
}

function compareTime($time_to_check, $interval)
{
    $time = new DateTime("now");
    $time->sub(new DateInterval($interval));
    $time_diff = $time_to_check->diff($time);
    return $time_diff->format("%r");
}

function buildGeoKretyLog(User $user, GeoCache $geoCache, $i, DateTime $logDateTime)
{
    global $absolute_server_URI;
    $geoKretyLog = new GeoKretLog();
    $geoKretyLog
        ->setGeoCache($geoCache)
        ->setUser($user)
        ->setTrackingCode($_POST['GeoKretIDAction' . $i]['nr'])
        ->setGeoKretId($_POST['GeoKretIDAction' . $i]['id'])
        ->setGeoKretName($_POST['GeoKretIDAction' . $i]['nm'])
        ->setLogType($_POST['GeoKretIDAction' . $i]['action'])
        ->setComment(substr($_POST['GeoKretIDAction' . $i]['tx'], 0, 80))
        ->setLogDateTime($logDateTime)
    ;
    return $geoKretyLog;
}

function enqueueGeoKretyLog(DateTime $logDateTime, User $user, GeoCache $geoCache, $MaxNr)
{
    $geoKretyLogs = [];
    for ($i = 1; $i < $MaxNr + 1; $i++) {
        if ($_POST['GeoKretIDAction' . $i]['action'] > -1) {
            $geoKretyLogs[] = buildGeoKretyLog($user, $geoCache, $i, $logDateTime);
        }
    }
    if(count($geoKretyLogs) > 0){
        GeoKretLog::EnqueueLogs($geoKretyLogs);
    }
}

function processGeoKrety(DateTime $logDateTime, $user, $geoCache)
{
    $MaxNr = isset($_POST['MaxNr']) ? (int) $_POST['MaxNr'] : 0;
    if ($MaxNr > 0) {
        enqueueGeoKretyLog($logDateTime, $user, $geoCache, $MaxNr);
    }
}
