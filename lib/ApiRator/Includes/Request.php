<?php

namespace ApiRator\Includes;


abstract class Request extends Opts
{

    public function execApi()
    {
        $this->checkRequiredParams();

        $url = $this->getResultApiUrl();

        if ($this->logger) {
            $this->logger->debug("execApi: " . $url);
        }

        $parameters = $this->getParameters();

        if ($this->logger) {
            $this->logger->debug("with parameters: " . serialize($parameters));
        }

        $apiCurl = curl_init($url);
        curl_setopt($apiCurl, CURLOPT_POST, 1);
        curl_setopt($apiCurl, CURLOPT_HTTPHEADER, array("Content-type: multipart/form-data"));
        curl_setopt($apiCurl, CURLOPT_POSTFIELDS, $parameters);
        curl_setopt($apiCurl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($apiCurl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($apiCurl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($apiCurl, CURLOPT_SSL_VERIFYHOST, 0);
        $apiContent = curl_exec($apiCurl);

        if ($apiContent === false) {
            if ($this->logger) {
                $this->logger->error(curl_error($apiCurl));
            }
            curl_close($apiCurl);
            return false;
        }

        curl_close($apiCurl);

        if (!$apiContent) {
            if ($this->logger) {
                $this->logger->debug("apiContent is empty");
            }
            return false;
        }

        if ($this->logger) {
            $this->logger->debug("execApi result: " . $apiContent);
        }

        return $this->answerProcessing($apiContent);
    }

    abstract public function getResultApiUrl();
    abstract public function answerProcessing($content);

}