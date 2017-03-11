<?php

/*
* To change this license header, choose License Headers in Project Properties.
* To change this template file, choose Tools | Templates
* and open the template in the editor.
*/
define ('domain_list','/etc/vmail/domains');
define ('domain_list','/etc/vmail/domains');
define ('custom_ssl_domain_list','/etc/vmail/domains-custom-ssl');
define ('current_ssl_domain_list','/etc/nginx/conf.d/ssl/');
define ('acmesh_home','/root/.acme.sh/');
define ('wild_cert','/etc/pki/tls/certs/vps-private.net.combined.crt');
define ('wild_key','/etc/pki/tls/private/vps-private.net.key');

require 'vendor/autoload.php';

/**
* Return the user's home directory.
*/

function drush_server_home() {
  // Cannot use $_SERVER superglobal since that's empty during UnitUnishTestCase
  // getenv('HOME') isn't set on Windows and generates a Notice.
  $home = getenv('HOME');
  if (!empty($home)) {
    // home should never end with a trailing slash.
    $home = rtrim($home, '/');
  }
  elseif (!empty($_SERVER['HOMEDRIVE']) && !empty($_SERVER['HOMEPATH'])) {
    // home on windows
    $home = $_SERVER['HOMEDRIVE'] . $_SERVER['HOMEPATH'];
    // If HOMEPATH is a root directory the path can end with a slash. Make sure
    // that doesn't happen.
    $home = rtrim($home, '\\/');
  }
  return empty($home) ? NULL : $home;
}
