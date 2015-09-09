<?php

/*
 * ------------------------------------------------------------------------
 * GLPI Plugin MantisBT
 * Copyright (C) 2014 by the GLPI Plugin MantisBT Development Team.
 *
 * https://forge.indepnet.net/projects/mantis
 * ------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI Plugin MantisBT project.
 *
 * GLPI Plugin MantisBT is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * GLPI Plugin MantisBT is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI Plugin MantisBT. If not, see <http://www.gnu.org/licenses/>.
 *
 * ------------------------------------------------------------------------
 *
 * @package GLPI Plugin MantisBT
 * @author Stanislas Kita (teclib')
 * @co-author François Legastelois (teclib')
 * @co-author Le Conseil d'Etat
 * @copyright Copyright (c) 2014 GLPI Plugin MantisBT Development team
 * @license GPLv3 or (at your option) any later version
 * http://www.gnu.org/licenses/gpl.html
 * @link https://forge.indepnet.net/projects/mantis
 * @since 2014
 *
 * ------------------------------------------------------------------------
 */

/**
 * function to install the plugin
 *
 * @return boolean
 */
function plugin_mantis_install() {
   require_once ('inc/mantis.class.php');
   PluginMantisMantis::install();
   
   global $DB;
   
   // création de la table du plugin
   if (! TableExists("glpi_plugin_mantis_mantis")) {
      $query = "CREATE TABLE `glpi_plugin_mantis_mantis` (
               `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
               `items_id` int(11) NOT NULL,
               `idMantis` int(11) NOT NULL,
               `dateEscalade` date NOT NULL,
               `itemtype` varchar(255) NOT NULL,
               `user` int(11) NOT NULL)";
      $DB->query($query) or die($DB->error());
   } else {
      $mig = new Migration(200);
      $table = 'glpi_plugin_mantis_mantis';
      $mig->addField($table, 'itemType', 'string');
      $mig->executeMigration();
      
      $mig = new Migration(201);
      $table = 'glpi_plugin_mantis_mantis';
      $mig->addField($table, 'itemType', 'string');
      $mig->changeField('glpi_plugin_mantis_mantis', 'itemType', 'itemtype', 'string', array());
      $mig->changeField('glpi_plugin_mantis_mantis', 'idTicket', 'items_id', 'integer', array());
      $mig->executeMigration();
   }
   
   // création de la table du plugin
   if (! TableExists("glpi_plugin_mantis_userprefs")) {
      $query = "CREATE TABLE `glpi_plugin_mantis_userprefs` (
               `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
               `users_id` int(11) NOT NULL ,
               `followTask` int(11) NOT NULL default '0',
               `followFollow` int(11) NOT NULL default '0',
               `followAttachment` int(11) NOT NULL default '0',
               `followTitle` int(11) NOT NULL default '0',
               `followDescription` int(11) NOT NULL default '0',
               `followCategorie` int(11) NOT NULL default '0',
               `followLinkedItem` int(11) NOT NULL default '0',
               UNIQUE KEY (`users_id`))";
      $DB->query($query) or die($DB->error());
   }
   
   // Création de la table uniquement lors de la première installation
   if (! TableExists("glpi_plugin_mantis_profiles")) {
      // requete de création de la table
      $query = "CREATE TABLE `glpi_plugin_mantis_profiles` (
               `id` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_profiles (id)',
               `right` char(1) collate utf8_unicode_ci default NULL,
               PRIMARY KEY  (`id`)
             ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, $DB->error());
      
      // creation du premier accès nécessaire lors de l'installation du plugin
      include_once ("inc/profile.class.php");
      PluginMantisProfile::createAdminAccess($_SESSION['glpiactiveprofile']['id']);
   }
   
   // création de la table pour la configuration du plugin
   if (! TableExists("glpi_plugin_mantis_configs")) {
      $query = "CREATE TABLE `glpi_plugin_mantis_configs` (
                  `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
                  `host` varchar(255) NOT NULL default '',
                  `url` varchar(255) NOT NULL default '',
                  `login` varchar(255) NOT NULL default '',
                  `pwd` varchar(255) NOT NULL default '',
                  `champsUrlGlpi` varchar(100) NOT NULL default '',
                  `champsGlpi` varchar(100) NOT NULL default '',
                  `enable_assign` int(3) NOT NULL default 0,
                  `neutralize_escalation` int(3) NOT NULL default 0,
                  `status_after_escalation` int(3) NOT NULL default 0,
                  `show_option_delete` int(3) NOT NULL default 0,
                  `doc_categorie` int(3) NOT NULL default 0,
                  `itemType` varchar(255) NOT NULL,
                  `etatMantis` varchar(100) NOT NULL default '')";
      $DB->query($query) or die($DB->error());
      // insertion du occcurence dans la table (occurrence par default)
      $query = "INSERT INTO `glpi_plugin_mantis_configs`
                       (`id`, `host`, `url`, `login`, `pwd`)
                VALUES (NULL, '','','','')";
      $DB->query($query) or die("error in glpi_plugin_mantis_configs table" . $DB->error());
   } else {
      $mig = new Migration(200);
      $table = 'glpi_plugin_mantis_configs';
      $mig->addField($table, 'neutralize_escalation', 'integer', array(
            'value' => 5
      ));
      $mig->addField($table, 'status_after_escalation', 'integer');
      $mig->addField($table, 'show_option_delete', 'integer', array(
            'value' => 0
      ));
      $mig->addField($table, 'doc_categorie', 'integer', array(
            'value' => 0
      ));
      $mig->addField($table, 'itemType', 'string');
      $mig->executeMigration();
   }
   
   return true;
}

/**
 * function to uninstall the plugin
 *
 * @return boolean
 */
function plugin_mantis_uninstall() {
   require_once ('inc/mantis.class.php');
   PluginMantisMantis::uninstall();
   return true;
}

// Define Additionnal search options for types (other than the plugin ones)
function plugin_mantis_getAddSearchOptions($itemtype) {
   $sopt = array();
   if ($itemtype == 'Ticket') {
      
      $sopt['common'] = "MantisBT";
      
      $sopt[78963]['table'] = 'glpi_plugin_mantis_mantis';
      $sopt[78963]['field'] = 'idMantis';
      $sopt[78963]['searchtype'] = 'equals';
      $sopt[78963]['nosearch'] = true;
      $sopt[78963]['datatype'] = 'bool';
      $sopt[78963]['name'] = __('ticket linked to mantis', 'mantis');
      $sopt[78963]['joinparams'] = array(
            'jointype' => "itemtype_item"
      );
   } else if ($itemtype == 'Problem') {
      $sopt['common'] = "MantisBT";
      
      $sopt[78964]['table'] = 'glpi_plugin_mantis_mantis';
      $sopt[78964]['field'] = 'id';
      $sopt[78964]['searchtype'] = 'equals';
      $sopt[78964]['nosearch'] = true;
      $sopt[78964]['datatype'] = 'bool';
      $sopt[78964]['name'] = __('problem linked to mantis', 'mantis');
      $sopt[78964]['joinparams'] = array(
            'jointype' => "itemtype_item"
      );
   }
   return $sopt;
}

function plugin_mantis_giveItem($type, $ID, $data, $num) {
   $searchopt = &Search::getOptions($type);
   $table = $searchopt[$ID]["table"];
   $field = $searchopt[$ID]["field"];
   
   switch ($table . '.' . $field) {
      case "glpi_plugin_mantis_mantis.idMantis" :
         return Dropdown::getYesNo($data["ITEM_$num"]);
         break;
   }
   
   return "";
}






