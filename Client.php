<?php

namespace Kapi\Http;

class Client
{
    /**
     * @var Request
     */
    private $request;

    /**
     * @var Response
     */
    private $response;

    /**
     * @var array
     */
    private $_config;

    /**
     * @var resource
     */
    private $context;

    /**
     * @var array
     */
    private $http;

    /**
     * @var array
     */
    private $ssl;

    /**
     * Client constructor.
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->request = new Request();
        $this->response = new Response();

        $this->setConfig($config);
    }

    public function setConfig(array $config)
    {
        $this->_config = $config;
    }

    public function setHeader($name, $value)
    {
        $this->request = $this->request->withHeader($name, $value);
    }

    public function addHeader($name, $value)
    {
        $this->request = $this->request->withAddedHeader($name, $value);
    }

    public function removeHeader($name)
    {
        $this->request = $this->request->withoutHeader($name);
    }

    public function get($url)
    {
        return $this->launch($url, 'GET');
    }

    public function post($url, $data = [], array $config = [])
    {
        return $this->launch($url, 'POST', $data, $config);
    }

    private function launch($url, $method, $data = [], array $config = [])
    {
        // $this->setConfig($config);

        $this->requestEmitter($url, $method, $data);
        $this->createContext();
        $this->send();

        return $this->response;
    }

    private function createContext()
    {
        $this->createHeaders();
        $this->createContent();
        $this->createOptions();

        if ('https' === $this->request->getUri()->getScheme()) {
            $this->createSsl();
        }

        $this->context = stream_context_create([
            'http' => $this->http,
            'ssl' => $this->ssl
        ]);
    }

    private function createHeaders()
    {
        $headers = [];
        foreach ($this->request->getHeaders() as $name => $values) {
            $headers[] = sprintf('%s: %s', $name, implode(', ', $values));
        }

        $this->http['header'] = implode("\r\n", $headers);
    }

    private function createContent()
    {
        $body = $this->request->getBody();

        if ($body) {
            $body->rewind();
            $content = $body->getContents();
        }

        $this->http['content'] = $content ?? '';
    }

    private function createOptions()
    {
        $this->http['method'] = $this->request->getMethod();
        $this->http['protocol_version'] = $this->request->getProtocolVersion() ?? '1.1';

        // TODO: merge Config and http options
    }

    private function createSsl()
    {
        // TODO: Implements createSsl() method.
        $this->ssl = [];
    }

    private function send()
    {
        $uri = $this->request->getUri();


        // prefer curl or file_get_contents; is equivalent to fread but best
        // stream_get_contents too
        $stream = new Stream(fopen($uri, 'rb', false, $this->context));
        $headers = $stream->getMetadata('wrapper_data');
        $content = $stream->getContents();
        $stream->close();


        $this->responseParser($headers, $content);
    }

    private function requestEmitter($uri, $method, $body = '', array $headers = [])
    {
        $this->request = $this->request->setUri($uri)->withMethod($method)->setBody($body);
        $this->request->addHeaders($headers);
    }

    private function responseParser($headers, $content)
    {
        $this->_parseHeaders($headers);

        $stream = new Stream('php://memory', 'wb+');
        $stream->write($content);
        $stream->rewind();

        $this->response = $this->response->withBody($stream);
    }

    private function _parseHeaders($headers)
    {
        foreach ($headers as $value) {
            if (substr($value, 0, 5) === 'HTTP/') {
                preg_match('/HTTP\/([\d.]+) ([0-9]+)(.*)/i', $value, $matches);
                $this->response = $this->response->withStatus((int)$matches[2], trim($matches[3]))->withProtocolVersion($matches[1]);
                continue;
            }
            list($name, $value) = explode(':', $value, 2);
            $value = trim($value);
            $name = trim($name);

            $this->response = $this->response->withAddedHeader($name, $value);
        }
    }
}