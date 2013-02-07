#!/usr/bin/perl -w

use SOAP::Lite;

#SOAP::Lite->import(trace => debug);

$url = shift;
if (!$url) {
	print "Usage: qotd-client.pl <url>\n";
	print "       e.g. qotd-client.pl http://qotd.ralph-schuster.eu/qotd.pl\n";
	exit 1;
}

($url =~ /:\/\/([^\/:]+)/) && ($domain = $1);

$result =  SOAP::Lite
        -> uri("http://$domain/Quote")
        -> proxy("$url")
        -> getquote();
       
unless ($result->fault) {
	$quote = $result->valueof('//quote/');
	$author = $result->valueof('//author/');

	print("\"".$quote."\"\n\t\t\t(".$author.")\n");

} else {
	print join ', ', $result->faultcode, $result->faultstring;
}
