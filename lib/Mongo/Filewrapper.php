<?php

class Mongo_Filewrapper
{

    protected $_location = array('127.0.0.1');
    protected $_port     = array('27017');
    protected $_username = '';
    protected $_password = '';
    protected $_database = 'filewrapper';
    protected $_options  = array();

    private $_client = null;
    private $_db = null;

    public function __construct(array $location = null, array $port = null,
        string $username = null, string $password= null, string $database = null, array $options = null)
    {
        if ( !empty($location) )
            $this->_location = $location;

        if ( !empty($port) )
            $this->_port = $port;

        if ( !empty($username) )
            $this->_username = $username;

        if ( !empty($password) )
            $this->_password = $password;

        if ( !empty($database) )
            $this->_database = $database;

        $this->_options = $options;

        $this->init();
    }

    private function init()
    {
        if ( !is_array($this->_options) && !empty($this->_username) )
        {
            $this->_options = array(
                'username' => $this->_username,
                'password' => $this->_password,
            );
        }
        elseif ( !empty($this->_username) )
        {
            $this->_options['password'] = $this->_password;
            $this->_options['username'] = $this->_username;
        }
        else
        {
            $this->_options = array();
        }

        $this->_client = new MongoClient('mongodb://'.$this->buildConenctionString(), $this->_options);

        $this->_db = $this->_client->selectDB($this->_database);
    }

    protected function buildConenctionString()
    {
        $str = '';
        if ( count($this->_location) != count($this->_port) )
        {
            throw new MongoException("Number of locations does not match number of ports");
        }

        for ($i = 0; $i < count($this->_location); $i++)
        {
            $str .= $this->_location[$i].':'.$this->_port[$i].',';
        }
        $str = trim($str, ',');

        return $str;
    }

    public function is_writable(string $filename)
    {
        return true;
    }

    public function disk_free_space(string $filename)
    {
        //it is theoretically possible to retrieve free space but it's much effort
        // not only for programming but also for the machine, so let's use this instead
        return PHP_INT_MAX - 999;
    }

    public function disk_total_space(string $filename)
    {
        //see dist_free_space
        return PHP_INT_MAX;
    }

    public function unlink(string $filename)
    {

    }

    public function is_file(string $filename)
    {

    }

    public function is_dir(string $filename)
    {

    }

    public function glob(string $filename)
    {

    }

    public function rmdir(string $filename)
    {

    }

    public function mkdir(string $filename)
    {
        //no need do create directory, files are used as key including directory
        return true;
    }

    public function chmod(string $filename)
    {
        //if an key exists it is modifiable and readable, no support for rights
        return false;
    }

    public function fopen(string $filename)
    {

    }

    public function stream_get_contents(string $filename)
    {

    }

    public function fclose(string $filename)
    {
        //closing does not exsts (i think)
        return true;
    }

    public function flock(string $filename)
    {
        //even if there is something like locking i don't think we want it for speed reasons
        return false;
    }

    public function fseek(string $filename, int $offset, int $mode)
    {

    }

    public function ftruncate(string $filename, int $size)
    {

    }
}
