<?php
/**
 * amoCRM API Logger
 */
namespace Ufee\Amo\Api;

class Logger
{
	private $path = '/logs/',
			$options = [
				'name' => '',
				'chunk' => 'm-Y',
				'date_format' => "d.m.Y H:i:s \r\n-------------------\r\n",
			];
	private static
			$_instances = [];

			
    /**
     * Constructor
     */
	private function __construct($name)
    {
		if (!is_string($name) && !is_array($name)) {
			throw new \Exception('Logger filename must be string or array');
		}
		if (is_string($name)) {
			$name = ['name' => $name];
		}
		$this->options = array_merge($this->options, $name);
		$this->options['name'] = ltrim($this->options['name'], '/');
		
		if (strpos($this->options['name'], '/') > 0) {
			$exps = explode('/', $this->options['name']);
			$this->options['name'] = array_pop($exps);
			$this->path = '/logs/'.join('/', $exps).'/';
		}
		if ($this->options['chunk'] != '') {
			$this->path .= date($this->options['chunk'].'/', time());
		}
        $this->path = AMOAPI_ROOT.$this->path;
        if (!file_exists($this->path)) {
            mkdir($this->path);
        }
	}
	
    /**
     * Get instance
	 * @return object
     */
    public static function getInstance($name = null)
    {
		if (is_null($name)) {
			if (empty(static::$_instances)) {
				throw new \Exception('Empty logger instance');
			}
			return end(static::$_instances);
		}
		if (!array_key_exists($name, static::$_instances)) {
			static::$_instances[$name] = new static($name);
		}
		return static::$_instances[$name];
	}
	
    /**
     * Log data
	 * @param mixed ... args
	 * @return bool
     */
    public function log()
    {
		$write = [];
		$args = func_get_args();
		
		foreach ($args as $arg) {
            $data = trim(print_r($arg, 1));
            if ($data !== '') {
                $write[]= $data;
            }
		}
		return file_put_contents(
			$this->path.'/'.$this->options['name'], 
            date($this->options['date_format'], time())." - \t".join(" \r\n - \t", $write)."\r\n \r\n",
            FILE_APPEND | LOCK_EX
		);
	}
}