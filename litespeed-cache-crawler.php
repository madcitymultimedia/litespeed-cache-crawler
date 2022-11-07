<?php

/**
 * @wordpress-plugin
 * Plugin Name:       Litespeed cache crawler
 * Description:       Cron triggered website crawler to have always cache ready for the real world visitor
 * Version:           2.2.7
 * Date:              2022-09-18
 * Author:            Jaro Kurimsky <pixtweaks@protonmail.com>
 * Author URI:        https://wpspeeddoctor.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 */

defined( 'ABSPATH' ) || exit;

if ( is_admin() ) {

  require_once __DIR__. '/admin-menu.php';

  if ( is_plugin_menu_lsc() ) require_once __DIR__. '/admin.php';
}

