#!/usr/bin/tclsh
#
# Requirements: Tcl and mysqltcl need to be installed. 
# 
# Tcl is a stock part of any Linux distro -- you should be able to use
# your distro's package management system to install it (eg 'yum install tcl'
# or 'apt get tcl').
# mysqltcl is available from http://www.xdobry.de/mysqltcl
# You might need to build it from source or it might be in a 3rd party repo.
#
# You also need some way to filter E-Mails containing your new links to
# this script.  Usually that can be done with Procmail on a typical Linux
# system.
# Something like this recipe (assumes the E-Mail address the links are going
# to is 'ganlinks@deepsoft.com' and that a version of this script is in 
# /var/www/deepsoft/):
#
# :0 c
# * ^TOganlinks@deepsoft.com   
# | /var/www/deepsoft/ganlinksToDB.tcl | \
#   mail -s 'ganlinksToDB.tcl results' heller@deepsoft.com
#
# Links are added to the database with side=right and enabled=false -- you'll
# need to go to the GAN Database backend page to enable the new links (either 
# all or selectively).  You can change this by editing the ',1,0)' below. 
# 
# The script flushes expired links each time it is run.
#
# DB Access -- edit these three lines to code in your DB access info.
set USER     {username}
set PASSWORD {password}
set DATABASE {databasename}
# If your db prefix is other then 'wp_', you'll need to edit the SQL 
# statements below.
#


set baseversion [package require Tcl]

if {"$::tcl_platform(machine)" eq "x86_64"} {
  lappend auto_path /usr/lib64/tcl$baseversion
} else {
  lappend auto_path /usr/lib/tcl$baseversion
}

package require mysqltcl

set DB [::mysql::connect -user $USER -password $PASSWORD -db $DATABASE]

while { [gets stdin line] >= 0 } {
  if {[regexp {^"Id"[[:space:]]*"Name"} "$line"] > 0} {break}
}

proc db_quote {s} {
  regsub -all {'} $s {\'} result
  return "'$result'"
}

proc tsv_unquote {s} {
  if {[regexp {^"(.*)"$} $s -> q]} {
    regsub -all {""} $q {"} q
    return $q
  } else {
    return $s
  }
}

proc fixdate {date} {
  if {[regexp {^([[:digit:]]*)/([[:digit:]]*)/([[:digit:]]*)$} "$date" -> m d y] > 0} {
    if {[string length $y] < 4} {set y 20$y}
    return [format {%04d-%02d-%02d} $y $m $d]
  } else {
    return $date
  }
}

while { [gets stdin line] >= 0 } {
#				Edit if your WP prefix is other then wp_
  set sqlstatement {insert into wp_DWS_GAN (LinkID, LinkName,  MerchantID,
			Advertiser, ClickserverLink, ImageURL, ImageHeight, 
			ImageWidth, StartDate, EndDate, PromoType, 
			MerchandisingText,  side,enabled) values (}

  set rawelts [split "$line" "\t"]
  
  if {[llength $rawelts] < 16} {continue}
  set LinkID [string trim [tsv_unquote [lindex $rawelts 0]]]
  if {[regexp {^[[:digit:]]} $LinkID] > 0} {set LinkID J$LinkID}
  set elts [list $LinkID]
  lappend elts [lindex $rawelts 1]
  set MerchantID [string trim [tsv_unquote [lindex $rawelts 2]]]
  if {[regexp {^[[:digit:]]} $MerchantID] > 0} {set MerchantID K$MerchantID}
  lappend elts $MerchantID
  eval [list lappend elts] [lrange $rawelts 3 5]
  set elts [lrange $rawelts 0 5]
  set height 0; set width 0
  regexp {^"([[:digit:]]*)x([[:digit:]]*)"$} [lindex $rawelts 6] -> width height
  lappend elts $height $width
  lappend elts [fixdate [string trim [tsv_unquote [lindex $rawelts 8]]]]
  set enddate [fixdate [string trim [tsv_unquote [lindex $rawelts 9]]]]
  
  if {"$enddate" eq "none" || "$enddate" eq ""} {
    lappend elts "2099-12-31"
  } else {
    lappend elts $enddate
  }
  lappend elts [lindex $rawelts 11]
  lappend elts [lindex $rawelts 15]
  
  foreach dbelt $elts \
	  type {string string string string string string int int  
		string string string string} {
    set dbelt [tsv_unquote $dbelt]
    switch -exact $type {
      string {append sqlstatement "[db_quote $dbelt],"}
      int {append sqlstatement "$dbelt,"}
    }
  }
  # Change this for initial side,enabled 
  #		side: 1=right,0=leader
  #		enabled: 0=no,1=yes
  append sqlstatement "1,0)";
  puts "$sqlstatement;"
  if {[catch {::mysql::exec $DB "$sqlstatement"} result]} {
    puts "-- DB Error: $result"
  } else {
    puts "-- DB Result: $result"
  }
}

#	Edit if your WP prefix is other then wp_
#puts "delete from wp_DWS_GAN where EndDate<CURDATE();"
#if {[catch {::mysql::exec $DB "delete from wp_DWS_GAN where EndDate<CURDATE()"} result]} {
#  puts "-- DB Error: $result"
#} else {
#  puts "-- DB Result: $result"
#}

::mysql::close $DB

  
  
