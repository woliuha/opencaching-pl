<?php
	global $lang, $rootpath;

	if (!isset($rootpath)) $rootpath = './';

	//include template handling
	require_once($rootpath . 'lib/common.inc.php');

  setlocale(LC_TIME, 'pl_PL.UTF-8');

  $rsU = sql('SELECT COUNT(*) `count` FROM (SELECT COUNT(caches.user_id) FROM `caches` WHERE `status`=1 GROUP BY `user_id`) `users_with_founds`');
  $fC = sql('SELECT COUNT(*) `count` FROM `caches` WHERE `status`=1');
    $rsUs = mysql_fetch_array($rsU);
    $fCt = mysql_fetch_array($fC);
 
 $rsfCR = sql("SELECT COUNT(*) `count`, `cache_location`.`adm3` region FROM `cache_location` INNER JOIN cache_logs ON cache_location.cache_id=cache_logs.cache_id WHERE `cache_location`.`code1`='PL' AND cache_logs.type='1' AND cache_logs.deleted='0' GROUP BY `cache_location`.`code3` ORDER BY count DESC");

	echo '<table width="97%"><tr><td align="center"><center><b> '.tr('activity_by_region').'</b> <br /><br /> '.tr('users_who_found_caches').': ';
	echo $rsUs[count]; 
	echo ' .::. '.tr('number_of_active_caches').': ';
	echo $fCt[count]; 
	echo '</center></td></tr></table><br><table border="1" bgcolor="white" width="97%">' . "\n";

 
echo '
<tr class="bgcolor2">
	<td width="20%" align="right">
		&nbsp;&nbsp;<b>'.tr('number_found_of_caches').'</b>&nbsp;&nbsp;
	</td>
	<td align="right">
		&nbsp;&nbsp;<b>'.tr('region').'</b>&nbsp;&nbsp;
	</td>
</tr><tr><td height="2"></td></tr>';

while ($line=mysql_fetch_array($rsfCR))
{
    echo '<tr class="bgcolor2">
			<td align="right">
				&nbsp;&nbsp;<b>'.$line[count].'</b>&nbsp;&nbsp;
			</td>
			<td align="right">
				&nbsp;&nbsp;<b>'.$line[region].'</b>&nbsp;&nbsp;
			</td>';

}

	echo '</table>' . "\n";

?>
