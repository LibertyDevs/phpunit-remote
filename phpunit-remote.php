<?php

//// user configuration //////////////////////////////////
/**
 * Procject configuration
 */
$l_project_root = "/Users/liberty/works/virtual_machines/vm1/crawler";
$l_test_root = $l_project_root . "/app/tests";
$r_project_root = "/vagrant_data/crawler";
$r_test_root = $r_project_root . "/app/tests";
$r_phpunit_path = $r_project_root . "/vendor/bin/phpunit";
$r_phpunit_configuration_path = "{$r_project_root}/phpunit.xml";
$r_phpunit_bootstrap_path = "{$r_project_root}/bootstrap/autoload.php";

/**
 * ssh2 configuration
 */
$r_host = 'vm1';
$r_port = '2222';
$r_user = 'vagrant';
$pubkey = '/Users/liberty/.vagrant.d/insecure_public_key';
$privkey = '/Users/liberty/.vagrant.d/insecure_private_key';

/**
 * remote nb path configuration
 */
$r_nb_suite_path = "/tmp/NetBeansSuite.php";
//////////////////////////////////////////////////////

/**
 * pre execute settings
 */
// Set Arguments
$l_phpunit_xml_log_path = null;
$r_phpunit_xml_log_path = null;
$l_phpunit_coverage_path = null;
$r_phpunit_coverage_path = null;
$l_nb_suite_path = null; // local netbeans suite path
$r_phpunit_args = null; // set arguments for remote host
$is_enable_coverage = false; // --coverage-clover: specified:true
for ($i = 1; $i < count($argv); $i++) {

    // error: --bootstrap not supported
    if (preg_match("/^--bootstrap/", $argv[$i]) === 1) {
        die("Error: It does not support the specification of the bootstrap.\nPlease specify in this script.");
    }

    // error: --configuration not supported
    if (preg_match("/^--configuration/", $argv[$i]) === 1) {
        die("Error: It does not support the specification of the configuration.\nPlease specify in this script.");
    }

    // replace: run path
    if (preg_match("/^--run\=/", $argv[$i]) === 1) {
        $regex = preg_quote($l_test_root, "/");
        $r_phpunit_args[] = preg_replace("/{$regex}/", $r_test_root, $argv[$i]);
        continue;
    }

    // replcae: --log-junit file_path
    if (preg_match("/^--log-junit/", $argv[$i]) === 1) {
        $r_phpunit_args[] = '--log-junit';
        $i++;
        $l_phpunit_xml_log_path = $argv[$i];
        $r_phpunit_xml_log_path = preg_replace("/^\/var/", "/tmp", $argv[$i]);
        $r_phpunit_args[] = $r_phpunit_xml_log_path;
        continue;
    }

    // replcae: --coverage-clover file_path
    if (preg_match("/^--coverage-clover/", $argv[$i]) === 1) {
        $r_phpunit_args[] = '--coverage-clover';
        $i++;
        $l_phpunit_coverage_path = $argv[$i];
        $r_phpunit_coverage_path = preg_replace("/^\/var/", "/tmp", $argv[$i]);
        $r_phpunit_args[] = $r_phpunit_coverage_path;
        $is_enable_coverage = true;
        continue;
    }

    // replcae: --filter arg string
    if (preg_match("/^--filter/", $argv[$i]) === 1) {
        $r_phpunit_args[] = '--filter';
        $i++;
        $r_phpunit_args[] = $argv[$i];
        continue;
    }

    // set: Set NetBeansSuite path
    if (preg_match("/NetBeansSuite\.php$/", $argv[$i]) === 1) {
        $l_nb_suite_path = $argv[$i];
        $r_phpunit_args[] = $r_nb_suite_path;
        continue;
    }

    // others
    $r_phpunit_args[] = $argv[$i];
}
// add bootstrap and phpunit_xml path
$r_phpunit_args[] = '--bootstrap';
$r_phpunit_args[] = $r_phpunit_bootstrap_path;
$r_phpunit_args[] = '--configuration';
$r_phpunit_args[] = $r_phpunit_configuration_path;

/**
 * Connection to host
 */
$conn = ssh2_connect($r_host, $r_port, array('hostkey' => 'ssh-rsa'));
if (!ssh2_connect($r_host, $r_port, array('hostkey' => 'ssh-rsa'))) {
    die("Connection failure to host: server_name:{$r_host} port:{$r_port} user: {$_user} publickey_path: {$pubkey} privatekey_path: {$privkey}");
}
if (!ssh2_auth_pubkey_file($conn, $r_user, $pubkey, $privkey)) {
    die("Authentication failure to host: server_name:{$r_host} port:{$r_port} user: {$_user} publickey_path: {$pubkey} privatekey_path: {$privkey}");
}

/**
 * Transfer NetBeansSuite to host
 */
if (!ssh2_scp_send($conn, $l_nb_suite_path, $r_nb_suite_path)) {
    die("Trancefer failure to host: local_path:{$l_nb_suite_path} remote_path:{$r_nb_suite_path}");
}

/**
 * Execute phpunit on remote server
 */
$_quoted_args = '"' . implode("\" \"", $r_phpunit_args) . '"';
$phpunit_command = $r_phpunit_path . " " . $_quoted_args;

$stream = ssh2_exec($conn, $phpunit_command);
$error_stream = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);

// Enable blocking for both streams
stream_set_blocking($stream, true);
stream_set_blocking($error_stream, true);

// Whichever of the two below commands is listed first will receive its appropriate output.  The second command receives nothing
$phpunit_result_log = preg_replace("/" . preg_quote($r_test_root, "/") . "/", $l_test_root, stream_get_contents($stream));
$phpunit_result_log_error = preg_replace("/" . preg_quote($r_project_root, "/") . "/", $l_project_root, stream_get_contents($error_stream));

// Close the streams
fclose($error_stream);
fclose($stream);

// Output error
if ($phpunit_result_log_error) {
    print $phpunit_result_log_error;
    return 1;
}

/**
 * Get phpunit_log and coverage_log
 */
if (!ssh2_scp_recv($conn, $r_phpunit_xml_log_path, $l_phpunit_xml_log_path)) {
    die("Receive failure to host: local_path:{$l_phpunit_xml_log_path} remote_path:{$r_phpunit_xml_log_path}");
} else {
    // Replace test root path
    $_phpunit_xml_log_str = file_get_contents($l_phpunit_xml_log_path);
    $_replaced_phpunit_xml_log_str = preg_replace("/" . preg_quote($r_test_root, "/") . "/", $l_test_root, $_phpunit_xml_log_str);
    file_put_contents($l_phpunit_xml_log_path, $_replaced_phpunit_xml_log_str);
}

if ($is_enable_coverage) {
    if (!ssh2_scp_recv($conn, $r_phpunit_coverage_path, $l_phpunit_coverage_path)) {
        die("Receive failure to host: local_path:{$l_phpunit_coverage_path} remote_path:{$r_phpunit_coverage_path}");
    } else {
        // Replace project root path
        $_phpunit_coverage_str = file_get_contents($l_phpunit_coverage_path);
        $_replaced_hpunit_coverage_str = preg_replace("/" . preg_quote($r_project_root, "/") . "/", $l_project_root, $_phpunit_coverage_str);
        file_put_contents($l_phpunit_coverage_path, $_replaced_hpunit_coverage_str);
    }
}

print $phpunit_result_log;
return 0;
