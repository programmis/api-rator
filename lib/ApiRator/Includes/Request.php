<?php

namespace ApiRator\Includes;

use CURLFile;

/**
 * Class Request
 *
 * @package ApiRator\Includes
 */
abstract class Request extends Opts
{
    /**
     * @param string $url
     * @param array  $parameters
     *
     * @return bool|string
     */
    private function request($url, $parameters)
    {
        $http_headers = $this->prepareHeaders();
        if (self::$logger) {
            self::$logger->debug("with parameters: " . serialize($parameters));
        }

        $apiCurl = curl_init($url);
        curl_setopt($apiCurl, CURLOPT_POST, 1);
        curl_setopt($apiCurl, CURLOPT_TIMEOUT, $this->getRequestTimeout());
        if ($http_headers) {
            curl_setopt($apiCurl, CURLOPT_HTTPHEADER, $http_headers);
            curl_setopt($apiCurl, CURLOPT_POSTFIELDS, $parameters);
        } else {
            curl_setopt($apiCurl, CURLOPT_POSTFIELDS, http_build_query($parameters));
        }
        curl_setopt($apiCurl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($apiCurl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($apiCurl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($apiCurl, CURLOPT_SSL_VERIFYHOST, 0);

        $this->execCallback($this->getBeforeRequestCallback());
        $apiContent = curl_exec($apiCurl);
        $this->execCallback($this->getAfterRequestCallback());

        if ($apiContent === false) {
            if (self::$logger) {
                self::$logger->error("CURL returned error: " . curl_error($apiCurl));
            }
            curl_close($apiCurl);

            return false;
        }

        curl_close($apiCurl);

        if (!$apiContent) {
            if (self::$logger) {
                self::$logger->debug("CURL content is empty");
            }

            return false;
        }

        return $apiContent;
    }

    /**
     * @param string $upload_url
     * @param array  $files path to file
     *
     * @return bool
     */
    public function uploadFiles($upload_url, $files, $file_name = 'file')
    {
        $curl_files = [];
        foreach ($files as $key => $data) {
            $path = realpath($data);
            if ($path) {
                $curl_files[$file_name . ($key + 1)] = (
                (class_exists('CURLFile', false)) ?
                    new CURLFile(realpath($data)) :
                    '@' . realpath($data)
                );
            }
        }
        if (!$curl_files) {
            self::$logger->error('Empty curl_files array');

            return false;
        }

        if (self::$logger) {
            self::$logger->debug("uploadFiles to: " . $upload_url);
        }

        $vkContent = $this->request($upload_url, $curl_files);
        if (!$vkContent) {
            return false;
        }

        self::$logger->debug("execUrl result: " . $vkContent);

        $this->setOriginalAnswer($vkContent);

        return $this->answerProcessing($vkContent);
    }

    /**
     * @return bool
     */
    public function execApi()
    {
        $this->checkRequiredParams();

        $url = $this->getResultApiUrl();

        if (self::$logger) {
            self::$logger->debug("execApi: " . $url);
        }

        $parameters = $this->getParameters();
        $parameters = $this->handleParameters($parameters);

        $apiContent = $this->request($url, $parameters);
        if (!$apiContent) {
            return false;
        }

        if (self::$logger) {
            self::$logger->debug("execApi result: " . $apiContent);
        }

        $this->setOriginalAnswer($apiContent);

        return $this->answerProcessing($apiContent);
    }

    /**
     * @return string
     */
    abstract public function getResultApiUrl();

    /**
     * use it in getResultApiUrl
     *
     * @return string
     */
    abstract public function getMethod();

    /**
     * @param $content
     *
     * @return bool
     */
    abstract public function answerProcessing($content);

    /**
     * use it in getResultApiUrl
     *
     * @return string
     */
    abstract public function getApiVersion();

    /**
     * @return string
     */
    abstract public function getAccessToken();

    /**
     * @param $parameters
     *
     * @return mixed
     */
    public function handleParameters($parameters)
    {
        return $parameters;
    }

    /**
     * @return array
     */
    public function prepareHeaders()
    {
        $headers['Content-type'] = 'multipart/form-data';

        $headers      = array_merge($headers, $this->getHeaders());
        $http_headers = [];
        foreach ($headers as $key => $header) {
            $http_headers[] = $key . ': ' . $header;
        }

        if (self::$logger) {
            self::$logger->debug('with headers: ' . serialize($headers));

            return $http_headers;
        }

        return $http_headers;
    }
}
