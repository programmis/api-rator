<?php

namespace ApiRator\Includes;

use Psr\Log\LoggerInterface;

/**
 * Class Opts
 *
 * @package ApiRator\Includes
 */
class Opts
{
    private $parameters = [];
    /** @var LoggerInterface */
    protected static $logger;
    private $magic_arg;
    private $headers = [];
    private $original_answer = '';
    private $request_timeout = 30;

    private static $beforeRequestCallback = [];
    private static $afterRequestCallback = [];

    /**
     * @var bool
     */
    private $ignoreDefaultHeader = false;

    /**
     * @return $this
     */
    public function ignoreDefaultHeader()
    {
        $this->ignoreDefaultHeader = true;

        return $this;
    }

    /**
     * @return bool
     */
    public function isIgnoreDefaultHeader()
    {
        return $this->ignoreDefaultHeader;
    }

    /**
     * @return array
     */
    public function getBeforeRequestCallback()
    {
        return self::$beforeRequestCallback;
    }

    /**
     * @return array
     */
    public function getAfterRequestCallback()
    {
        return self::$afterRequestCallback;
    }

    /**
     * @param        $object_or_function
     * @param string $method
     *
     * @throws \Exception
     */
    private static function checkObjectMethod($object_or_function, $method = '')
    {
        if (is_object($object_or_function)) {
            if (!method_exists($object_or_function, $method)) {
                $error = "method $method is not exist";
                if (self::$logger) {
                    self::$logger->critical($error);
                }
                throw new \Exception($error);
            }
        } elseif (!is_callable($object_or_function)) {
            $error = "first param must be object or function";
            if (self::$logger) {
                self::$logger->critical($error);
            }
            throw new \Exception($error);
        }
    }

    /**
     * @param array $callbackType
     * @param string $access_token
     */
    protected function execCallback($callbackType, $access_token = '')
    {
        if (isset($callbackType['object'], $callbackType['method'])) {
            $object = $callbackType['object'];
            $method = $callbackType['method'];
            $object->$method($access_token);
        } elseif (isset($callbackType['function'])) {
            $function = $callbackType['function'];
            $function($access_token);
        }
    }

    /**
     * @param        $object_or_function
     * @param string $method
     */
    public static function setBeforeRequestCallback($object_or_function, $method = '')
    {
        self::checkObjectMethod($object_or_function, $method);
        if (is_object($object_or_function)) {
            self::$beforeRequestCallback = [
                'object' => $object_or_function,
                'method' => $method
            ];
        } else {
            self::$beforeRequestCallback = [
                'function' => $object_or_function
            ];
        }
    }

    /**
     * @param        $object_or_function
     * @param string $method
     */
    public static function setAfterRequestCallback($object_or_function, $method = '')
    {
        self::checkObjectMethod($object_or_function, $method);
        if (is_object($object_or_function)) {
            self::$afterRequestCallback = [
                'object' => $object_or_function,
                'method' => $method
            ];
        } else {
            self::$afterRequestCallback = [
                'function' => $object_or_function
            ];
        }
    }

    /**
     * @param LoggerInterface $logger
     *
     * @throws \Exception
     */
    public static function setLogger($logger)
    {
        if ($logger) {
            if (!is_object($logger)) {
                $logger = new $logger();
            }
            if (!($logger instanceof LoggerInterface)) {
                throw new \Exception("Logger must by implemented from LoggerInterface");
            }
            self::$logger = $logger;
        }
    }

    /**
     * @return int
     */
    public function getRequestTimeout()
    {
        return $this->request_timeout;
    }

    /**
     * @param int $request_timeout
     */
    public function setRequestTimeout($request_timeout)
    {
        $this->request_timeout = $request_timeout;

        return $this;
    }

    /**
     * Opts constructor.
     *
     * @param string $magic_arg
     * @param LoggerInterface|null $logger
     */
    public function __construct($magic_arg, $logger = null)
    {
        $this->magic_arg = $magic_arg;
        self::setLogger($logger);
    }

    /**
     * @param string $name
     * @param        $value
     */
    public function __set($name, $value)
    {
        if (preg_match('|' . $this->magic_arg . '_(.*)|', $name, $res)) {
            if (isset($res[1]) && $res[1]) {
                $this->setParameter($res[1], $value);
            }
        } else {
            if (self::$logger) {
                self::$logger->warning('Set unknown variable: ' . $name);
            }
        }
    }

    /**
     * @param string $name
     *
     * @return null|string
     */
    public function __get($name)
    {
        if (preg_match('|' . $this->magic_arg . '_(.*)|', $name, $res)) {
            if (isset($res[1]) && $res[1] && array_key_exists($res[1], $this->parameters)) {
                return $this->getParameter($res[1]);
            }
        } else {
            if (self::$logger) {
                self::$logger->warning('Get unknown variable: ' . $name);
            }
        }

        return null;
    }

    /**
     * original answer after execApi
     *
     * @return string
     */
    public function getOriginalAnswer()
    {
        return $this->original_answer;
    }

    /**
     * @param string $original_answer
     *
     * @return Opts
     */
    protected function setOriginalAnswer($original_answer)
    {
        $this->original_answer = $original_answer;

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
                    if (self::$logger) {
                        self::$logger->critical($error);
                    }
                    throw new \Exception($error);
                }
            } else {
                if (self::$logger) {
                    self::$logger->warning("Wrong parameter: '" . $key . "', value: " . $param);
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
        $parameters = [];
        foreach ($this->parameters as $key => $param) {
            $parameters[$key] = $param['value'];
        }

        return $parameters;
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
                if (self::$logger) {
                    self::$logger->info('Set parameter: ' . $name . ' as array, values: ' . serialize($value));
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
        ) {
            if (self::$logger) {
                self::$logger->error('Please set valid parameters');
            }

            return $this;
        }
        $r_params = [];

        foreach ($params as $key => $param) {
            if (is_array($param)) {
                if (isset($param['value']) || isset($param['required'])) {
                    if (isset($param['value'])) {
                        $r_params[$key]['value'] = $param['value'];
                    }
                    if (isset($param['required'])) {
                        $r_params[$key]['required'] = $param['required'];
                    }
                } elseif ($param) {
                    $r_params[$key]['value'] = $param;
                }
            } else {
                $r_params[$key]['value'] = $param;
            }
        }

        $this->parameters = $r_params;

        return $this;
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
