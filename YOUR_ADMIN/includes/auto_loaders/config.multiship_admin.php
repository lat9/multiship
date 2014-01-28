<?php
// ---------------------------------------------------------------------------
// Part of the Multiple Shipping Addresses plugin for Zen Cart v1.5.1 and later
//
// Copyright (C) 2014, Vinos de Frutas Tropicales (lat9)
//
// @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
// ---------------------------------------------------------------------------

if (!defined('IS_ADMIN_FLAG')) {
  die('Illegal Access');
} 
$autoLoadConfig[200][] = array(
  'autoType'  => 'init_script',
  'loadFile'  => 'init_multiship.php');
$autoLoadConfig[200][] = array(
  'autoType'  => 'class',
  'loadFile'  => 'observers/class.multiship_admin_observer.php',
  'classPath' => DIR_WS_CLASSES);
$autoLoadConfig[200][] = array(
  'autoType'   => 'classInstantiate',
  'className'  => 'multiship_observer',
  'objectName' => 'multiship_observer');