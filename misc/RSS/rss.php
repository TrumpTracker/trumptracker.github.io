<?php
require('rss.class.php');
$rss = new RSS("old_data.json","db.json"); //Create RSS instance
$yaml = $rss->getYAML(); //Get the newest YAML from github
$points = $rss->parsePoints($yaml); //Parse the YAML correctly
$rss->parseDifference($points); //See if there is any difference
$rss->updateOld($points); //Update the yaml file to the newest YAML
$rss = $rss->getDB(); //Get all previous changes

//Start actually parsing the RSS

$i = 0;
header("Content-Type: application/xml; charset=UTF-8");
echo "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\n";
?>
<rss version="2.0">
<channel>
<title>Recent TrumpTracker updates</title>
<link>https://trumptracker.github.io</link>
<description>RSS feed for all policy updates on Trump's presidency</description>
<language>en-US</language>
<?
foreach($rss as $id => $item) {
	$i++;
	if($i < 10) {
		echo "<item>\n";
		echo "<guid isPermaLink='false'>$id</guid>\n";
		echo "<title>$item[title]</title>\n";
		echo "<link>$item[url]</link>\n";
		echo "<description>$item[content]</description>\n";
		$time = date('D, d M Y H:i:s T',$item['time']);
		echo "<pubDate>$time</pubDate>\n";
		echo "</item>\n";
	}
}
?>
</channel>
</rss>