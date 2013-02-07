#!/usr/bin/perl -w
use strict;
use warnings;
use CGI;

my $q = new CGI();

print $q->header;
my $color = $q->param("color");
my $fontsize = $q->param("fontsize");
my $fontfamily = $q->param("fontfamily");

# check input
$color = '000000' if $color !~/^[0-9a-f]{6}$/i;
$fontsize = '' if $color !~/^[0-9]+$/;
$fontfamily =~ s/'//g;

# create font def 
my $fontdef = '';
$fontdef .= "font-family: '$fontfamily'; " if $fontfamily;
$fontdef .= "font-size: ${fontsize}pt; " if $fontsize;

print <<EOF
<html>
<head>
    <title>False Quote of the Day</title>
    <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.9.0/jquery.min.js" type="text/javascript" charset="utf-8"></script>
    <script type="text/javascript" language="JavaScript" src="quote.js"></script>
</head>
<body style="color: $color; $fontdef border: 0; padding: 0; margin: 0; " onLoad="getquoteAjax();">
	<table id="fqotd" style="$fontdef border: none; text-align: center; width: 100%; height: 100%;">
		<tr><td valign="middle">
		<span id="fqotd-quote" style="font-style: italic; vertical-align: middle;"><img src="ajax-loader.gif" alt="Loading..." title="Loading..."/></span>
		<span id="fqotd-author" style="vertical-align: middle;"></span>
		</td></tr>
	</table>
</body>
<html>
EOF


