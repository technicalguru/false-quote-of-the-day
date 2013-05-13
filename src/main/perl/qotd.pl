#!/usr/bin/perl -w
	 
use SOAP::Transport::HTTP;
use Config::Simple;
use DBI;

	# Load DB config
	my $CONFIG   = new Config::Simple('/var/www/vhosts/ralph-schuster.eu/qotd/qotd.ini');
	my $DBNAME   = $CONFIG->param('DBNAME');
	my $DBLOGIN  = $CONFIG->param('DBLOGIN');
	my $DBPASSWD = $CONFIG->param('DBPASSWD');
	my $DBHOST   = $CONFIG->param('DBHOST');
	my $DBPORT   = $CONFIG->param('DBPORT');

	# Access DB
	my $DSN = "DBI:mysql:database=$DBNAME;host=$DBHOST;port=$DBPORT";
	my $DBH = DBI->connect("dbi:mysql:$DSN", $DBLOGIN, $DBPASSWD) or die "Cannot connect to database...\n$DBI::errstr\n";

# Dispatch the SOAP request
SOAP::Transport::HTTP::Apache
   ->dispatch_to('Quote')
   ->handle;
 
package Quote;

# Returns the quote of the day
sub getquote {
	
	my @quotes;
	my $rnr;
	my ($quote, $author, $maxId, $row);
	
	# load the settings
	my %SETTINGS = ();
	my $sth = $DBH->prepare("SELECT * FROM qotd_settings");
	$sth->execute();
	while ($row = $sth->fetchrow_hashref()) {
		$SETTINGS{$$row{'name'}} = $$row{'value'};
	}
	
	# How many quotes do we have?
	$sth = $DBH->prepare("SELECT MAX(id) AS cnt FROM qotd_quotes");
	$sth->execute();
	if ($row = $sth->fetchrow_hashref()) {
		$maxId = $$row{'cnt'};
	}

	# check whether the quote was already selected for today
	my $id = 0;
	my @T = localtime(time);
	my $today = sprintf("%04d%02d%02d", $T[5]+1900, $T[4]+1, $T[3]);
	if ($SETTINGS{'currentDay'} eq $today) {
		$id = $SETTINGS{'currentId'};
		$sth = $DBH->prepare("SELECT * FROM qotd_quotes WHERE id=$id");
		$sth->execute();
		if ($row = $sth->fetchrow_hashref()) {
			$quote  = $$row{'quote'};
			$author = $$row{'author'};
		}
	} else {
		# Find a new quote
		if ($SETTINGS{'currentDay'}) {
			$id = $SETTINGS{'currentId'};
		} else {
			$id = 0;
		}
		while (!$quote) {
			$id++;
			$id = 1 if $id > $maxId;
			$sth = $DBH->prepare("SELECT * FROM qotd_quotes WHERE id=$id");
			$sth->execute();
			if ($row = $sth->fetchrow_hashref()) {
				$quote  = $$row{'quote'};
				$author = $$row{'author'};
			}
		}

		# Save settings for new quote
		if ($SETTINGS{'currentDay'}) {
			$sth = $DBH->prepare("UPDATE qotd_settings SET value='$today' WHERE name='currentDay'");
			$sth->execute();
			$sth = $DBH->prepare("UPDATE qotd_settings SET value='$id' WHERE name='currentId'");
			$sth->execute();
		} else {
			$sth = $DBH->prepare("INSERT INTO qotd_settings (name, value) VALUES ('currentDay', '$today')");
			$sth->execute();
			$sth = $DBH->prepare("INSERT INTO qotd_settings (name, value) VALUES ('currentId', '$id')");
			$sth->execute();
		}
		$sth = $DBH->prepare("UPDATE qotd_quotes SET last_usage=CURRENT_TIMESTAMP WHERE id=$id");
		$sth->execute();
	}

	# Sanitize for SOAP
	$quote = SOAP::Data->type(string => $quote);
	$author = SOAP::Data->type(string => $author);
	
	# return quote
	return { 'quote' => $quote, 'author' => $author};
	
}

