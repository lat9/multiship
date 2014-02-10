<?php
// ---------------------------------------------------------------------------
// Part of the Multiple Shipping Addresses plugin for Zen Cart v1.5.1 and later
//
// Copyright (C) 2014, Vinos de Frutas Tropicales (lat9)
//
// @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
// ---------------------------------------------------------------------------

// -----
// Needs to be instantiated before the init_cart_handler (at checkpoint 140).
//
$autoLoadConfig[131][] = array ('autoType'=>'class',
                                'loadFile'=>'observers/class.multiship_observer.php');
$autoLoadConfig[131][] = array ('autoType'=>'classInstantiate',
                                'className'=>'multiship_observer',
                                'objectName'=>'multiship_observer');
// -----
// Needs to be instantiated after the messageStack but before the multiship_observer, since
// the observer calls functions in this class.
//
$autoLoadConfig[0][]   = array ('autoType' => 'class',
                                'loadFile' => 'class.multiship.php');
$autoLoadConfig[130][] = array ('autoType' => 'classInstantiate',
                                'className' => 'multiship',
                                'objectName' => 'multiship',
                                'checkInstantiated' => true,
                                'classSession' => true);                                