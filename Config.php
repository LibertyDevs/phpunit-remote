<?php

class Config
{

    //// user configuration //////////////////////////////////
    /**
     * Procject configuration
     */
    private $projectRootLocal = "/Users/liberty/works/virtual_machines/vm1/crawler";
    private $testRootLocal = "/Users/liberty/works/virtual_machines/vm1/crawler/app/tests";
    private $projectRootRemote = "/vagrant_data/crawler";
    private $testRootRemote = "/vagrant_data/crawler/app/tests";
    private $phpunitPathRemote = "/vagrant_data/crawler/vendor/bin/phpunit";
    private $phpunitConfigurationPathRemote = "/vagrant_data/crawler/phpunit.xml";
    private $phpunitBootstrapPathRemote = "/vagrant_data/crawler/bootstrap/autoload.php";

    /**
     * ssh2 configuration
     */
    private $hostRemote = 'vm1';
    private $portRemote = '2222';
    private $userRemote = 'vagrant';
    private $pubkey = '/Users/liberty/.vagrant.d/insecure_public_key';
    private $privkey = '/Users/liberty/.vagrant.d/insecure_private_key';

    /**
     * remote nb path configuration
     */
    private $nbSuitePathRemote = "/tmp/NetBeansSuite.php";

    //////////////////////////////////////////////////////

    private function __construct()
    {
        
    }

    /**
     * 
     * @return \Config
     */
    public static function getInstance()
    {
        return new self;
    }

    public function getProjectRootLocal()
    {
        return $this->projectRootLocal;
    }

    public function getTestRootLocal()
    {
        return $this->testRootLocal;
    }

    public function getProjectRootRemote()
    {
        return $this->projectRootRemote;
    }

    public function getTestRootRemote()
    {
        return $this->testRootRemote;
    }

    public function getPhpunitPathRemote()
    {
        return $this->phpunitPathRemote;
    }

    public function getPhpunitConfigurationPathRemote()
    {
        return $this->phpunitConfigurationPathRemote;
    }

    public function getPhpunitBootstrapPathRemote()
    {
        return $this->phpunitBootstrapPathRemote;
    }

    public function getHostRemote()
    {
        return $this->hostRemote;
    }

    public function getPortRemote()
    {
        return $this->portRemote;
    }

    public function getUserRemote()
    {
        return $this->userRemote;
    }

    public function getPubkey()
    {
        return $this->pubkey;
    }

    public function getPrivkey()
    {
        return $this->privkey;
    }

    public function getNbSuitePathRemote()
    {
        return $this->nbSuitePathRemote;
    }

}
