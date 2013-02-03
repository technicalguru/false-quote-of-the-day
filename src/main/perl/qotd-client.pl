#!/usr/bin/perl -w

use SOAP::Lite;

$url = shift;
if (!$url) {
	print "Usage: qotd-client.pl <url>\n";
	print "       e.g. qotd-client.pl http://qotd.ralph-schuster.eu/qotd/qotd.pl\n");
	exit 1;
}

($url =~ /:\/\/([^\/:]+)/) && ($domain = $1);

$result =  SOAP::Lite
        -> uri("http://$domain/Quote")
        -> proxy('$url')
        -> getquote();
        
@res = $result->paramsout;
$res = $result->result;

print("\"".$res."\"\n\t\t\t(".$res[0].")\n");
