<?php

/* GAN_Database.php -- Database class for accessing the database in various
 * ways.
 *
 * These are all static member functions -- no instances of the class are
 * ever created.
 */

class GAN_Database {
  static function database_version () {
    global $wpdb;
    if ($wpdb->get_var("SHOW TABLES LIKE '" .GAN_AD_TABLE. "'") != GAN_AD_TABLE) {
	return 0.0;
    }
    $advertrow = $wpdb->get_row('DESCRIBE '.GAN_AD_TABLE.' Advertiser', 'ARRAY_A' );
    if (count($advertrow) < 1) {
	return 3.0;
    } else {
	return 1.0;
    }
  }

  /* Create ad table.  This table holds the ads themselves. It corresponds to the
   * fields passed in the CSV passed in link subscription E-Mails
   */
  static function make_ad_table() {
    $columns = array ( 'id' => 'int NOT NULL AUTO_INCREMENT',	/* ID */
	// Moved to merch table   'Advertiser' => 'varchar(255) NOT NULL' , /* Advertiser name */
		       'LinkID'     => 'varchar(16)  NOT NULL' , /* Link ID */
		       'LinkName'   => 'varchar(255) NOT NULL' , /* Link Name */
		       'MerchandisingText' => 'varchar(255)' ,   /* Merch text */
		       'AltText' => 'varchar(255)' ,		 /* Alt text */
		       'StartDate' => 'date NOT NULL' ,		 /* Start date */
		       'EndDate'   => 'date NOT NULL' ,		 /* End data */
		       'ClickserverLink' => 'varchar(255) NOT NULL' , /* Ad link */
		       'ImageURL' => 'varchar(255)' ,		 /* URL of image */
		       'ImageHeight' => 'int default 0' ,	 /* height of image */
		       'ImageWidth' => 'int default 0' ,	 /* width of image */
		       'LinkURL' => 'varchar(255) NOT NULL' ,	 /* Link URL */
		       'PromoType' => 'varchar(32) NOT NULL' ,   /* Type of promo */
		       'MerchantID'  => 'varchar(16) NOT NULL',  /* Merchant ID */
	// column dropped (not used)   'side' => 'boolean NOT NULL',		 /* side (not used) */
		       'enabled' => 'boolean NOT NULL',		 /* enabled flag */
	// Columns added (from ad stats table):
		       'LastRunDate' => "date default '1970-01-01'" , /* Last Impression data */
		       'Impressions' => 'int default 0',	/* Impression count */
		       'PRIMARY' => 'KEY (id)'
		     );
    global $wpdb;
    $sql = "CREATE TABLE " . GAN_AD_TABLE . '(';
    foreach($columns as $column => $option) {
       $sql .= "{$column} {$option}, ";
    }
    $sql = rtrim($sql, ', ') . ")";
    if($wpdb->get_var("SHOW TABLES LIKE '" . GAN_AD_TABLE . "'") != GAN_AD_TABLE) {
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);
    }
  }
  /* Make Merchants table -- contains the MerchantID, the Advertiser name, 
   * number of impressions, and the date of the last impression.
   */
  static function make_merchs_table() {
    $columns = array ( 'id' => 'int NOT NULL AUTO_INCREMENT',	/* ID */
		       'Advertiser' => 'varchar(255) NOT NULL' , /* Advertiser name */
		       'MerchantID' => 'varchar(16) NOT NULL' , /* Merchant ID */
		       'LastRunDate' => "date default '1970-01-01'" , /* Last Impression data */
		       'Impressions' => 'int default 0', /* Impression count */
		       'PRIMARY' => 'KEY (id)',
		       'INDEX'     => '(MerchantID)' );
    global $wpdb;
    $sql = "CREATE TABLE " . GAN_MERCH_TABLE . '(';
    foreach($columns as $column => $option) {
	$sql .= "{$column} {$option}, ";
    }
    $sql = rtrim($sql, ', ') . ")";
    if($wpdb->get_var("SHOW TABLES LIKE '" . GAN_MERCH_TABLE . "'") != GAN_MERCH_TABLE) {
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	$result = dbDelta($sql);
    }
  }
  static function upgrade_database() {
    global $wpdb;
    $dbversion = GAN_Database::database_version();
    //file_put_contents("php://stderr","*** GAN_Database::upgrade_database: dbversion = ".$dbversion."\n");
    if ($dbversion == 3.0) return;
    if ($dbversion == 0.0) {
      GAN_Database::make_ad_table();
      GAN_Database::make_merchs_table();
      return;
    }
    //file_put_contents("php://stderr","*** GAN_Database::upgrade_database: making merchs table\n");
    GAN_Database::make_merchs_table();
    //file_put_contents("php://stderr","*** GAN_Database::upgrade_database: populating merchs table\n");
    $query = 'SELECT DISTINCT MerchantID,Advertiser from '.GAN_AD_TABLE;
    //file_put_contents("php://stderr","*** GAN_Database::upgrade_database: query = ".$query."\n");
    $merchs = $wpdb->get_results($query, 'ARRAY_A' );
    //file_put_contents("php://stderr","*** GAN_Database::upgrade_database: merchs = ".print_r((array)$merchs,true)."\n");
    foreach ((array)$merchs as $merch) {
      //file_put_contents("php://stderr","*** GAN_Database::upgrade_database: inserting ".print_r($merch,true)." into merchs table\n");
      $wpdb->insert(GAN_MERCH_TABLE,$merch,array("%s","%s"));
    }
    $query = 'SELECT DISTINCT MerchantID,Impressions,LastRunDate from '.
		GAN_MERCH_STATS_TABLE;
    $merch_stats = $wpdb->get_results($query, 'ARRAY_A' );
    foreach ((array)$merch_stats as $merch_stat) {
      //file_put_contents("php://stderr","*** GAN_Database::upgrade_database: inserting ".print_r($merch_stat,true)." into merchs table\n");
      $wpdb->update( GAN_MERCH_TABLE, array("Impressions" => $merch_stat['Impressions'],
					  "LastRunDate" => $merch_stat['LastRunDate']),
				    array("MerchantID" => $merch_stat['MerchantID']),
				    array("%d","%s"), "%s");
    }
    //file_put_contents("php://stderr","*** GAN_Database::upgrade_database: dropping ".GAN_MERCH_STATS_TABLE." and ".GAN_MERCH_STATS_TABLE_VIEW."\n");
    $wpdb->query('DROP TABLE '.GAN_MERCH_STATS_TABLE);
    $wpdb->query('DROP VIEW  '.GAN_MERCH_STATS_TABLE_VIEW);
    //file_put_contents("php://stderr","*** GAN_Database::upgrade_database: Altering ".GAN_AD_TABLE.": dropping Advertiser and side columns\n");
    $wpdb->query('ALTER TABLE '.GAN_AD_TABLE.' DROP COLUMN Advertiser');
    $wpdb->query('ALTER TABLE '.GAN_AD_TABLE.' DROP COLUMN side');
    //file_put_contents("php://stderr","*** GAN_Database::upgrade_database: Altering ".GAN_AD_TABLE.": adding stats columns\n");
    $sql = 'ALTER TABLE '.GAN_AD_TABLE.' ADD COLUMN (';
    $columns = array (
		       'LastRunDate' => "date default '1970-01-01'" , /* Last Impression data */
		       'Impressions' => 'int default 0',	/* Impression count */
		     );
    foreach($columns as $column => $option) {
      $sql .= "{$column} {$option}, ";
    }
    $sql = rtrim($sql, ', ') . ")";
    //file_put_contents("php://stderr","*** GAN_Database::upgrade_database: sql = ".$sql."\n");
    $wpdb->query($sql);
    $query = 'SELECT DISTINCT adid,Impressions,LastRunDate from '.
		GAN_AD_STATS_TABLE;
    //file_put_contents("php://stderr","*** GAN_Database::upgrade_database: merging in ad stats\n");
    $ad_stats = $wpdb->get_results($query, 'ARRAY_A' );
    foreach ((array)$ad_stats as $ad_stat) {
      //file_put_contents("php://stderr","*** GAN_Database::upgrade_database: inserting ".print_r($ad_stat,true)."\n");
      $wpdb->update( GAN_AD_TABLE, array("Impressions" => $ad_stat['Impressions'],
					  "LastRunDate" => $ad_stat['LastRunDate']),
				    array("id" => $ad_stat['adid']),
				    array("%d","%s"), "%d");
    }
    //file_put_contents("php://stderr","*** GAN_Database::upgrade_database: Dropping ".GAN_AD_STATS_TABLE." and ".GAN_AD_STATS_TABLE_VIEW."\n");
    $wpdb->query('DROP TABLE '.GAN_AD_STATS_TABLE);
    $wpdb->query('DROP VIEW  '.GAN_AD_STATS_TABLE_VIEW);
  }
  /*
   * Return an array of ordered merchants (advertisers).  Returns only
   * merchants with enabled ads, with valid dates, with ads of the
   * specified size ( 0,0 == text ads).  The results are ordered by
   * merchant Impressions -- the least viewed merchant (advertiser) is first.
   */
  static function ordered_merchants($height,$width) {
    global $wpdb;
    if (GAN_Database::database_version() < 3.0) {
      $sql = $wpdb->prepare(
		   "SELECT DISTINCT MerchantID FROM ".GAN_MERCH_STATS_TABLE_VIEW.
		   " WHERE ImageHeight = %d && ImageWidth = %d &&".
		   " enabled = 1 &&".
		   " CURDATE() >= StartDate && CURDATE() < EndDate".
		   " ORDER BY Impressions",$height,$width);
    } else {
      $sql = $wpdb->prepare(
		   "SELECT DISTINCT M.MerchantID FROM ".GAN_MERCH_TABLE.' M,'.
			GAN_AD_TABLE.' A'.
		   " WHERE A.ImageHeight = %d && A.ImageWidth = %d &&".
		   " A.MerchantID = M.MerchantID &&".
		   " A.enabled = 1 &&".
		   " CURDATE() >= A.StartDate && CURDATE() < A.EndDate".
		   " ORDER BY M.Impressions",$height,$width);
    }		   
    return $wpdb->get_col($sql);
  }
  /*
   * Return an array of ordered ads for a given merchant (advertiser).  
   * Returns only enabled ads, with valid dates, with ads of the
   * specified size ( 0,0 == text ads).  The results are ordered by
   * ad Impressions -- the least viewed ad is first.
   */
  static function ordered_ads($height,$width,$merch) {
    global $wpdb;
    if (GAN_Database::database_version() < 3.0) {
      $sql = $wpdb->prepare(
		"SELECT DISTINCT adid FROM ".GAN_AD_STATS_TABLE_VIEW.
		" WHERE ImageHeight = %d && ImageWidth = %d &&".
		" enabled = 1 && MerchantID = %s &&".
		" CURDATE() >= StartDate && CURDATE() < EndDate".
		" ORDER BY Impressions",$height,$width,$merch);
    } else {    
      $sql = $wpdb->prepare(
		"SELECT DISTINCT id FROM ".GAN_AD_TABLE.
		" WHERE ImageHeight = %d && ImageWidth = %d &&".
		" enabled = 1 && MerchantID = %s &&".
		" CURDATE() >= StartDate && CURDATE() < EndDate".
		" ORDER BY Impressions",$height,$width,$merch);
    }
    return $wpdb->get_col($sql);
  }
  /* Get a specified ad by its id. The complete ad's row is returned as an
   * associative array.
   */
  static function get_ad($id,$format = 'ARRAY_A') {
    global $wpdb;
    $sql = $wpdb->prepare("SELECT * FROM ".GAN_AD_TABLE.
			  " WHERE ID = %d",$id);
    $result = $wpdb->get_row($sql, $format );
    if ($format == 'ARRAY_A' && !isset($result['Advertiser'])) {
      $result['Advertiser'] = GAN_Database::get_merch_name($result['MerchantID']);
    } else if (!isset($result->Advertiser)) {
      $result->Advertiser = GAN_Database::get_merch_name($result->MerchantID);
    }
    return $result;
  }
  static function get_blank_ad() {
    return (object) array(
	'Advertiser' => '', 'LinkID' => '', 'LinkName' => '',
	'MerchandisingText' => '', 'AltText' => '',
	'StartDate' => date('n/j/Y',time()), 
	'EndDate' => date('n/j/Y',time()+60*60*24*7),
	'ClickserverLink' => '', 'ImageURL' => '',
	'ImageHeight' => 0, 'ImageWidth' => 0,
	'LinkURL' => '', 'PromoType' => '', 'MerchantID' => '',
	'enabled' => 0);
  }
  static function init_counts($id) {
    if (GAN_Database::database_version() >= 3.0) return;
    //file_put_contents("php://stderr","*** GAN_Database::init_counts(".$id.")\n");
    global $wpdb;
    $merchid = $wpdb->get_var($wpdb->prepare("SELECT MerchantID FROM ".
					     GAN_AD_TABLE.
					     " WHERE ID = %d",$id));
    //file_put_contents("php://stderr","*** -: merchid = ".$merchid."\n");
    $merchcount = $wpdb->get_var($wpdb->prepare("SELECT count(Impressions) FROM ".
						GAN_MERCH_STATS_TABLE.
						" WHERE MerchantID = %s",
						$merchid));
    //file_put_contents("php://stderr","*** -: merchcount = ".$merchcount."\n");
    if ($merchcount == 0) {
      $wpdb->insert(GAN_MERCH_STATS_TABLE,array("MerchantID" => $merchid),"%s");
    }
    $adcount = $wpdb->get_var($wpdb->prepare("SELECT count(Impressions) FROM ".
					    GAN_AD_STATS_TABLE.
					    " WHERE adid = %d",$id));
    //file_put_contents("php://stderr","*** -: adcount = ".$adcount."\n");
    if ($adcount == 0) {
      $wpdb->insert(GAN_AD_STATS_TABLE,array("adid" => $id),"%d");
    }
  }
  static function PopulateStatsTables() {
    if (GAN_Database::database_version() >= 3.0) return;
    global $wpdb;
    $alladids = $wpdb->get_col("SELECT id from ".GAN_AD_TABLE);
    foreach ($alladids as $adid) {
      GAN_Database::init_counts($adid);
    }
  }
  /* Update an ad's impression counts (merchant and the ad itself). If the
   * ad or merchant does not already have a impression count row, it is
   * created.
   */
  static function bump_counts($id) {
    //file_put_contents("php://stderr","*** GAN_Database::bump_counts(".$id.")\n");
    GAN_Database::init_counts($id);
    global $wpdb;
    $merchid = $wpdb->get_var($wpdb->prepare("SELECT MerchantID FROM ".
					     GAN_AD_TABLE.
					     " WHERE ID = %d",$id));
    //file_put_contents("php://stderr","*** -: merchid = ".$merchid."\n");
    if (GAN_Database::database_version() < 3.0) {
      $merchseen = $wpdb->get_var($wpdb->prepare("SELECT Impressions FROM ".
						GAN_MERCH_STATS_TABLE.
						" WHERE MerchantID = %s",
						$merchid));
    //file_put_contents("php://stderr","*** -: merchseen = ".$merchseen."\n");
      $wpdb->update( GAN_MERCH_STATS_TABLE,
		   array("Impressions" => $merchseen + 1,
			 "LastRunDate" => date('Y-m-d')),
		   array("MerchantID" => $merchid),
		   array("%d","%s"), "%s");
      $adseen = $wpdb->get_var($wpdb->prepare("SELECT Impressions FROM ".
					    GAN_AD_STATS_TABLE.
					    " WHERE adid = %d",$id));
    //file_put_contents("php://stderr","*** -: adseen = ".$adseen."\n");
      $wpdb->update( GAN_AD_STATS_TABLE,
		   array("Impressions" => $adseen + 1,
		         "LastRunDate" => date('Y-m-d')),
		   array("adid" => $id),
		   array("%d","%s"), "%d");
    } else {
      $merchseen = $wpdb->get_var($wpdb->prepare("SELECT Impressions FROM ".
					GAN_MERCH_TABLE.
					" WHERE MerchantID = %s",
					$merchid));
      $wpdb->update( GAN_MERCH_TABLE,
			array("Impressions" => $merchseen + 1,
			      "LastRunDate" => date('Y-m-d')),
			array("MerchantID" => $merchid),
			array("%d","%s"), "%s");
      $adseen = $wpdb->get_var($wpdb->prepare("SELECT Impressions FROM ".
					    GAN_AD_TABLE.
					    " WHERE id = %d",$id));
    //file_put_contents("php://stderr","*** -: adseen = ".$adseen."\n");
      $wpdb->update( GAN_AD_TABLE,
		   array("Impressions" => $adseen + 1,
		         "LastRunDate" => date('Y-m-d')),
		   array("id" => $id),
		   array("%d","%s"), "%d");
    }
  }
  static function delete_ad_by_id($id) {
    global $wpdb;
    $sql = $wpdb->prepare("select DISTINCT MerchantID " . GAN_AD_TABLE . ' where id = %d',$id);
    $MerchantID = $wpdb->get_var($sql);
    $sql = $wpdb->prepare("delete from " . GAN_AD_TABLE . ' where id = %d',$id);
    $wpdb->query($sql);
    if (GAN_Database::database_version() < 3.0) {
      $sql = $wpdb->prepare("delete from " . GAN_AD_STATS_TABLE . ' where adid = %d',$id);
      $wpdb->query($sql);
    }
    $sql = $wpdb->prepare("select count(*) from ".GAN_AD_TABLE. ' where MerchantID = %s',$MerchantID);
    if ($wpdb->get_var($sql) == 0) {
      if (GAN_Database::database_version() < 3.0) {
	$sql = $wpdb->prepare("delete from " . GAN_MERCH_STATS_TABLE . " where MerchantID = %s",$MerchantID);
      } else {
	$sql = $wpdb->prepare("delete from " . GAN_MERCH_TABLE . " where MerchantID = %s",$MerchantID);
      }
      $wpdb->query($sql);
    }
  }
  static function toggle_enabled($id) {
    global $wpdb;
    $sql = $wpdb->prepare("SELECT enabled FROM " . GAN_AD_TABLE . ' where id = %s',$id);
    $curenabled = $wpdb->get_var($sql);
    if ($curenabled == 0) {$newenabled = 1;}
    else {$newenabled = 0;}
    $sql = $wpdb->prepare("update " . GAN_AD_TABLE . " set enabled=%d where id = %d",$newenabled,$id);
    $wpdb->query($sql);
  }
  static function enableall($where) {
    global $wpdb;
    $wpdb->query("update " . GAN_AD_TABLE . " set enabled=true" . $where);
  }
  static function deleteexpired($wand) {
    global $wpdb;
    $idstodelete = $wpdb->get_col("select id from " . GAN_AD_TABLE . ' where EndDate<CURDATE() ' . $wand);
    foreach ($idstodelete as $id) {
      GAN_Database::delete_ad_by_id($id);
    }
  }
  static function get_GAN_data($where,$format = 'ARRAY_A') {
    global $wpdb;
    return $wpdb->get_results("SELECT id,MerchantID,LinkID,ImageWidth,LinkName,StartDate,EndDate,Enabled FROM " . GAN_AD_TABLE . $where . ' Order by EndDate', $format);
  }
  static function get_GAN_row_count($where) {
    global $wpdb;
    return $wpdb->get_var("SELECT count(*) FROM " . GAN_AD_TABLE . $where);
  }
  static function insert_GAN($Advertiser,$LinkID,$LinkName,$MerchandisingText,
			     $AltText,$StartDate,$EndDate,$ClickserverLink,
			     $ImageURL,$ImageHeight,$ImageWidth,$LinkURL,
			     $PromoType,$MerchantID,$enabled) {
    global $wpdb;
    if (preg_match("/^[[:digit:]]/",$LinkID)) {$LinkID = 'J'.$LinkID;}
    if (preg_match("/^[[:digit:]]/",$MerchantID)) {$MerchantID = 'K'.$MerchantID;}
    if (GAN_Database::database_version() < 3.0) {
      $wpdb->insert(GAN_AD_TABLE,array('Advertiser' => $Advertiser,
				       'LinkID' => $LinkID,
				       'LinkName' => $LinkName,
				       'MerchandisingText' => $MerchandisingText,
				       'AltText' => $AltText,
				       'StartDate' => $StartDate,
				       'EndDate' => $EndDate,
				       'ClickserverLink' => $ClickserverLink,
				       'ImageURL' => $ImageURL,
				       'ImageHeight' => $ImageHeight,
				       'ImageWidth' => $ImageWidth,
				       'LinkURL' => $LinkURL,
				       'PromoType' => $PromoType,
				       'MerchantID' => $MerchantID,
				       'enabled' => $enabled),
				array("%s","%s","%s","%s","%s","%s","%s","%s",
				      "%s","%s","%s","%s","%s","%s","%d"));
      $newid = $wpdb->insert_id;
      GAN_Database::init_counts($newid);
    } else {
      $wpdb->insert(GAN_AD_TABLE,array('LinkID' => $LinkID,
				       'LinkName' => $LinkName,
				       'MerchandisingText' => $MerchandisingText,
				       'AltText' => $AltText,
				       'StartDate' => $StartDate,
				       'EndDate' => $EndDate,
				       'ClickserverLink' => $ClickserverLink,
				       'ImageURL' => $ImageURL,
				       'ImageHeight' => $ImageHeight,
				       'ImageWidth' => $ImageWidth,
				       'LinkURL' => $LinkURL,
				       'PromoType' => $PromoType,
				       'MerchantID' => $MerchantID,
				       'enabled' => $enabled),
				array("%s","%s","%s","%s","%s","%s","%s",
				      "%s","%s","%s","%s","%s","%s","%d"));
      $newid = $wpdb->insert_id;
      $c = $wpdb->get_var($wpdb->prepare('SELECT count(*) from '.
				GAN_MERCH_TABLE.
				    ' Where MerchantID = %s',$MerchantID));
      if ($c == 0) {
	$wpdb->insert(GAN_MERCH_TABLE,array('Advertiser' => $Advertiser,
					    'MerchantID' => $MerchantID),
			array("%s","%s"));
      } else {
	$oldadname = $wpdb->get_var($wpdb->prepare('SELECT Advertiser from '.
					GAN_MERCH_TABLE.
					' Where MerchantID = %s',$MerchantID));
	if ($oldadname != $Advertiser) {
	  $wpdb->update(GAN_MERCH_TABLE,array('Advertiser' => $Advertiser),
					array('MerchantID' => $MerchantID),
					"%s","%s");
	}
      }
    }
    return $newid;
  }
  static function update_GAN($id,$Advertiser,$LinkID,$LinkName,$MerchandisingText,
			     $AltText,$StartDate,$EndDate,$ClickserverLink,
			     $ImageURL,$ImageHeight,$ImageWidth,$LinkURL,
			     $PromoType,$MerchantID,$enabled) {
    global $wpdb;
    if (preg_match("/^[[:digit:]]/",$LinkID)) {$LinkID = 'J'.$LinkID;}
    if (preg_match("/^[[:digit:]]/",$MerchantID)) {$MerchantID = 'K'.$MerchantID;}
    if (GAN_Database::database_version() < 3.0) {
      $wpdb->update(GAN_AD_TABLE,array('Advertiser' => $Advertiser,
				     'LinkID' => $LinkID,
				     'LinkName' => $LinkName,
				     'MerchandisingText' => $MerchandisingText,
				     'AltText' => $AltText,
				     'StartDate' => $StartDate,
				     'EndDate' => $EndDate,
				     'ClickserverLink' => $ClickserverLink,
				     'ImageURL' => $ImageURL,
				     'ImageHeight' => $ImageHeight,
				     'ImageWidth' => $ImageWidth,
				     'LinkURL' => $LinkURL,
				     'PromoType' => $PromoType,
				     'MerchantID' => $MerchantID,
				     'enabled' => $enabled),
				array('id' => $id),
				array("%s","%s","%s","%s","%s","%s","%s","%s",
				      "%s","%s","%s","%s","%s","%s","%d"),
				array("%d"));
    } else {
      $wpdb->update(GAN_AD_TABLE,array('LinkID' => $LinkID,
				     'LinkName' => $LinkName,
				     'MerchandisingText' => $MerchandisingText,
				     'AltText' => $AltText,
				     'StartDate' => $StartDate,
				     'EndDate' => $EndDate,
				     'ClickserverLink' => $ClickserverLink,
				     'ImageURL' => $ImageURL,
				     'ImageHeight' => $ImageHeight,
				     'ImageWidth' => $ImageWidth,
				     'LinkURL' => $LinkURL,
				     'PromoType' => $PromoType,
				     'MerchantID' => $MerchantID,
				     'enabled' => $enabled),
				array('id' => $id),
				array("%s","%s","%s","%s","%s","%s","%s",
				      "%s","%s","%s","%s","%s","%s","%d"),
				array("%d"));
      $c = $wpdb->get_var($wpdb->prepare('SELECT count(*) from '.
				GAN_MERCH_TABLE.
				    ' Where MerchantID = %s',$MerchantID));
      if ($c == 0) {
	$wpdb->insert(GAN_MERCH_TABLE,array('Advertiser' => $Advertiser,
					    'MerchantID' => $MerchantID),
			array("%s","%s"));
      } else {
	$oldadname = $wpdb->get_var($wpdb->prepare('SELECT Advertiser from '.
					GAN_MERCH_TABLE.
					' Where MerchantID = %s',$MerchantID));
	if ($oldadname != $Advertiser) {
	  $wpdb->update(GAN_MERCH_TABLE,array('Advertiser' => $Advertiser),
					array('MerchantID' => $MerchantID),
					"%s","%s");
	}
      }
    }
  }
  static function get_merchants() {
    global $wpdb;
    if (GAN_Database::database_version() < 3.0) {
      return $wpdb->get_results("SELECT distinct Advertiser,MerchantID from  " . GAN_AD_TABLE . " order by Advertiser", 'ARRAY_A');
    } else {
      return $wpdb->get_results("SELECT distinct Advertiser,MerchantID from  " . GAN_MERCH_TABLE . " order by Advertiser", 'ARRAY_A');
    }
  }
  static function get_imagewidths() {
    global $wpdb;
    return $wpdb->get_results("SELECT distinct ImageWidth from  " . GAN_AD_TABLE . " order by ImageWidth", 'ARRAY_A');
  }
  static function total_ads() {
    global $wpdb;
    return $wpdb->get_var("SELECT count(*) FROM " . GAN_AD_TABLE);
  }
  static function disabled_count() {
    global $wpdb;
    return $wpdb->get_var("SELECT count(*) FROM " . GAN_AD_TABLE . ' where Enabled = 0');
  }
  static function advertiser_count() {
    global $wpdb;
    if (GAN_Database::database_version() < 3.0) {
      return $wpdb->get_var("SELECT count(distinct MerchantID) FROM " . GAN_AD_TABLE);
    } else {
      return $wpdb->get_var("SELECT count(distinct MerchantID) FROM " . GAN_MERCH_TABLE);
    }
  }
  static function width_count() {
    global $wpdb;
    return $wpdb->get_var("SELECT count(distinct ImageWidth) FROM " . GAN_AD_TABLE);
  }
  static function top_merch() {
    global $wpdb;
    if (GAN_Database::database_version() < 3.0) {
      return $wpdb->get_results("SELECT Impressions, MerchantID from ".
				      GAN_MERCH_STATS_TABLE.
				    " order by Impressions DESC LIMIT 5", 'ARRAY_A');
    } else {
      return $wpdb->get_results("SELECT Impressions, MerchantID from ".
				      GAN_MERCH_TABLE.
				    " order by Impressions DESC LIMIT 5", 'ARRAY_A');
    }
  }
  static function max_merch_impressions() {
    global $wpdb;
    if (GAN_Database::database_version() < 3.0) {
      return $wpdb->get_var("SELECT MAX(Impressions) from ".
				GAN_MERCH_STATS_TABLE);
    } else {
      return $wpdb->get_var("SELECT MAX(Impressions) from ".
				GAN_MERCH_TABLE);
    }
  }
  static function merch_statistics() {
    global $wpdb;
    if (GAN_Database::database_version() < 3.0) {
      return $wpdb->get_row("SELECT MAX(Impressions) maximum, ".
	  			 "MIN(Impressions) minimum, ".
				 "AVG(Impressions) average, ".
				 "STDDEV(Impressions) std_deviation, ".
				 "VARIANCE(Impressions) variance FROM ".
				GAN_MERCH_STATS_TABLE, 'ARRAY_A');
    } else {
      return $wpdb->get_row("SELECT MAX(Impressions) maximum, ".
	  			 "MIN(Impressions) minimum, ".
				 "AVG(Impressions) average, ".
				 "STDDEV(Impressions) std_deviation, ".
				 "VARIANCE(Impressions) variance FROM ".
				GAN_MERCH_TABLE, 'ARRAY_A');
    }
  }
  static function top_ads() {
    global $wpdb;
    if (GAN_Database::database_version() < 3.0) {
      return $wpdb->get_results("SELECT Impressions, adid from ".
				    GAN_AD_STATS_TABLE.
				    " order by Impressions DESC LIMIT 5", 'ARRAY_A');
    } else {
      return $wpdb->get_results("SELECT Impressions, id adid from ".
				    GAN_AD_TABLE.
				    " order by Impressions DESC LIMIT 5", 'ARRAY_A');
    }
  }
  static function ad_statistics() {
    global $wpdb;
    if (GAN_Database::database_version() < 3.0) {
      return $wpdb->get_row("SELECT MAX(Impressions) maximum, ".
	  				            "MIN(Impressions) minimum, ".
						    "AVG(Impressions) average, ".
						    "STDDEV(Impressions) std_deviation, ".
						    "VARIANCE(Impressions) variance FROM ".
						GAN_AD_STATS_TABLE, 'ARRAY_A');
    } else {
      return $wpdb->get_row("SELECT MAX(Impressions) maximum, ".
	  				            "MIN(Impressions) minimum, ".
						    "AVG(Impressions) average, ".
						    "STDDEV(Impressions) std_deviation, ".
						    "VARIANCE(Impressions) variance FROM ".
						GAN_AD_TABLE, 'ARRAY_A');
    }
  }
  static function max_ad_impressions() {
    global $wpdb;
    if (GAN_Database::database_version() < 3.0) {
      return $wpdb->get_var("SELECT MAX(Impressions) from ".
					 	GAN_AD_STATS_TABLE);
    } else {
      return $wpdb->get_var("SELECT MAX(Impressions) from ".
					 	GAN_AD_TABLE);
    }
  }
  static function get_merch_name($merchid) {
    global $wpdb;
    if (GAN_Database::database_version() < 3.0) {
      $sql = $wpdb->prepare("SELECT DISTINCT Advertiser FROM ".GAN_AD_TABLE.
			  " WHERE MerchantID = %s",$merchid);
    } else {
      $sql = $wpdb->prepare("SELECT DISTINCT Advertiser FROM ".GAN_MERCH_TABLE.
			  " WHERE MerchantID = %s",$merchid);
    }
    //file_put_contents("php://stderr","*** GAN_Database::get_merch_name: sql = ".$sql."\n");
    $adv = $wpdb->get_var($sql);
    //file_put_contents("php://stderr","*** GAN_Database::get_merch_name: adv = ".$adv."\n");
    return $adv;
  }
  static function get_link_name($id) {
    global $wpdb;
    $sql = $wpdb->prepare("SELECT DISTINCT LinkName FROM ".GAN_AD_TABLE.
				" WHERE ID = %d",$id);
    return $wpdb->get_var($sql);
  }
  static function get_link_id($id) {
    global $wpdb;
    $sql = $wpdb->prepare("SELECT DISTINCT LinkID FROM ".GAN_AD_TABLE.
				" WHERE ID = %s",$id);
    return $wpdb->get_var($sql);
  }
  static function get_GAN_AD_VIEW_data($where) {
    global $wpdb;
    if (GAN_Database::database_version() < 3.0) {
      return $wpdb->get_results("SELECT * FROM " . GAN_AD_STATS_TABLE_VIEW . 
				$where . ' Order by Impressions, EndDate', 
				'ARRAY_A');
    } else {
      return $wpdb->get_results("SELECT id, id adid, LastRunDate, Impressions,".
				" ImageHeight, ImageWidth, MerchantID,".
				" enabled, StartDate, EndDate  FROM " . 
				GAN_AD_TABLE . $where . 
				' Order by Impressions, EndDate', 'ARRAY_A');
    }
  }
  static function get_GAN_AD_VIEW_row_count($where) {
    global $wpdb;
    if (GAN_Database::database_version() < 3.0) {
      return $wpdb->get_var("SELECT count(*) FROM " . GAN_AD_STATS_TABLE_VIEW . $where);
    } else {
      return $wpdb->get_var("SELECT count(*) FROM " . GAN_AD_TABLE . $where);
    }
  }
  static function zero_GAN_AD_STAT($id) {
    global $wpdb;
    if (GAN_Database::database_version() < 3.0) {
      $sql = $wpdb->prepare("update " . GAN_AD_STATS_TABLE . " set Impressions=0,LastRunDate='1970-01-01' where id = %d",$id);
    } else {
      $sql = $wpdb->prepare("update " . GAN_AD_TABLE . " set Impressions=0,LastRunDate='1970-01-01' where id = %d",$id);
    }
    $wpdb->query($sql);
  }
  static function zero_GAN_AD_STATS($where) {
    global $wpdb;
    if (GAN_Database::database_version() < 3.0) {
      $wpdb->query("update " . GAN_AD_STATS_TABLE . " set Impressions=0,LastRunDate='1970-01-01' ".$where);
    } else {
      $wpdb->query("update " . GAN_AD_TABLE . " set Impressions=0,LastRunDate='1970-01-01' ".$where);
    }
  }
  static function get_GAN_MERCH_STATS_data($where) {
    global $wpdb;
    if (GAN_Database::database_version() < 3.0) {
      return $wpdb->get_results("SELECT * FROM " . GAN_MERCH_STATS_TABLE . $where . ' Order by Impressions', 'ARRAY_A');
    } else {
      return $wpdb->get_results("SELECT * FROM " . GAN_MERCH_TABLE . $where . ' Order by Impressions', 'ARRAY_A');
    }
  }
  static function get_GAN_MERCH_STATS_row_count($where) {
    global $wpdb;
    if (GAN_Database::database_version() < 3.0) {
      return $wpdb->get_var("SELECT count(*) FROM " . GAN_MERCH_STATS_TABLE . $where);
    } else {
      return $wpdb->get_var("SELECT count(*) FROM " . GAN_MERCH_TABLE . $where);
    }
  }
  static function zero_GAN_MERCH_STAT($id) {
    global $wpdb;
    if (GAN_Database::database_version() < 3.0) {
      $sql = $wpdb->prepare("update " . GAN_MERCH_STATS_TABLE . " set Impressions=0,LastRunDate='1970-01-01'  where id = %d",$id);
    } else {
      $sql = $wpdb->prepare("update " . GAN_MERCH_TABLE . " set Impressions=0,LastRunDate='1970-01-01'  where id = %d",$id);
    }
    $wpdb->query($sql);
  }
  static function zero_GAN_MERCH_STATS($where) {
    global $wpdb;
    if (GAN_Database::database_version() < 3.0) {
      $wpdb->query("update " . GAN_MERCH_STATS_TABLE . " set Impressions=0,LastRunDate='1970-01-01' ".$where);
    } else {
      $wpdb->query("update " . GAN_MERCH_TABLE . " set Impressions=0,LastRunDate='1970-01-01' ".$where);
    }
  }
  /*
   * Create merchant dropdown list
   */

  static function merchdropdown ($merchid,$name='merchid') {
    $GANMerchants = GAN_Database::get_merchants();
	
    ?><label for="gan-merchid"><?php _e('Advertisers:','gan'); ?></label>
      <select name="<?php echo $name; ?>" id="gan-merchid" maxlength="20">
      <option value="" <?php 
	if ( $merchid == "" ) echo 'selected="selected"'; 
	?>><?php _e('All','gan'); ?></option><?php
      foreach ((array)$GANMerchants as $GANMerchant) {
        $shortadvert = substr($GANMerchant['Advertiser'],0,25);
        ?><option value="<?php echo $GANMerchant['MerchantID']; ?>" <?php 
	if ( $merchid == $GANMerchant['MerchantID'] ) 
		echo 'selected="selected"'; 
	?> label="<?php echo $GANMerchant['Advertiser']; 
	?>"><?php echo $shortadvert; ?></option><?php 
      }
    ?></select><?php
  }

  /*
   * Make a image width dropdown list
   */

  function imwidthdropdown ($imwidth,$name = 'imwidth') {
    $GANImageWidths = GAN_Database::get_imagewidths();
  
    ?><label for="gan-imwidth"><?php _e('Image Width:','gan'); ?></label>
      <select name="<?php echo $name; ?>" id="gan-imwidth" maxlength="4">
      <option value="-1" <?php 
    if ( $imwidth == "-1" ) echo 'selected="selected"'; 
    ?>><?php _e('All','gan'); ?></option><?php
    foreach ((array)$GANImageWidths as $GANImageWidth) {
      ?><option value="<?php echo $GANImageWidth['ImageWidth']; ?>" <?php 
    if ( $imwidth == $GANImageWidth['ImageWidth'] ) 
      echo 'selected="selected"'; 
    ?>><?php echo $GANImageWidth['ImageWidth']; ?></option><?php 
    }
    ?></select><?php
  }
}

?>
