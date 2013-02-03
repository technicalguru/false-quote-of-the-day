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
	my ($quote, $author, $numQuotes, $row);
	
	# TODO The quote is selected randomly, have it selected by day
	
	# How many quotes do we have?
	my $sth = $DBH->prepare("SELECT COUNT(*) AS cnt FROM qotd_quotes");
	$sth->execute();
	if ($row = $sth->fetchrow_hashref()) {
		$numQuotes = $$row{'cnt'};
	}
	
	$rnr = int(rand($numQuotes - 1));
	
	# Select the quote from DB
	$sth = $DBH->prepare("SELECT * FROM qotd_quotes ORDER BY id LIMIT $rnr, 1");
	$sth->execute();
	if ($row = $sth->fetchrow_hashref()) {
		$qotd = $row;
	}

	$quote = SOAP::Data->type(string => $$qotd{'quote'});
	$author = SOAP::Data->type(string => $$qotd{'author'});
	
	# return quote
	return { 'quote' => $quote, 'author' => $author};
	
}
