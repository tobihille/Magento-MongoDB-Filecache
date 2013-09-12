<?php

class Mongo_Filewrapper
{

    protected $_location = array('127.0.0.1');
    protected $_port     = array('27017');
    protected $_username = '';
    protected $_password = '';
    protected $_database = 'filewrapper';
    protected $_collection = 'files';
    protected $_options  = array();

    /**
     * @var MongoClient plain connection to Mongo instance(s)
     */
    private $_client = null;

    /**
     * @var MongoDB connection to database
     */
    private $_db = null;

    /**
     * @var MongoCollection wrapper for default connection for less code to access this ;)
     */
    private $_coll = null;

    public function __construct(array $location = null, array $port = null, string $username = null,
        string $password= null, string $database = null, string $collection = null, array $options = null)
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

        if ( !empty($collection) )
            $this->_collection = $collection;

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

        $this->_coll = $this->_db->selectCollection($this->_collection);
    }

    protected function buildConenctionString()
    {
        $str = '';
        if ( count($this->_location) != count($this->_port) )
        {
            throw new MongoException("Number of locations does not match number of ports.");
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
        //see disk_free_space
        return PHP_INT_MAX;
    }

    public function unlink(string $filename)
    {
        return $this->_coll->remove( array('filename' => $filename) );
    }

    public function is_file(string $filename)
    {
        $cursor = $this->_coll->find( array('filename' => $filename) );

        $cnt = 0;
        foreach ( $cursor as $result )
        {
            $cnt++;
        }

        return $cnt > 0;
    }

    public function is_dir(string $filename)
    {
        //we are not interested in directories, we save files including path
        // so just return true so no one tries to create a document for a directory
        return true;
    }

    public function glob(string $filename, int $flag)
    {
        //don't know what to do here, please help!
    }

    public function rmdir(string $filename)
    {
        //remove all documents with given string enclosed in '/' (pattern of a directory - name)
        // if someone finds a better way, please contribute!
        return $this->_coll->remove( array('filename' => '/\/'.$filename.'\//') );
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
        //normally returns a filehandle, we just return the filename
        // so that it will be passed to wrapper - functions which
        // expect a filename
        return $filename;
    }

    public function stream_get_contents(string $filename)
    {
        $cursor = $this->_coll->find( array('filename' => $filename) );

        $content = '';
        foreach ( $cursor as $result )
        {
            //if we have more than 1 file we just read the first
            $content = $result['content'];
            break;
        }

        return $content;
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
        //we just return $offset even if SEEK_CUR is used
        // because we do not maintain a position
        if ( $mode == SEEK_SET || $mode == SEEK_CUR )
        {
            return $offset;
        }
        if ( $mode == SEEK_END )
        {
            $cursor = $this->_coll->find( array('filename' => $filename) );

            $content = '';
            foreach ( $cursor as $result )
            {
                //if we have more than 1 file we just read the first
                $content = $result['content'];
                break;
            }

            $len = strlen($content);

            return $len + $offset;
        }
    }

    public function ftruncate(string $filename, int $size)
    {
        $cursor = $this->_coll->find( array('filename' => $filename) );

        $content = '';
        $res = null;
        foreach ( $cursor as $result )
        {
            //if we have more than 1 file we just read the first
            $content = $result['content'];
            $res = $result;
            break;
        }

        $newStr = substr($content, 0, $size);
        $res['content'] = $newStr;
        return $this->_coll->update( array('filename' => $filename), $res );
    }
}
