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
  if {[regexp "^Advertiser Site Name\t" "$line"] > 0} {break}
}

proc db_quote {s} {
  regsub -all {'} $s {\'} result
  return "'$result'"
}

while { [gets stdin line] >= 0 } {
#				Edit if your WP prefix is other then wp_
  set sqlstatement {insert into wp_DWS_GAN (Advertiser, LinkID, LinkName, 
			MerchandisingText, AltText, StartDate, EndDate, 
			ClickserverLink, ImageURL, ImageHeight, ImageWidth, 
			LinkURL, PromoType,  MerchantID,side,enabled) values (}
  set elts [split "$line" "\t"]
  if {[llength $elts] < 14} {continue}
  if {"[lindex $elts 6]" eq "none" || "[lindex $elts 6]" eq ""} {
    set elts [lreplace $elts 6 6 "2099-12-31"]
  }
  foreach dbelt $elts \
	  type {string string string string string string string string string 
		int int string string string} {
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


#**** Code removed -- this will be handled by a daily scheduled event


#	Edit if your WP prefix is other then wp_
#puts "delete from wp_DWS_GAN where EndDate<CURDATE();"
#if {[catch {::mysql::exec $DB "delete from wp_DWS_GAN where EndDate<CURDATE()"} result]} {
#  puts "-- DB Error: $result"
#} else {
#  puts "-- DB Result: $result"
#}


::mysql::close $DB

  
  
