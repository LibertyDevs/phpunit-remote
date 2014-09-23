<?php

require_once __DIR__ . '/Config.php';

//// get user configuration //////////////////////////////////
$config = Config::getInstance();
/**
 * Procject configuration
 */
$projectRootRemote = $config->getProjectRootLocal();
$projectRootLocal = $config->getProjectRootRemote();
$testRootLocal = $config->getTestRootLocal();
$testRootRemote = $config->getTestRootRemote();
$phpunitPathRemote = $config->getPhpunitPathRemote();
$phpunitConfigurationPathRemote = $config->getPhpunitConfigurationPathRemote();
$phpunitBootstrapPathRemote = $config->getPhpunitBootstrapPathRemote();

/**
 * ssh2 configuration
 */
$hostRemote = $config->getHostRemote();
$portRemote = $config->getPortRemote();
$userRemote = $config->getUserRemote();
$pubkey = $config->getPubkey();
$privkey = $config->getPrivkey();

/**
 * remote nb path configuration
 */
$nbSuitePathRemote = $config->getNbSuitePathRemote();
//////////////////////////////////////////////////////

/**
 * pre execute settings
 */
// Set Arguments
$phpunitXmlLogPathLocal = null;
$phpunitXmlLogPathRemote = null;
$phpunitCoveragePathLocal = null;
$phpunitCoveragePathRemote = null;
$nbSuitePathLocal = null; // local netbeans suite path
$phpunitArgsRemote = null; // set arguments for remote host
$isEnableCoverage = false; // --coverage-clover: specified:true
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
        $regex = preg_quote($testRootLocal, "/");
        $phpunitArgsRemote[] = preg_replace("/{$regex}/", $testRootRemote, $argv[$i]);
        continue;
    }

    // replcae: --log-junit file_path
    if (preg_match("/^--log-junit/", $argv[$i]) === 1) {
        $phpunitArgsRemote[] = '--log-junit';
        $i++;
        $phpunitXmlLogPathLocal = $argv[$i];
        $phpunitXmlLogPathRemote = preg_replace("/^\/var/", "/tmp", $argv[$i]);
        $phpunitArgsRemote[] = $phpunitXmlLogPathRemote;
        continue;
    }

    // replcae: --coverage-clover file_path
    if (preg_match("/^--coverage-clover/", $argv[$i]) === 1) {
        $phpunitArgsRemote[] = '--coverage-clover';
        $i++;
        $phpunitCoveragePathLocal = $argv[$i];
        $phpunitCoveragePathRemote = preg_replace("/^\/var/", "/tmp", $argv[$i]);
        $phpunitArgsRemote[] = $phpunitCoveragePathRemote;
        $isEnableCoverage = true;
        continue;
    }

    // replcae: --filter arg string
    if (preg_match("/^--filter/", $argv[$i]) === 1) {
        $phpunitArgsRemote[] = '--filter';
        $i++;
        $phpunitArgsRemote[] = $argv[$i];
        continue;
    }

    // set: Set NetBeansSuite path
    if (preg_match("/NetBeansSuite\.php$/", $argv[$i]) === 1) {
        $nbSuitePathLocal = $argv[$i];
        $phpunitArgsRemote[] = $nbSuitePathRemote;
        continue;
    }

    // others
    $phpunitArgsRemote[] = $argv[$i];
}
// add bootstrap and phpunit_xml path
$phpunitArgsRemote[] = '--bootstrap';
$phpunitArgsRemote[] = $phpunitBootstrapPathRemote;
$phpunitArgsRemote[] = '--configuration';
$phpunitArgsRemote[] = $phpunitConfigurationPathRemote;

/**
 * Connection to host
 */
$conn = ssh2_connect($hostRemote, $portRemote, array('hostkey' => 'ssh-rsa'));
if (!ssh2_connect($hostRemote, $portRemote, array('hostkey' => 'ssh-rsa'))) {
    die("Connection failure to host: server_name:{$hostRemote} port:{$portRemote} user: {$userRemote} publickey_path: {$pubkey} privatekey_path: {$privkey}");
}
if (!ssh2_auth_pubkey_file($conn, $userRemote, $pubkey, $privkey)) {
    die("Authentication failure to host: server_name:{$hostRemote} port:{$portRemote} user: {$userRemote} publickey_path: {$pubkey} privatekey_path: {$privkey}");
}

/**
 * Transfer NetBeansSuite to host
 */
if (!ssh2_scp_send($conn, $nbSuitePathLocal, $nbSuitePathRemote)) {
    die("Trancefer failure to host: local_path:{$nbSuitePathLocal} remote_path:{$nbSuitePathRemote}");
}

/**
 * Execute phpunit on remote server
 */
$_quoted_args = '"' . implode("\" \"", $phpunitArgsRemote) . '"';
$phpunit_command = $phpunitPathRemote . " " . $_quoted_args;

$stream = ssh2_exec($conn, $phpunit_command);
$error_stream = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);

// Enable blocking for both streams
stream_set_blocking($stream, true);
stream_set_blocking($error_stream, true);

// Whichever of the two below commands is listed first will receive its appropriate output.  The second command receives nothing
$phpunit_result_log = preg_replace("/" . preg_quote($testRootRemote, "/") . "/", $testRootLocal, stream_get_contents($stream));
$phpunit_result_log_error = preg_replace("/" . preg_quote($projectRootLocal, "/") . "/", $projectRootRemote, stream_get_contents($error_stream));

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
if (!ssh2_scp_recv($conn, $phpunitXmlLogPathRemote, $phpunitXmlLogPathLocal)) {
    die("Receive failure to host: local_path:{$phpunitXmlLogPathLocal} remote_path:{$phpunitXmlLogPathRemote}");
} else {
    // Replace test root path
    $_phpunit_xml_log_str = file_get_contents($phpunitXmlLogPathLocal);
    $_replaced_phpunit_xml_log_str = preg_replace("/" . preg_quote($testRootRemote, "/") . "/", $testRootLocal, $_phpunit_xml_log_str);
    file_put_contents($phpunitXmlLogPathLocal, $_replaced_phpunit_xml_log_str);
}

if ($isEnableCoverage) {
    if (!ssh2_scp_recv($conn, $phpunitCoveragePathRemote, $phpunitCoveragePathLocal)) {
        die("Receive failure to host: local_path:{$phpunitCoveragePathLocal} remote_path:{$phpunitCoveragePathRemote}");
    } else {
        // Replace project root path
        $_phpunit_coverage_str = file_get_contents($phpunitCoveragePathLocal);
        $_replaced_hpunit_coverage_str = preg_replace("/" . preg_quote($projectRootLocal, "/") . "/", $projectRootRemote, $_phpunit_coverage_str);
        file_put_contents($phpunitCoveragePathLocal, $_replaced_hpunit_coverage_str);
    }
}

print $phpunit_result_log;
return 0;
