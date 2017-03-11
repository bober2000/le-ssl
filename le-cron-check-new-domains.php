<?php

require 'config.php';

class le_cron_Exception extends Exception {
    
}

try {
    if (!file_exists(domain_list)) {
        throw new le_cron_Exception("File " . domain_list . " does not exist\n", 1);
    }
    if (!file_exists(custom_ssl_domain_list)) {
        throw new le_cron_Exception("File " . custom_ssl_domain_list . " does not exist\n", 2);
    }
    try {
        if (!$dlist = @file(domain_list, FILE_IGNORE_NEW_LINES)) {
            throw new le_cron_Exception("Can't open " . domain_list . " file \n", 3);
        }
    } catch (Exception $ex) {
        echo 'Error: ', $ex->getMessage(), "\n";
        exit(3);
    }
    try {
        if (!$dcustomlist = @file(custom_ssl_domain_list, FILE_IGNORE_NEW_LINES)) {
            throw new le_cron_Exception("Can't open " . domain_list . " file \n", 4);
        }
    } catch (Exception $ex) {
        echo 'Error: ', $ex->getMessage(), "\n";
        exit(4);
    }
    try {
        if (!$currentssllist = @scandir(current_ssl_domain_list)) {
            throw new le_cron_Exception("Can't open " . current_ssl_domain_list . " directory \n", 5);
        }
    } catch (Exception $ex) {
        echo 'Error: ', $ex->getMessage(), "\n";
        exit(5);
    }
    $currentssllist = array_diff($currentssllist, array('..', '.'));
    if (!empty($currentssllist)) {

        foreach ($currentssllist as $domain) {
            $dssllist[] = explode(".conf", $domain, -1);
        }
        $dssllist = array_reduce($dssllist, 'array_merge', array());
        $result = array_diff($dlist, $dcustomlist, $dssllist);
        foreach ($result as $domain) {
            try {
                $cmd = escapeshellcmd('/usr/bin/php le-ssl-add.php -n ' . $domain);
                exec($cmd . " 2>&1", $aResult, $return_val);
                if ($return_val <> 0)
                    throw new Exception("Can't run" . $cmd, 6);
            } catch (Exception $ex) {
                echo 'Error: ', $ex->getMessage(), "\n";
                exit(6);
            }
        }
    } else {
        $result = array_diff($dlist, $dcustomlist);
        var_dump($result);
        foreach ($result as $domain) {
            try {
                $cmd = escapeshellcmd('/usr/bin/php le-ssl-add.php -n ' . $domain);
                exec($cmd . " 2>&1", $aResult, $return_val);
                if ($return_val <> 0)
                    throw new Exception("Can't run " . $cmd, 6);
            } catch (Exception $ex) {
                echo 'Error: ', $ex->getMessage(), "\n";
                exit(6);
            }
        }
    }
    // Check if domain deleted but config for nginx exists
    $result = array_diff($dssllist, $dlist);
    foreach ($result as $domain) {
        try {
            $file = current_ssl_domain_list . $domain . '.conf';
            if (!(is_file($file) && @unlink($file))) {
                throw new Exception("Can't delete" . $file, 7);
            }
        } catch (Exception $ex) {
            echo 'Error: ', $ex->getMessage(), "\n";
            exit(7);
        }
        // Delete Certificate from acme.sh too
        try {
            $cmd = escapeshellcmd("rm -rf " . acmesh_home . $domain);
            $gid = exec($cmd . " 2>&1", $aResult, $return_val);
            if ($return_val <> 0)
                throw new \Exception("Can't remove domain" . $domain . " dir in acme.sh home", 8);
        } catch (Exception $ex) {
            echo 'Error: ', $ex->getMessage(), "\n";
            exit(8);
        }
    }
    try {
        $cmd = escapeshellcmd('service nginx reload');
        $gid = exec($cmd . " 2>&1", $aResult, $return_val);
        if ($return_val <> 0)
            throw new \Exception("Can't reload nginx", 9);
    } catch (Exception $ex) {
        echo 'Error: ', $ex->getMessage(), "\n";
        exit(9);
    }
    try {
        $cmd = escapeshellcmd("rm -rf /usr/share/ssl/certs/" . $domain . ".crt");
        $gid = exec($cmd . " 2>&1", $aResult, $return_val);
        if ($return_val <> 0)
            throw new \Exception("Can't remove domain" . $domain . " certificate /usr/share/ssl/certs/" . $domain . ".crt", 10);
    } catch (Exception $ex) {
        echo 'Error: ', $ex->getMessage(), "\n";
        exit(10);
    }
    try {
        $cmd = escapeshellcmd("rm -rf /usr/share/ssl/private/" . $domain . ".key");
        $gid = exec($cmd . " 2>&1", $aResult, $return_val);
        if ($return_val <> 0)
            throw new \Exception("Can't remove domain" . $domain . " key /usr/share/ssl/private/" . $domain . ".crt", 10);
    } catch (Exception $ex) {
        echo 'Error: ', $ex->getMessage(), "\n";
        exit(10);
    }
} catch (Exception $ex) {
    echo 'Error: ', $ex->getMessage(), "\n";
}
