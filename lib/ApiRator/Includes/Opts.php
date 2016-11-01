<?php

namespace ApiRator\Includes;

use Psr\Log\LoggerInterface;

/**
 * Class Opts
 * @package ApiRator\Includes
 */
class Opts
{
    private $access_token;
    private $parameters = [];
    private $method;
    protected $logger;
    private $magic_arg;
    private $v;
    private $headers = [];

    /**
     * Opts constructor.
     * @param string $magic_arg
     * @param LoggerInterface|null $loggerInterface
     */
    public function __construct($magic_arg, LoggerInterface $loggerInterface = null)
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
            if ($this->logger) {
                $this->logger->warning('Set unknown variable: ' . $name);
            }
        }
    }

    public function __get($name)
    {
        if (preg_match('|' . $this->magic_arg . '_(.*)|', $name, $res)) {
            if (isset($res[1]) && $res[1] && array_key_exists($res[1], $this->parameters)) {
                return $this->getParameter($res[1]);
            }
        } else {
            if ($this->logger) {
                $this->logger->warning('Get unknown variable: ' . $name);
            }
        }
        return null;
    }

    /**
     * @param string $v
     *
     * @return Opts
     */
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

    /**
     * @return string
     */
    public function getApiVersion()
    {
        return $this->getV();
    }

    /**
     * @return string
     */
    public function getAccessToken()
    {
        return $this->access_token;
    }

    /**
     * @param string $v
     *
     * @return $this
     */
    public function setV($v)
    {
        $this->v = $v;

        return $this;
    }

    /**
     * @param string|array $params
     */
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

    /**
     * @throws \Exception
     */
    public function checkRequiredParams()
    {
        foreach ($this->parameters as $key => $param) {
            if (is_array($param)) {
                if (isset($param['required']) && $param['required'] && !isset($param['value'])) {
                    $error = "not fill '" . $key . "' required parameter!";
                    if ($this->logger) {
                        $this->logger->critical($error);
                    }
                    throw new \Exception($error);
                }
            } else {
                if ($this->logger) {
                    $this->logger->warning("Wrong parameter: '" . $key . "', value: " . $param);
                }
                unset($this->parameters[$key]);
            }
        }
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public function getParameter($name)
    {
        return isset($this->parameters[$name]['value'])
            ? $this->parameters[$name]['value'] : '';
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        $parameters = array();
        foreach ($this->parameters as $key => $param) {
            $parameters[$key] = $param['value'];
        }
        return $parameters;
    }

    /**
     * @param string $method
     *
     * @return $this
     */
    public function setMethod($method)
    {
        $this->method = $method;

        return $this;
    }

    /**
     * @param string $name
     * @param array|string $value maybe ['value' => 'test', 'require' => true]
     *
     * @return $this
     */
    public function setParameter($name, $value)
    {
        if (is_array($value)) {
            if (isset($value['value'])) {
                $this->parameters[$name]['value'] = $value['value'];
            } elseif (!isset($value['required'])) {
                $this->parameters[$name]['value'] = $value;
                if ($this->logger) {
                    $this->logger->info('Set parameter: ' . $name . ' as array, values: ' . serialize($value));
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

    /**
     * @param array $params ['test' => ['value' => 'test']]
     *
     * @return $this
     */
    public function setParameters($params)
    {
        if (!is_array($params)
            || !isset($params[key($params)])
            || (
                !isset($params[key($params)]['value'])
                && !isset($params[key($params)]['required'])
            )
        ) {
            if ($this->logger) {
                $this->logger->error('Please set valid parameters');
            }
            return $this;
        }
        $this->parameters = $params;

        return $this;
    }

    /**
     * @param string $access_token
     *
     * @return $this
     */
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

    /**
     * @param string $name
     * @param string $header
     *
     * @return $this
     */
    public function addHeader($name, $header)
    {
        $this->headers[$name] = $header;

        return $this;
    }
}
