<?php  
/* 
Plugin Name: MyPageCounter
Plugin URI: http://blog.elearning.co.jp/?entryCounter=true
Description: Counter 
Version: 1 
Author: Yoichiro Nishimura
Author URI: http://php6.jp 
*/  
  
function entry_counter() {
	kstatlink();
	kstat_css();
	echoAnalytics();
	echo "
	<title>記事数一覧</title>
	<meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">
	<style>th,td {
	   border: 1px solid green;
	  padding:2px;
	}
	table {
	   border-collapse: collapse;
	   border: 3px solid green;
	}}</style>";

	$ret = mysql_query('select min(post_date) first_date from wp_posts');
	$data = mysql_fetch_array($ret);
	$startYear = substr($data['first_date'],0,4);
	$ret = mysql_query('select max(post_date) last_date from wp_posts');
	$data = mysql_fetch_array($ret);
	$lastYear = substr($data['last_date'],0,4);
	for($y = $startYear; $y <= $lastYear; $y++){
		for($m = 1; $m <= 12; $m++){
			$ymArray[] = sprintf("%04d-%02d", $y, $m);
		}
	}
	$ret = mysql_query("select * from (select count(*) count, display_name,post_author from wp_posts join wp_users on wp_users.ID = post_author where post_status = 'publish' and post_type = 'post' group by post_author) vtable order by count desc");
	echo mysql_error();
	echo "<table style='border:solid 1px'>";
	echo "<tr>";
	echo "<td></td>";
	while($data = mysql_fetch_array($ret)){
		$autherNameMap[$data['post_author']] = $data["display_name"];
		echo "<td>".substr($data["display_name"],0,7)."</td>";
		$post_authorArray[] = $data['post_author'];
	}
	echo "<td></td></tr>";
	foreach($ymArray as $ym){
		$ret = mysql_query("select count(*) count,post_author from wp_posts where post_date like '%".$ym."%' and post_status = 'publish' and post_type = 'post'  group by post_author");
		$count = array();
		while($data = mysql_fetch_array($ret)){
			$count[$data['post_author']] = $data['count'];
		}
		if(!$count)continue; 
		$ymArray2[] = $ym;
		echo "<tr>";
		echo "<td>".$ym."</td>";
		$monthly = 0;
		foreach($post_authorArray as $post_author){
			$data = mysql_fetch_array($ret);
			if($count[$post_author]){
				echo '<td><a href="?entryCounter=true&amp;author='.$post_author.'&amp;ym='.$ym.'">'.$count[$post_author]."</a></td>";
				$monthly += $count[$post_author];
				$personalTotal[$post_author] += $count[$post_author];
			}else{
				echo "<td bgcolor='#cccccc'>".$count[$post_author]."</td>";
			}
			if($data['count'])$flag = true;
			echo mysql_error();
		}
		echo '<td><strong><a href="?entryCounter=true&amp;ym='.$ym.'">'.$monthly.'</a></strong></td>';
		echo "</tr>";
	}
	echo "<tr>";
	echo "<td>Total</td>";
	foreach($post_authorArray as $post_author){
		$total += $personalTotal[$post_author];
		echo '<td><strong><a href="?entryCounter=true&amp;author='.$post_author.'">'.$personalTotal[$post_author]."</strong></td>";
	}
	echo '<td><strong><a href="?entryCounter=true">'.$total."</a></strong></td>";
	echo "</tr>";
	echo "</table>";
	echo '<br>';

	function getLastDay($ym){
		list($y, $m) = explode('-', $ym);
		return date("d", mktime(0, 0, 0, $m+1, 0, $y)); 
	}

	echo '<table style="border:solid 1px">';
	if(isset($_GET['ym'])){
		echo '<tr><td>yyyy-mm</td><td><a href="?entryCounter=true&amp;ym='.$_GET['ym'].'">'.$_GET['ym'].'</a></td></tr>';
	}
	if(isset($_GET['author'])){
		echo '<tr><td>Author</td><td><a href="?entryCounter=true&amp;author='.$_GET['author'].'">'.$autherNameMap[$_GET['author']].'</a></td></tr>';
	}
	echo '</table>';

	if(isset($_GET['ym'])){
		$ymArray2 = array($_GET['ym']);
	}
	foreach(array_reverse($ymArray2) as $ym){
		echo '<br>'.$ym.'<br><table style="border:solid 1px">';
		$dayResult = mysql_query("select mid(post_date,9,2) post_date, count(*) count from wp_posts where post_date like '%".mysql_escape_string($ym)."%' and post_status = 'publish' and post_type = 'post' ".(isset($_GET['author']) ? ' and post_author = '.mysql_escape_string($_GET['author']): '' )." group by left(post_date,10) order by post_date");
		while($dayData = mysql_fetch_array($dayResult)){
			$day[$dayData['post_date']+0] = $dayData['count'];
		}
		echo '<tr align="center" valign="bottom">';
		echo '<td>Graph</td>';
		for($i = 1; $i <= getLastDay($ym); $i++){
			if(isset($day[$i])){
				echo '<td>'.str_repeat('■<br>',$day[$i]).'</td>';
			}else{
				echo '<td></td>';
			}
		}
		echo '</tr>';
		echo '<tr align="center">';
		echo '<td>Day</td>';
		list($y, $m) = explode('-', $ym);
		for($d = 1; $d <= getLastDay($ym); $d++){
			$day = date("w", mktime(0, 0, 0, $m, $d, $y));
			if($day == 0)
				echo '<td width="20" bgcolor="#ffaaaa">'.$d.'</td>';
			elseif($day == 6)
				echo '<td width="20" bgcolor="#aaaaff">'.$d.'</td>';
			else
				echo '<td width="20">'.$d.'</td>';
		}
		echo '</tr>';
		echo '</table>';
	}
	echo '<br>';

	if(isset($_GET['ym']) && isset($_GET['author'])){
		$ret = mysql_query("select * from wp_posts where post_date like '%".mysql_escape_string($_GET['ym'])."%' and post_status = 'publish' and post_type = 'post' and post_author = ".mysql_escape_string($_GET['author'])." order by post_date");
	}elseif(isset($_GET['ym'])){
		$ret = mysql_query("select * from wp_posts where post_date like '%".mysql_escape_string($_GET['ym'])."%' and post_status = 'publish' and post_type = 'post' order by post_date");
	}elseif(isset($_GET['author'])){
		$ret = mysql_query("select * from wp_posts where post_status = 'publish' and post_type = 'post' and post_author = ".mysql_escape_string($_GET['author'])." order by post_date");
	}else{
		exit;
	}

	echo '<table style="border:solid 1px">';
	echo '<tr><td>投稿日時</td><td>投稿者名</td><td>ブログタイトル</td><td>カテゴリー</td><td>タグ</td></tr>';
	while($data = mysql_fetch_assoc($ret)){
		//タグ・カテゴリ生成
		$tag = "";
		$cat = "";
		$tcRet = mysql_query("SELECT * FROM wp_term_relationships AS A LEFT JOIN wp_term_taxonomy AS B ON A.term_taxonomy_id = B.term_taxonomy_id LEFT JOIN wp_terms AS C ON B.term_id = C.term_id WHERE A.object_id = '".$data['ID']."'");
		while($tcData = mysql_fetch_assoc($tcRet)){
			if($tcData['taxonomy']=="post_tag") $tag .= '<a href="?tag='.$tcData['name'].'">'.$tcData['name'].'</a><br>';
			if($tcData['taxonomy']=="category") $cat .= '<a href="?cat='.$tcData['term_id'].'">'.$tcData['name'].'</a><br>';
		}
		echo '<tr><td>'.$data['post_date'].'</td><td>'.$autherNameMap[$data['post_author']].'</td><td><a href="?p='.$data['ID'].'" target="_blank">'.$data['post_title'].'</a></td><td nowrap>'.$cat.'</td><td nowrap>'.$tag.'</td></tr>';
		//echo '<tr><td>'.$data['post_date'].'</td><td>'.$autherNameMap[$data['post_author']].'</td><td><a href="?p='.$data['ID'].'">'.$data['post_title'].'</a></td></tr>';
	}
	echo '</table>';

	exit;
}

if($_GET['entryCounter'])entry_counter();

add_action('get_footer', 'kstatlink');

function kstatlink(){

	if($_GET['entryCounter']){
		?><div class="kstat"><a href="<?=get_option('home')?>/"><img src="<?=get_option('home')?>/wp-content/plugins/MyPageCounter/allow.gif"></a></div><?
	}else{
		?><div class="kstat"><a href="<?=get_option('home')?>/?entryCounter=true"><img src="<?=get_option('home')?>/wp-content/plugins/MyPageCounter/allow.gif"></a></div><?
	}
}

function kstat_css() {
?>
<script type="text/javascript">
  swfobject.registerObject("wpFollowmeFlash", "9.0.0");
</script>
<style type="text/css">
.kstat {
	position:fixed;
	background:#00FF00;
	top:380px;
	right:0px;
	width:32px;
	height:32px;
}
</style>

<?php
}
add_action('wp_head', 'kstat_css');

function echoAnalytics(){?>
<script type="text/javascript">
  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-8991053-11']);
  _gaq.push(['_trackPageview']);
  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();
</script>
<?php }

if($_GET['entryCounter'])entry_counter();