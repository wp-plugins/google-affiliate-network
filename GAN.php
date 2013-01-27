<?php
/**
 * Plugin Name: Google Affiliate Network widget
 * Plugin URI: http://http://www.deepsoft.com/GAN
 * Description: A Widget to display Google Affiliate Network ads
 * Version: 6.0
 * Author: Robert Heller
 * Author URI: http://www.deepsoft.com/
 * License: GPL2
 * 
 * ------------------------------------------------------------------
 * GAN.php - Google Affiliate Network plugin / widgets
 * Created by Robert Heller on Sun Jan 27 13:46:15 2013
 * ------------------------------------------------------------------
 * Modification History: $Log: headerfile.text,v $
 * Modification History: Revision 1.1  2002/07/28 14:03:50  heller
 * Modification History: Add it copyright notice headers
 * Modification History:
 * ------------------------------------------------------------------
 * Contents:
 * ------------------------------------------------------------------
 *  
 *     Generic Project
 *     Copyright (C) 2010-2013  Robert Heller D/B/A Deepwoods Software
 * 			51 Locke Hill Road
 * 			Wendell, MA 01379-9728
 * 
 *     This program is free software; you can redistribute it and/or modify
 *     it under the terms of the GNU General Public License as published by
 *     the Free Software Foundation; either version 2 of the License, or
 *     (at your option) any later version.
 * 
 *     This program is distributed in the hope that it will be useful,
 *     but WITHOUT ANY WARRANTY; without even the implied warranty of
 *     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *     GNU General Public License for more details.
 * 
 *     You should have received a copy of the GNU General Public License
 *     along with this program; if not, write to the Free Software
 *     Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 * 
 *  
 */ 

/* Load constants */
require_once(dirname(__FILE__) . "/includes/GAN_Constants.php");

/* Additional file-specific constants */
define('GAN_FILE', basename(__FILE__));
define('GAN_PATH', GAN_DIR . '/' . GAN_FILE);

/* Load Database code */
require_once(GAN_INCLUDES_DIR . "/GAN_Database.php");

