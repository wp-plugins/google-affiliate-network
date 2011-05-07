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
# Links are added to the database with side=right and enabled=true.
#
# This stript expects what Google calls a 'Tab Separated Value' file, downloaded
# from the Links tab on the Google Affliate Network page.
#
# DB Access -- edit these three lines to code in your DB access info.
set USER     {username}
set PASSWORD {password}
set DATABASE {databasename}
# Change this line if your db prefix is other then 'wp_'.
set WPPrefix {wp_}

# No need to edit below this line.

# Database table names
set GAN_AD_TABLE ${WPPrefix}DWS_GAN
set GAN_MERCH_TABLE ${WPPrefix}DWS_GAN_MERCH
# Old (pre V3) tables
set GAN_AD_STATS_TABLE ${WPPrefix}DWS_GAN_AD_STATS
set GAN_MERCH_STATS_TABLE ${WPPrefix}DWS_GAN_MERCH_STATS

set baseversion [package require Tcl]

if {"$::tcl_platform(machine)" eq "x86_64"} {
  lappend auto_path /usr/lib64/tcl$baseversion
} else {
  lappend auto_path /usr/lib/tcl$baseversion
}

package require mysqltcl

set DB [::mysql::connect -user $USER -password $PASSWORD -db $DATABASE]

## Helper code:

# Make sure the date is in a format that MySQL understands and is Y2K clean
proc fixdate {date} {
  if {[regexp {^([[:digit:]]*)/([[:digit:]]*)/([[:digit:]]*)$} "$date" -> m d y] > 0} {
    if {[string length $y] < 4} {set y 20$y}
    return [format {%04d-%02d-%02d} $y $m $d]
  } else {
    return $date
  }
}

# Quote stuff for database insertion, etc.
proc db_quote {s} {
  global DB
  return '[::mysql::escape $DB $s]'
}

