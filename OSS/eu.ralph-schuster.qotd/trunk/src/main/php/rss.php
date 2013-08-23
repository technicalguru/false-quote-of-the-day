<?php

class RssFeed {

	var $rssFile = 'rss.xml';
	var $connection;
	var $quotes = array();

	function RssFeed($rssFile, $connection) {
		$this->rssFile = $rssFile;
		$this->connection = $connection;
		$this->loadQuotes();
	}

	function addItems($ids) {
		if (!is_array($ids)) $ids = explode(',', $ids);
		foreach ($ids AS $id) {
			$this->addQuote($this->getQuote($id));
		}		
	}

	function loadQuotes() {
		$result = mysql_query("SELECT * FROM qotd_settings WHERE name='rssContent'");
		if ($result) {
			$ids = mysql_fetch_assoc($result);
			$ids = array_reverse(preg_split('/,/', $ids['value']));
			$this->addItems($ids);
		}
	}

	function addQuote($quote) {
		if ($quote !== FALSE) {
			// Check that we don't have quote yet
			$found = 0;
			foreach ($this->quotes AS $q) {
				if ($q['id'] == $quote['id']) {
					$found = 1;
				}
			}

			if (!$found) {
				$this->quotes[] = $quote;
			}
		}
	}

	function &getQuote($id) {
		$result = mysql_query("SELECT * FROM qotd_quotes WHERE id=$id", $this->connection);
		if ($result) {
			$quote = mysql_fetch_assoc($result);
			return $quote;
		}
		return FALSE;
	}

	function save() {
		$quotes = array_reverse($this->quotes);
		$content = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n".
			"<rss version=\"2.0\"\n".
			"	xmlns:content=\"http://purl.org/rss/1.0/modules/content/\"\n".
			"	xmlns:wfw=\"http://wellformedweb.org/CommentAPI/\"\n".
			"	xmlns:dc=\"http://purl.org/dc/elements/1.1/\"\n".
			"	xmlns:atom=\"http://www.w3.org/2005/Atom\"\n".
			"	xmlns:sy=\"http://purl.org/rss/1.0/modules/syndication/\"\n".
			"	xmlns:slash=\"http://purl.org/rss/1.0/modules/slash/\"\n".
			"	>\n".

			"	<channel>\n".
			"		<title>In den Mund gelegt</title>\n".
			"		<atom:link href=\"http://qotd.ralph-schuster.eu/".$this->rssFile."\" rel=\"self\" type=\"application/rss+xml\" />\n".
			"		<link>http://qotd.ralph-schuster.eu</link>\n".
			"		<description>Der satirische Zitatservice</description>\n".
			"		<lastBuildDate>".date('r')."</lastBuildDate>\n".
			"		<language>de-DE</language>\n".
			"		<sy:updatePeriod>hourly</sy:updatePeriod>\n".
			"		<sy:updateFrequency>1</sy:updateFrequency>\n";

		$counter = 0;
		$ids = array();
		foreach ($quotes AS $quote) {
			$counter++;
			if ($counter > 10) break;
			$ids[] = $quote['id'];
			$time = time()-$counter*86400+86400;

			$content .= "		<item>\n".
				"			<title>".date("d.F Y", $time)."</title>\n".
				"			<link>http://qotd.ralph-schuster.eu</link>\n".
				"			<pubDate>".date('r', $time)."</pubDate>\n".
				"			<description><![CDATA[<em>".utf8_encode($quote['quote'])."</em> (".utf8_encode($quote['author']).")]]></description>\n".
				"		</item>\n";
		}
		$content .= "	</channel>\n".
			"</rss>\n";

		file_put_contents($this->rssFile, $content);

		mysql_query("UPDATE qotd_settings SET value='".implode(',', $ids)."' WHERE name='rssContent'");
	}

}

?>
