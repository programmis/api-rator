<?php

namespace ApiRator\Includes;

use Psr\Log\LoggerInterface;

class Opts
{

    private $access_token;
    private $parameters = [];
    private $method;
    protected $logger;
    private $magic_arg;
    private $v;
    private $headers = [];

    public function __construct($magic_arg /* string */, LoggerInterface $loggerInterface = null)
    {
        $this->magic_arg = $magic_arg;
        $this->logger = $loggerInterface;
    }

    public function __set($name, $value)
    {
        if (preg_match('|' . $this->magic_arg . '_(.*)|', $name, $res)) {
            if (isset($res[1]) && $res[1]) {
                $this->setParameter($res[1], $value);
            }
        } else {
            if($this->logger){
                $this->logger->warning('Set unknown variable: ' . $name);
            }
        }
    }

    public function __get($name)
    {
        if (preg_match('|' . $this->magic_arg . '_(.*)|', $name, $res)) {
            if (isset($res[1]) && $res[1] && array_key_exists($res[1], $this->data)) {
                return $this->data[$res[1]]['value'];
            }
        } else {
            if($this->logger){
                $this->logger->warning('Get unknown variable: ' . $name);
            }
        }
        return null;
    }

    public function setApiVersion($v)
    {
        return $this->setV($v);
    }

    /**
     * @return string
     */
    public function getV()
    {
        return $this->v;
    }

    public function getApiVersion(){
        return $this->getV();
    }
    
    /**
     * @return string
     */
    public function getAccessToken()
    {
        return $this->access_token;
    }
    

    public function setV($v)
    {
        $this->v = $v;
        return $this;
    }

    public function setRequiredParams($params)
    {
        if (is_array($params)) {
            foreach ($params as $param) {
                $this->parameters[$param]['required'] = true;
            }
        } else {
            $this->parameters[$params]['required'] = true;
        }
    }

    public function checkRequiredParams()
    {
        foreach ($this->parameters as $key => $param) {
            if (is_array($param)) {
                if (isset($param['required']) && $param['required'] && !isset($param['value'])) {
                    $error = "not fill '" . $key . "' required parameter!";
                    if($this->logger){
                        $this->logger->critical($error);
                    }
                    throw new \Exception($error);
                }
            } else {
                if($this->logger){
                    $this->logger->warning("Wrong parameter: '" . $key . "', value: " . $param);
                }
                unset($this->parameters[$key]);
            }
        }
    }

    public function getParameters()
    {
        $parameters = array();
        foreach ($this->parameters as $key => $param) {
            $parameters[$key] = $param['value'];
        }
        return $parameters;
    }

    public function setMethod($method)
    {
        $this->method = $method;
        return $this;
    }

    public function setParameter($name, $value)
    {
        if (is_array($value)) {
            if (isset($value['value'])) {
                $this->parameters[$name]['value'] = $value['value'];
            } else {
                if($this->logger){
                    $this->logger->warning('Can\'t set parameter: ' . $name . ', values: ' . serialize($value));
                }
            }
            if (isset($value['required'])) {
                $this->parameters[$name]['required'] = $value['required'];
            }
        } else {
            $this->parameters[$name]['value'] = $value;
        }
        return $this;
    }

    public function setParameters($param)
    {
        if(
            !is_array($param) 
            || (is_array($param[0]) && !isset($param[0]['value']))
            || !isset($param['value'])
        ){
            if($this->logger){
                $this->logger->error('Please set valid parameters');
            }
            return $this;
        }
        $this->parameters = $param;
        return $this;
    }

    public function setAccessToken($access_token)
    {
        $this->access_token = $access_token;
        return $this;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    public function addHeader($name, $header)
    {
        $this->headers[$name] = $header;
        return $this;
    }
    
}