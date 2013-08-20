#!/usr/bin/perl -w

# textimage.pl. (c) by Christoph Krüger christoph@krueger.name
# This script will generate a gif-image containing a quote and it's author
# Dependency: you have to have imagemagick installed

my ($quote, $author, $theme, $num_args);

$num_args = $#ARGV + 1;
die "usage: textimage.pl \'quote\' \'author\'\n" unless $num_args == 2;

my @bg = ('ffffff','bf8f30','2a4480','bf5930','259238','70227e');
my @f1 = ('000000','ffd073','6c8cd5','ff9b73','65e17b','c262d3');
my @f2 = ('777777','ffbf40','4671d5','ff7640','38e156','bc38d3');

$quote = $ARGV[0];
$author = $ARGV[1];
$theme = int(rand($#bg));

# sanitize the strings
$quote =~ s/\!/\\!/g;
$author =~ s/\!/\\!/g;

system("convert -background '#$bg[$theme]' -fill '#$f1[$theme]' -size 320x430 -pointsize 20 -font Times-Italic caption:'\"$quote\"' -trim -font Times-Roman -gravity SouthEast -size 320x -pointsize 30 -fill '#$f2[$theme]' caption:'($author)' -trim -bordercolor '#$bg[$theme]' -border 30 -append quote.gif");


