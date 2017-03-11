<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
require 'config.php';
$parser = new PEAR2\Console\CommandLine(array(
    'description' => 'Add LetsEncrypt SSL to domain',
    'version' => '0.0.1', // the version of your program
        ));
$parser->addOption(
        'interactive', array(
    'short_name' => '-i',
    'long_name' => '--interactive',
    'description' => 'Run interactively',
    'action' => 'StoreTrue'
        )
);
$parser->addOption(
        'name', array(
    'short_name' => '-n',
    'long_name' => '--domain_name',
    'description' => 'domain name',
    'action' => 'StoreString',
    'help_name' => 'domain_name'
        )
);

function create_test_configs($filename, $domain_name) {
    try {
        if (!@copy('templates/nginx.template', $filename)) {
            $mkdirErrorArray = error_get_last();
            throw new Exception("Can't create nginx config file " . $mkdirErrorArray['message'], 3);
        }
    } catch (Exception $ex) {
        echo 'Error: ', $ex->getMessage(), "\n";
        exit(3);
    }
    try {
        if (($domain_tpl = file_get_contents($filename)) === FALSE) {
            throw new \Exception("Can't open template file", 4);
        }
    } catch (Exception $ex) {
        echo 'Error: ', $ex->getMessage(), "\n";
        exit(4);
    }
    $fixed_domain_tpl = str_replace("%%domain%%", $domain_name, $domain_tpl);
    try {
        if (($domain_tpl = file_put_contents($filename, $fixed_domain_tpl)) === FALSE) {
            throw new \Exception("Can't write to template file", 5);
        }
    } catch (Exception $ex) {
        echo 'Error: ', $ex->getMessage(), "\n";
        exit(5);
    }
    try {
        $cmd = escapeshellcmd('nginx -t');
        exec($cmd . " 2>&1", $aResult, $return_val);
        if ($return_val <> 0)
            throw new \Exception("Nginx configuration error", 6);
    } catch (Exception $ex) {
        echo 'Error: ', $ex->getMessage(), "\n";
        exit(6);
    }
}

try {
    $result = $parser->parse();
    if ($result->options["interactive"]) {
        //TODO Запуск в интерактивном режиме
    }
    $cmdl = array_filter($result->options);
    if (empty($cmdl)) {
        $parser->displayUsage();
    }
    $fname = array(
        '/usr/share/ssl',
        '/usr/share/ssl/certs',
        '/usr/share/ssl/private'
    );
    foreach ($fname as $filename) {
        if (!file_exists($filename)) {
            try {
                if (!@mkdir($filename, 0755)) {
                    $mkdirErrorArray = error_get_last();
                    throw new Exception("Can't create directory " . $mkdirErrorArray['message'], 1);
                }
            } catch (Exception $ex) {
                echo 'Error: ', $ex->getMessage(), "\n";
                exit(1);
            }
        }
    }
    unset($fname);
    $filename = '/etc/nginx/conf.d/ssl/' . $result->options["name"] . '.conf';
    if (strpos($result->options["name"], '.vps-private.net') === false) {
        try {
            $cmd = escapeshellcmd('/root/.acme.sh/acme.sh --issue -d ' . $result->options["name"] . ' -d www.' . $result->options["name"] . ' -w /var/www/letsencrypt/ --fullchainpath /usr/share/ssl/certs/' . $result->options["name"] . '.crt --keypath /usr/share/ssl/private/' . $result->options["name"] . '.key');
            exec($cmd . " 2>&1", $aResult, $return_val);
            if ($return_val <> 0)
                throw new Exception("Can't run " . $cmd, 2);
        } catch (Exception $ex) {
            echo 'Error: ', $ex->getMessage(), "\n";
            exit(2);
        }
        create_test_configs($filename, $result->options["name"]);
    } else {
        // сделать симлинк на наши серты, добавить конф
        $filename = '/etc/nginx/conf.d/ssl/' . $result->options["name"] . '.conf';
        if (!symlink(wild_cert, '/usr/share/ssl/certs/' . $result->options["name"] . '.crt'))
            throw new \Exception("Can't create symlink for " . wild_cert . " to " . $result->options["name"] . ".crt", 7);
        if (!symlink(wild_key, '/usr/share/ssl/private/' . $result->options["name"] . '.key'))
            throw new \Exception("Can't create symlink for " . wild_key . " to " . $result->options["name"] . ".key", 8);
        create_test_configs($filename, $result->options["name"]);
    };
} catch (Exception $exc) {
    $parser->displayError($exc->getMessage());
}
  