proc tsv_unquote {s} {
  if {[regexp {^"(.*)"$} $s -> q]} {
    regsub -all {""} $q {"} q
    return $q
  } else {
    return $s
  }
}

## Functions translated from GAN_Database.php

# Database version
proc database_version {} {
  global DB
  global GAN_AD_TABLE

  set q [::mysql::query $DB "SHOW TABLES LIKE '$GAN_AD_TABLE'"]
  if {[::mysql::fetch $q] ne $GAN_AD_TABLE} {return 0.0}
  ::mysql::endquery $q
  set q [::mysql::query $DB "DESCRIBE $GAN_AD_TABLE Advertiser"]
  set rows [::mysql::result $q rows]
  ::mysql::endquery $q
  if {$rows < 1} {
    return 3.0
  } else {
    return 1.0
  }
}

# V1 database count init
proc init_counts {id} {
  global DB
  global GAN_AD_TABLE
  global GAN_AD_STATS_TABLE
  global GAN_MERCH_STATS_TABLE
  if {[database_version] >= 3.0} {return}
  set q [::mysql::query $DB "SELECT MerchantID FROM $GAN_AD_TABLE WHERE ID = $id"]
  set MerchantID [::mysql::fetch $q]
  ::mysql::endquery $q
  set q [::mysql::query $DB "SELECT count(Impressions) FROM $GAN_MERCH_STATS_TABLE  WHERE MerchantID = [db_quote $MerchantID]"]
  set merchcount [::mysql::fetch $q]
  ::mysql::endquery $q
  if {$merchcount == 0} {
    set insertstmt "INSERT INTO $GAN_MERCH_STATS_TABLE VALUES (MerchantID) ([db_quote $MerchantID])"
    puts "$insertstmt;"
    if {[catch {::mysql::exec $DB $insertstmt} result]} {
      puts "-- DB Error: $result"
    } else {
      puts "-- DB Result: $result"
    }
  }
  set q [::mysql::query $DB "SELECT count(Impressions) FROM $GAN_AD_STATS_TABLE  WHERE adid = $id"]
  set adcount [::mysql::fetch $q]
  ::mysql::endquery $q
  if {$adcount == 0} {
    set insertstmt "INSERT INTO $GAN_AD_STATS_TABLE VALUES (adid) ($id)"
    puts "$insertstmt;"
    if {[catch {::mysql::exec $DB $insertstmt} result]} {
      puts "-- DB Error: $result"
    } else {
      puts "-- DB Result: $result"
    }
  }
}

# Insert a link
proc insert_GAN {Advertiser LinkID LinkName MerchandisingText AltText
		 StartDate EndDate ClickserverLink ImageURL ImageHeight
		 ImageWidth LinkURL PromoType MerchantID {enabled 1}} {
  global DB
  global GAN_AD_TABLE
  global GAN_MERCH_TABLE

  if {[regexp {^[[:digit:]]} "$LinkID"] > 0} {set LinkID J$LinkID}
  if {[regexp {^[[:digit:]]} "$MerchantID"] > 0} {set MerchantID K$MerchantID}
  if {[database_version] < 3.0} {
    set sqlstatement "insert into $GAN_AD_TABLE (Advertiser, LinkID, LinkName, \
			MerchandisingText, AltText, StartDate, EndDate, \
			ClickserverLink, ImageURL, ImageHeight, ImageWidth, \
			LinkURL, PromoType,  MerchantID,enabled) values (\
			[db_quote $Advertiser],[db_quote $LinkID],\
			[db_quote $LinkName],[db_quote $MerchandisingText],\
			[db_quote $AltText],[db_quote $StartDate],\
			[db_quote $EndDate],[db_quote $ClickserverLink],\
			[db_quote $ImageURL],$ImageHeight,$ImageWidth,\
			[db_quote $LinkURL],[db_quote $PromoType],\
			[db_quote $MerchantID],$enabled)"
    puts "$sqlstatement;"
    if {[catch {::mysql::exec $DB $sqlstatement} result]} {
      puts "-- DB Error: $result"
    } else {
      puts "-- DB Result: $result"
    }
    init_counts [::mysql::insertid $DB]
  } else {
    set sqlstatement "insert into $GAN_AD_TABLE (LinkID, LinkName, \
			MerchandisingText, AltText, StartDate, EndDate, \
			ClickserverLink, ImageURL, ImageHeight, ImageWidth, \
			LinkURL, PromoType,  MerchantID,enabled) values (\
			[db_quote $LinkID],\
			[db_quote $LinkName],[db_quote $MerchandisingText],\
			[db_quote $AltText],[db_quote $StartDate],\
			[db_quote $EndDate],[db_quote $ClickserverLink],\
			[db_quote $ImageURL],$ImageHeight,$ImageWidth,\
			[db_quote $LinkURL],[db_quote $PromoType],\
			[db_quote $MerchantID],$enabled)"
    puts "$sqlstatement;"
    if {[catch {::mysql::exec $DB $sqlstatement} result]} {
      puts "-- DB Error: $result"
    } else {
      puts "-- DB Result: $result"
    }
    set q [::mysql::query $DB "SELECT count(*) from $GAN_MERCH_TABLE Where MerchantID = [db_quote $MerchantID]"]
    set c [::mysql::fetch $q]
    ::mysql::endquery $q
    if {$c == 0} {
      set sqlstatement "insert into $GAN_MERCH_TABLE (Advertiser, MerchantID) \
				values ([db_quote $Advertiser],\
					[db_quote $MerchantID])"
      puts "$sqlstatement;"
      if {[catch {::mysql::exec $DB $sqlstatement} result]} {
	puts "-- DB Error: $result"
      } else {
	puts "-- DB Result: $result"
      }
    }
  }
}


while { [gets stdin line] >= 0 } {
  if {[regexp {^"Id"[[:space:]]*"Name"} "$line"] > 0} {break}
}

while { [gets stdin line] >= 0 } {

  set rawelts [split "$line" "\t"]
  if {[llength $rawelts] < 16} {continue}
  
  set elts [list]

  lappend elts [tsv_unquote [lindex $rawelts 3]];# Advertizer
  lappend elts [tsv_unquote [lindex $rawelts 0]];# LinkID
  lappend elts [tsv_unquote [lindex $rawelts 1]];# LinkName
  lappend elts [tsv_unquote [lindex $rawelts 15]];# Merchandising Text
  lappend elts "";# No AltText
  lappend elts [fixdate [string trim [tsv_unquote [lindex $rawelts 8]]]];# StartDate
  set enddate [fixdate [string trim [tsv_unquote [lindex $rawelts 9]]]]
  if {"$enddate" eq "none" || "$enddate" eq ""} {
    lappend elts "2099-12-31";# EndDate (never)
  } else {
    lappend elts $enddate;# EndDate (explicit)
  }
  lappend elts [tsv_unquote [lindex $rawelts 4]];# ClickserverURL
  lappend elts [tsv_unquote [lindex $rawelts 5]];# Image URL

  set width 0
  set height 0
  regexp {^"([[:digit:]]*)x([[:digit:]]*)"$} [lindex $rawelts 6] -> width height
  
  lappend elts $height;# Height
  lappend elts $width;# Width
  lappend elts "";# No Link URL
  lappend elts [tsv_unquote [lindex $rawelts 12]];# Promo Type
  lappend elts [tsv_unquote [lindex $rawelts 2]];# MerchantID

  eval insert_GAN $elts
}

::mysql::close $DB

  
  
