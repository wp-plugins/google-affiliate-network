<?php

/* GAN_Database.php -- Database class for accessing the database in various
 * ways.
 *
 * These are all static member functions -- no instances of the class are
 * ever created.
 */

class GAN_Database {
  /* Create ad table.  This table holds the ads themselves. It corresponds to the
   * fields passed in the CSV passed in link subscription E-Mails
   */
  static function make_ad_table() {
    $columns = array ( 'id' => 'int NOT NULL AUTO_INCREMENT',	/* ID */
		       'Advertiser' => 'varchar(255) NOT NULL' , /* Advertiser name */
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
		       'side' => 'boolean NOT NULL',		 /* side (not used) */
		       'enabled' => 'boolean NOT NULL',		 /* enabled flag */
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
  /* Make Ad statisics table -- contains the number of impressions and the date
   * of the last impression.
   */
  static function make_ad_stats_table() {
    $columns = array ( 'id' => 'int NOT NULL AUTO_INCREMENT',	/* ID */
		       'adid' => 'int NOT NULL' ,		/* Ad ID */
		       'LastRunDate' => "date default '1970-01-01'" , /* Last Impression data */
		       'Impressions' => 'int default 0',	/* Impression count */
		       'PRIMARY' => 'KEY (id)',
		       'INDEX'     => '(adid)' );
    global $wpdb;
    $sql = "CREATE TABLE " . GAN_AD_STATS_TABLE . '(';
    foreach($columns as $column => $option) {
	$sql .= "{$column} {$option}, ";
    }
    $sql = rtrim($sql, ', ') . ")";
    if($wpdb->get_var("SHOW TABLES LIKE '" . GAN_AD_STATS_TABLE . "'") != GAN_AD_STATS_TABLE) {
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);
    }
  }
  /* Make Merchant statisics table -- contains the number of impressions and 
   * the date of the last impression.
   */
  static function make_merch_stats_table() {
    $columns = array ( 'id' => 'int NOT NULL AUTO_INCREMENT',	/* ID */
		       'MerchantID' => 'varchar(16) NOT NULL' , /* Merchant ID */
		       'LastRunDate' => "date default '1970-01-01'" , /* Last Impression data */
		       'Impressions' => 'int default 0', /* Impression count */
		       'PRIMARY' => 'KEY (id)',
		       'INDEX'     => '(MerchantID)' );
    global $wpdb;
    $sql = "CREATE TABLE " . GAN_MERCH_STATS_TABLE . '(';
    foreach($columns as $column => $option) {
	$sql .= "{$column} {$option}, ";
    }
    $sql = rtrim($sql, ', ') . ")";
    if($wpdb->get_var("SHOW TABLES LIKE '" . GAN_MERCH_STATS_TABLE . "'") != GAN_MERCH_STATS_TABLE) {
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	$result = dbDelta($sql);
    }
  }

  static function make_views() {
    global $wpdb;
    $sql = "CREATE VIEW ".GAN_AD_STATS_TABLE_VIEW.
			" (id, adid, LastRunDate, Impressions, ".
			"ImageHeight, ImageWidth, MerchantID, enabled, ".
			"StartDate, EndDate) AS SELECT ";
    $sql .= GAN_AD_STATS_TABLE . '.id, ';
    $sql .= GAN_AD_STATS_TABLE . '.adid, ';
    $sql .= GAN_AD_STATS_TABLE . '.LastRunDate, ';
    $sql .= GAN_AD_STATS_TABLE . '.Impressions, ';
    $sql .= GAN_AD_TABLE . '.ImageHeight, ';
    $sql .= GAN_AD_TABLE . '.ImageWidth, ';
    $sql .= GAN_AD_TABLE . '.MerchantID, ';
    $sql .= GAN_AD_TABLE . '.enabled, ';
    $sql .= GAN_AD_TABLE . '.StartDate, ';
    $sql .= GAN_AD_TABLE . '.EndDate ';
    $sql .= 'FROM '.GAN_AD_STATS_TABLE.', '.GAN_AD_TABLE.' WHERE ';
    $sql .= GAN_AD_TABLE.'.id = '.GAN_AD_STATS_TABLE.'.adid';

    if ($wpdb->get_var("SHOW TABLES LIKE '" . GAN_AD_STATS_TABLE_VIEW . "'") != GAN_AD_STATS_TABLE_VIEW) {
	$result = $wpdb->query($sql);
    }
    $sql = "CREATE VIEW ".GAN_MERCH_STATS_TABLE_VIEW.
		        " (id, MerchantID, LastRunDate, Impressions, ".
			"ImageHeight, ImageWidth, enabled, StartDate, ".
			"EndDate) AS SELECT ";
    $sql .= GAN_MERCH_STATS_TABLE . '.id, ';
    $sql .= GAN_MERCH_STATS_TABLE . '.MerchantID, ';
    $sql .= GAN_MERCH_STATS_TABLE . '.LastRunDate, ';
    $sql .= GAN_MERCH_STATS_TABLE . '.Impressions, ';
    $sql .= GAN_AD_TABLE . '.ImageHeight, ';
    $sql .= GAN_AD_TABLE . '.ImageWidth, ';
    $sql .= GAN_AD_TABLE . '.enabled, ';
    $sql .= GAN_AD_TABLE . '.StartDate, ';
    $sql .= GAN_AD_TABLE . '.EndDate ';
    $sql .= 'FROM '.GAN_MERCH_STATS_TABLE.', '.GAN_AD_TABLE.' WHERE ';
    $sql .= GAN_AD_TABLE.'.MerchantID = '.GAN_MERCH_STATS_TABLE.'.MerchantID';
    if ($wpdb->get_var("SHOW TABLES LIKE '" . GAN_MERCH_STATS_TABLE_VIEW . "'") != GAN_MERCH_STATS_TABLE_VIEW) {
	$result = $wpdb->query($sql);
    }
  }	  

  /*
   * Return an array of ordered merchants (advertisers).  Returns only
   * merchants with enabled ads, with valid dates, with ads of the
   * specified size ( 0,0 == text ads).  The results are ordered by
   * merchant Impressions -- the least viewed merchant (advertiser) is first.
   */
  static function ordered_merchants($height,$width) {
    global $wpdb;
    $sql = $wpdb->prepare(
		   "SELECT DISTINCT MerchantID FROM ".GAN_MERCH_STATS_TABLE_VIEW.
		   " WHERE ImageHeight = %d && ImageWidth = %d &&".
		   " enabled = 1 &&".
		   " CURDATE() >= StartDate && CURDATE() < EndDate".
		   " ORDER BY Impressions",$height,$width);
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
    $sql = $wpdb->prepare(
		"SELECT DISTINCT adid FROM ".GAN_AD_STATS_TABLE_VIEW.
		" WHERE ImageHeight = %d && ImageWidth = %d &&".
		" enabled = 1 && MerchantID = %s &&".
		" CURDATE() >= StartDate && CURDATE() < EndDate".
		" ORDER BY Impressions",$height,$width,$merch);
    
    return $wpdb->get_col($sql);
  }
  /* Get a specified ad by its id. The complete ad's row is returned as an
   * associative array.
   */
  static function get_ad($id) {
    global $wpdb;
    $sql = $wpdb->prepare("SELECT * FROM ".GAN_AD_TABLE.
			  " WHERE ID = %d",$id);
    return $wpdb->get_row($sql, 'ARRAY_A' );
  }
  /* Update an ad's impression counts (merchant and the ad itself). If the
   * ad or merchant does not already have a impression count row, it is
   * created.
   */
  static function bump_counts($id) {
    //file_put_contents("php://stderr","*** GAN_Database::bump_counts(".$id.")\n");
    global $wpdb;
    $merchid = $wpdb->get_var($wpdb->prepare("SELECT MerchantID FROM ".
					     GAN_AD_TABLE.
					     " WHERE ID = %d",$id));
    //file_put_contents("php://stderr","*** -: merchid = ".$merchid."\n");
    $merchseen = $wpdb->get_var($wpdb->prepare("SELECT Impressions FROM ".
						GAN_MERCH_STATS_TABLE.
						" WHERE MerchantID = %s",
						$merchid));
    //file_put_contents("php://stderr","*** -: merchseen (1) = ".$merchseen."\n");
    if ($merchseen == NULL || $merchseen == 0) {
      $wpdb->insert(GAN_MERCH_STATS_TABLE,array("MerchantID" => $merchid),"%s");
      $merchseen = 0;
    }
    //file_put_contents("php://stderr","*** -: merchseen (2) = ".$merchseen."\n");
    $wpdb->update( GAN_MERCH_STATS_TABLE,
		   array("Impressions" => $merchseen + 1,
			 "LastRunDate" => date('Y-m-d')),
		   array("MerchantID" => $merchid),
		   array("%d","%s"), "%s");
    $adseen = $wpdb->get_var($wpdb->prepare("SELECT Impressions FROM ".
					    GAN_AD_STATS_TABLE.
					    " WHERE adid = %d",$id));
    //file_put_contents("php://stderr","*** -: adseen (1) = ".$adseen."\n");
    if ($adseen == NULL || $adseen == 0) {
      $wpdb->insert(GAN_AD_STATS_TABLE,array("adid" => $id),"%d");
      $adseen = 0;
    }
    //file_put_contents("php://stderr","*** -: adseen (2) = ".$adseen."\n");
    $wpdb->update( GAN_AD_STATS_TABLE,
		   array("Impressions" => $adseen + 1,
		         "LastRunDate" => date('Y-m-d')),
		   array("adid" => $id),
		   array("%d","%s"), "%d");
  }
  static function delete_ad_by_id($id) {
    global $wpdb;
    $sql = $wpdb->prepare("select MerchantID " . GAN_AD_TABLE . ' where id = %d',$id);
    $MerchantID = $wpdb->get_var($sql);
    $sql = $wpdb->prepare("delete from " . GAN_AD_TABLE . ' where id = %d',$id);
    $wpdb->query($sql);
    $sql = $wpdb->prepare("delete from " . GAN_AD_STATS_TABLE . ' where adid = %d',$id);
    $wpdb->query($sql);
    $sql = $wpdb->prepare("select count(*) from ".GAN_AD_TABLE. ' where MerchantID = %s',$MerchantID);
    if ($wpdb->get_var($sql) == 0) {
      $sql = $wpdb->prepare("delete from " . GAN_MERCH_STATS_TABLE . " where MerchantID = %s",$MerchantID);
      $wpdb->query($sql);
    }
  }
  static function toggle_side($id) {
    global $wpdb;
    $sql = $wpdb->prepare("SELECT Side FROM " . GAN_AD_TABLE . ' where id = %d',$id);
    $curside = $wpdb->get_var($sql);
    if ($curside == 0) {$newside = 1;}
    else {$newside = 0;}
    $sql = $wpdb->prepare("update " . GAN_AD_TABLE . " set Side=%d where id = %d",$newside,$id);
    $wpdb->query($sql);
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
    $wpdb->query("delete from " . GAN_AD_TABLE . ' where EndDate<CURDATE() ' . $wand);
  }
  static function clean_stats_tables() {
    global $wpdb;
    $adids = $wpdb->get_col("select adid " . GAN_AD_STATS_TABLE . ' order by Impressions');
    foreach ($adids as $adid) {
      if ($wpdb->get_var("select count(*) from " . GAN_AD_TABLE . ' where id = '.$adid) == 0) {
	$wpdb->query("delete from " . GAN_AD_STATS_TABLE . ' where adid = '.$adid);
      }
    }
    $merchids = $wpdb->get_col("select MerchantID from " . GAN_MERCH_STATS_TABLE . ' order by Impressions');
    foreach ($merchids as $MerchantID) {
      if ($wpdb->get_var("select count(*) from " . GAN_AD_TABLE . " where MerchantID = '".$MerchantID."'") == 0) {
	$wpdb->query("delete from " . GAN_MERCH_STATS_TABLE . " where MerchantID = '".$MerchantID."'");
      }
    }
  }
  static function get_GAN_data($where) {
    global $wpdb;
    return $wpdb->get_results("SELECT id,Advertiser,LinkID,ImageWidth,LinkName,StartDate,EndDate,Side,Enabled FROM " . GAN_AD_TABLE . $where . ' Order by EndDate', 'ARRAY_A');
  }
  static function get_GAN_row_count($where) {
    global $wpdb;
    return $wpdb->get_var("SELECT count(*) FROM " . GAN_AD_TABLE . $where);
  }
  static function insert_GAN($Advertiser,$LinkID,$LinkName,$MerchandisingText,
			     $AltText,$StartDate,$EndDate,$ClickserverLink,
			     $ImageURL,$ImageHeight,$ImageWidth,$LinkURL,
			     $PromoType,$MerchantID,$side,$enabled) {
    global $wpdb;
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
				     'side' => $side,
				     'enabled' => $enabled),
				array("%s","%s","%s","%s","%s","%s","%s","%s",
				      "%s","%s","%s","%s","%s","%s","%d","%d"));
  }
  static function update_GAN($id,$Advertiser,$LinkID,$LinkName,$MerchandisingText,
			     $AltText,$StartDate,$EndDate,$ClickserverLink,
			     $ImageURL,$ImageHeight,$ImageWidth,$LinkURL,
			     $PromoType,$MerchantID,$side,$enabled) {
    global $wpdb;
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
				     'side' => $side,
				     'enabled' => $enabled),
				array('id' => $id),
				array("%s","%s","%s","%s","%s","%s","%s","%s",
				      "%s","%s","%s","%s","%s","%s","%d","%d"),
				array("%d"));
  }
  static function get_merchants() {
    global $wpdb;
    return $wpdb->get_results("SELECT distinct Advertiser,MerchantID from  " . GAN_AD_TABLE . " order by Advertiser", 'ARRAY_A');
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
    return $wpdb->get_var("SELECT count(distinct MerchantID) FROM " . GAN_AD_TABLE);
  }
  static function width_count() {
    global $wpdb;
    return $wpdb->get_var("SELECT count(distinct ImageWidth) FROM " . GAN_AD_TABLE);
  }
  static function top_merch() {
    global $wpdb;
    return $wpdb->get_results("SELECT Impressions, MerchantID from ".
				      GAN_MERCH_STATS_TABLE.
				    " order by Impressions DESC LIMIT 5", 'ARRAY_A');
  }
  static function max_merch_impressions() {
    global $wpdb;
    return $wpdb->get_var("SELECT MAX(Impressions) from ".
				GAN_MERCH_STATS_TABLE);
  }
  static function merch_statistics() {
    global $wpdb;
    return $wpdb->get_row("SELECT MAX(Impressions) maximum, ".
	  			 "MIN(Impressions) minimum, ".
				 "AVG(Impressions) average, ".
				 "STDDEV(Impressions) std_deviation, ".
				 "VARIANCE(Impressions) variance FROM ".
				GAN_MERCH_STATS_TABLE, 'ARRAY_A');
  }
  static function top_ads() {
    global $wpdb;
    return $wpdb->get_results("SELECT Impressions, adid from ".
				    GAN_AD_STATS_TABLE.
				    " order by Impressions DESC LIMIT 5", 'ARRAY_A');
  }
  static function ad_statistics() {
    global $wpdb;
    return $wpdb->get_row("SELECT MAX(Impressions) maximum, ".
	  				            "MIN(Impressions) minimum, ".
						    "AVG(Impressions) average, ".
						    "STDDEV(Impressions) std_deviation, ".
						    "VARIANCE(Impressions) variance FROM ".
						GAN_AD_STATS_TABLE, 'ARRAY_A');
  }
  static function max_ad_impressions() {
    global $wpdb;
    return $wpdb->get_var("SELECT MAX(Impressions) from ".
					 	GAN_AD_STATS_TABLE);
  }
  static function get_merch_name($merchid) {
    global $wpdb;
    $sql = $wpdb->prepare("SELECT DISTINCT Advertiser FROM ".GAN_AD_TABLE.
			  " WHERE MerchantID = %s",$merchid);
    return $wpdb->get_var($sql);
  }
  static function get_link_name($id) {
    global $wpdb;
    $sql = $wpdb->prepare("SELECT DISTINCT LinkName FROM ".GAN_AD_TABLE.
				" WHERE ID = %s",$id);
    return $wpdb->get_var($sql);
  }
}

?>
