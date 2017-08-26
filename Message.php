<?php

namespace Kapi\Http;

class Message extends AbstractMessage
{
    /**
     * @param array $headers
     * @return static
     */
    public function setHeaders(array $headers)
    {
        $new = clone $this;
        $new->headers = $new->headerNames = [];
        $new = $new->withAddedHeaders($headers);

        return $new;
    }

    /**
     * @param array $headers
     * @return static
     */
    public function withHeaders(array $headers)
    {
        $new = clone $this;

        foreach ($headers as $name => $value) {
            $new = $new->withHeader($name, $value);
        }

        return $new;
    }

    /**
     * @param array $headers
     * @return static
     */
    public function withAddedHeaders(array $headers)
    {
        $new = clone $this;

        foreach ($headers as $name => $value) {
            $new = $new->withAddedHeader($name, $value);
        }

        return $new;
    }
}