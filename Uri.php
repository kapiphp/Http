<?php

namespace Kapi\Http;

use InvalidArgumentException;

class Uri extends AbstractUri
{
    public const GEN_DELIMS_CHARACTERS = ':\/\?\#\[\]@';
    public const SUB_DELIMS_CHARACTERS =  '!\$&\'\(\)\*\+,;=';
    public const RESERVED_CHARACTERS = self::GEN_DELIMS_CHARACTERS . self::SUB_DELIMS_CHARACTERS;
    public const UNRESERVED_CHARACTERS = '\w\-\.~';

    /**
     * @var array
     */
    protected $allowedSchemes = [
        'http',
        'https',
    ];

    /**
     * Uri constructor.
     *
     * @param string $uri
     */
    public function __construct($uri = '')
    {
        if (!is_string($uri)) {
            throw new InvalidArgumentException(sprintf(
                'URI passed to constructor must be a string; received "%s"',
                (is_object($uri) ? get_class($uri) : gettype($uri))
            ));
        }

        $parts = parse_url($uri);

        if (!$parts) {
            throw new InvalidArgumentException(
                'The source URI string appears to be malformed'
            );
        }

        $this->scheme    = isset($parts['scheme']) ? $this->_filterScheme($parts['scheme']) : '';
        $this->userInfo  = $parts['user'] ?? '';
        $this->host      = $parts['host'] ?? '';
        $this->port      = $parts['port'] ?? null;
        $this->path      = isset($parts['path']) ? $this->_filterPath($parts['path']) : '';
        $this->query     = isset($parts['query']) ? $this->_filterQuery($parts['query']) : '';
        $this->fragment  = isset($parts['fragment']) ? $this->_filterFragment($parts['fragment']) : '';

        if (isset($parts['pass'])) {
            $this->userInfo .= ':' . $parts['pass'];
        }
    }

    /**
     * @inheritDoc
     */
    public function withScheme($scheme)
    {
        if (!is_string($scheme)) {
            throw new InvalidArgumentException('Scheme must be a string');
        }

        $scheme = $this->_filterScheme($scheme);

        $new = clone $this;
        $new->scheme = $scheme;
        $new->_validateState();

        return $new;
    }

    /**
     * @inheritDoc
     */
    public function withUserInfo($user, $password = null)
    {
        if (!is_string($user)) {
            throw new InvalidArgumentException(sprintf(
                '%s expects a string user argument; received %s',
                __METHOD__,
                (is_object($user) ? get_class($user) : gettype($user))
            ));
        }

        if (null !== $password && !is_string($password)) {
            throw new InvalidArgumentException(sprintf(
                '%s expects a string password argument; received %s',
                __METHOD__,
                (is_object($password) ? get_class($password) : gettype($password))
            ));
        }

        $new = clone $this;
        $new->userInfo = $user . ($password ? ':' . $password : '');

        return $new;
    }

    /**
     * @inheritDoc
     */
    public function withHost($host)
    {
        if (!is_string($host)) {
            throw new InvalidArgumentException(sprintf(
                '%s expects a string argument; received %s',
                __METHOD__,
                is_object($host) ? get_class($host) : gettype($host)
            ));
        }

        $new = clone $this;
        $new->host = $host;
        $new->_validateState();

        return $new;
    }

    /**
     * @inheritDoc
     */
    public function withPath($path)
    {
        if (!is_string($path)) {
            throw new InvalidArgumentException(
                'Invalid path provided; must be a string'
            );
        }

        if (strpos($path, '?') !== false) {
            throw new InvalidArgumentException(
                'Invalid path provided; must not contain a query string'
            );
        }

        if (strpos($path, '#') !== false) {
            throw new InvalidArgumentException(
                'Invalid path provided; must not contain a URI fragment'
            );
        }

        $path = $this->_filterPath($path);

        $new = clone $this;
        $new->path = $path;
        $new->_validateState();

        return $new;
    }

    /**
     * @inheritDoc
     */
    public function withQuery($query)
    {
        if (!is_string($query)) {
            throw new InvalidArgumentException(
                'Query string must be a string'
            );
        }

        if (strpos($query, '#') !== false) {
            throw new InvalidArgumentException(
                'Query string must not include a URI fragment'
            );
        }

        $query = $this->_filterQuery($query);

        $new = clone $this;
        $new->query = $query;

        return $new;

    }

    /**
     * @inheritDoc
     */
    public function withFragment($fragment)
    {
        if (!is_string($fragment)) {
            throw new InvalidArgumentException(sprintf(
                '%s expects a string argument; received %s',
                __METHOD__,
                (is_object($fragment) ? get_class($fragment) : gettype($fragment))
            ));
        }

        $fragment = $this->_filterFragment($fragment);

        $new = clone $this;
        $new->fragment = $fragment;

        return $new;
    }

    /**
     * Filters the scheme to ensure it is a valid scheme.
     *
     * @param string $scheme Scheme name.
     * @return string Filtered scheme.
     * @throws \InvalidArgumentException If the scheme is invalid.
     */
    private function _filterScheme($scheme)
    {
        $scheme = strtolower($scheme);
        $scheme = preg_replace('#:(//)?$#', '', $scheme);

        if (!preg_match('/\A[a-z][a-z0-9\+\-\.]+\z/', $scheme)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid scheme "%s" specified; must be a valid RFC 3986 scheme',
                $scheme
            ));
        }

        if ($scheme && !in_array($scheme, $this->allowedSchemes)) {
            throw new InvalidArgumentException(sprintf(
                'Unsupported scheme "%s"; must be any empty string or in the set (%s)',
                $scheme,
                implode(', ', array_keys($this->allowedSchemes))
            ));
        }

        return $scheme;
    }

    /**
     * Filters the path of a URI to ensure it is properly encoded.
     *
     * @param string $path
     * @return string
     */
    private function _filterPath($path)
    {
        $path = preg_replace_callback(
            '/(?:[^' . self::UNRESERVED_CHARACTERS . self::SUB_DELIMS_CHARACTERS . '%:@\/]+|%(?![A-Fa-f0-9]{2}))/',
            [$this, '_rawUrlEncode'],
            $path
        );

        if ($path && $path[0] === '/') {
            $path = '/' . ltrim($path, '/');
        }

        return $path;
    }

    /**
     * Filter a query string to ensure it is properly encoded.
     *
     * Ensures that the values in the query string are properly urlencoded.
     *
     * @param string $query
     * @return string
     */
    private function _filterQuery($query)
    {
        if ($query && $query[0] === '?') {
            $query = substr($query, 1);
        }

        $parts = explode('&', $query);
        foreach ($parts as $index => $part) {
            list($key, $value) = $this->_splitQueryValue($part);
            if ($value === null) {
                $parts[$index] = $this->_filterQueryOrFragment($key);
                continue;
            }
            $parts[$index] = sprintf(
                '%s=%s',
                $this->_filterQueryOrFragment($key),
                $this->_filterQueryOrFragment($value)
            );
        }

        return implode('&', $parts);
    }

    /**
     * Split a query value into a key/value tuple.
     *
     * @param string $value
     * @return array A value with exactly two elements, key and value
     */
    private function _splitQueryValue($value)
    {
        $data = explode('=', $value, 2);
        if (1 === count($data)) {
            $data[] = null;
        }
        return $data;
    }

    /**
     * Filter a fragment value to ensure it is properly encoded.
     *
     * @param null|string $fragment
     * @return string
     */
    private function _filterFragment($fragment)
    {
        if ($fragment && $fragment[0] === '#') {
            $fragment = substr($fragment, 1);
        }

        return $this->_filterQueryOrFragment($fragment);
    }

    /**
     * Filter a query string key or value, or a fragment.
     *
     * @param string $value
     * @return string
     */
    private function _filterQueryOrFragment($value)
    {
        return preg_replace_callback(
            '/(?:[^' . self::UNRESERVED_CHARACTERS . self::SUB_DELIMS_CHARACTERS . '%:@\/\?]+|%(?![A-Fa-f0-9]{2}))/',
            [$this, '_rawUrlEncode'],
            $value
        );
    }

    /**
     * URL encode a character returned by a regex.
     *
     * @param array $matches
     * @return string
     */
    private function _rawUrlEncode(array $matches)
    {
        return rawurlencode($matches[0]);
    }

    /**
     * Valid Uri State
     *
     * @throws \InvalidArgumentException
     */
    private function _validateState()
    {
        if (!$this->getAuthority()) {
            if (0 === strpos($this->path, '//')) {
                throw new InvalidArgumentException('The path of a URI without an authority must not start with two slashes "//"');
            }
            if (!$this->scheme && false !== strpos(explode('/', $this->path, 2)[0], ':')) {
                throw new InvalidArgumentException('A relative URI must not have a path beginning with a segment containing a colon');
            }
        } elseif (isset($this->path[0]) && $this->path[0] !== '/') {
            throw new InvalidArgumentException('The path of a URI with an authority must start with a slash "/" or be empty');
        }
    }

    /**
     * * TODO: Control for five rules
     *
     * RFC
     * Si un URI contient un composant d'autorité, le composant du chemin doit être vide ou commencer par un caractère de barre oblique ("/"). Si un URI ne contient pas de composant d'autorité, le chemin ne peut pas commencer par deux caractères slash ("//"). En outre, une référence URI (Section 4.1) peut être une référence de chemin relatif, auquel cas le premier segment de chemin ne peut pas contenir un caractère de deux points (":"). L'ABNF requiert cinq règles distinctes pour désambiguiser ces cas, dont une seule correspondra à la sous-chaîne de chemin d'accès dans une référence URI donnée. Nous utilisons le terme générique «composant du chemin» pour décrire la sous-chaîne URI associée par l'analyseur à l'une de ces règles.

          Chemin = chemin-abempty; Commence par "/" ou est vide
                        / Chemin-absolu; Commence par "/" mais pas "//"
                        / Path-noscheme; Commence par un segment non-colon
                        / Path-root; Commence par un segment
                        / Chemin-vide; Zéro caractère

          Chemin-abempty = * (segment "/")
          Path-absolute = "/" [segment-nz * (segment "/")]
          Path-noscheme = segment-nz-nc * (segment "/") s'il y a pas de scheme et d'autorité
          Path-rootless = segment-nz * (segment "/")
          Chemin-vide = 0 <pchar>
          Segment = * pchar
          Segment-nz = 1 * pchar
          Segment-nz-nc = 1 * (non-sauvegardé / codé / sub-delims / "@")
                        ; Segment sans longueur de zéro sans aucun colon ":"

          Pchar = non-sauvegardé / codé / sub-delims / ":" / "@"
     *
     *
     *
     * toString
     *  Cas où le chemin doit être ajusté pour rendre la référence URI
      Valable en tant que PHP ne permet pas de lancer une exception dans __toString ():
        - Si le chemin est sans racine et une autorité est présente, le chemin DOIT
          Être préfixé par "/".
        - Si le chemin commence avec plus d'un "/" et qu'aucune autorité n'est
          Présent, les barres de démarrage DOIVENT être réduites à un.
     */
    private function _validPath()
    {
        $segment = '([' . self::UNRESERVED_CHARACTERS . self::SUB_DELIMS_CHARACTERS . ':@]|%[a-fA-F0-9]{2})+';
        if ($this->getAuthority()) {
            // path abempty
            if (!preg_match('/^(\/' . $segment . ')*$/', $this->path)) {
                throw new InvalidArgumentException('The path of a URI with an authority must start with a slash "/" or be empty');
            }
        } else {
            // path absolute
            if (!preg_match('/^\/(' . $segment . '(\/' . $segment . ')*)?$/', $this->path)) {
                if (!$this->scheme) {
                    // path noscheme
                    if (!preg_match('/^([' . self::UNRESERVED_CHARACTERS . self::SUB_DELIMS_CHARACTERS . '@]|%[a-fA-F0-9]{2})+(\/' . $segment . ')*$/', $this->path)) {
                        throw new InvalidArgumentException('A relative URI must not have a path beginning with a segment containing a colon');
                    }
                } else {
                    // path rootless
                    if (!preg_match('/^' . $segment . '(\/' . $segment . ')*$/', $this->path)) {
                        throw new InvalidArgumentException('The path of a URI without an authority must not start with two slashes "//"');
                    }
                }
            }
        }

        // double "/" est valide mais pas conseillé
        //
        //pchar = unreserved / pct-encoded / sub-delims / ":" / "@"

        /**
         * Si un URI contient un composant d'autorité,
           le composant du chemin doit être vide ou commencer par un caractère de barre oblique ("/").
         * Si un URI ne contient pas de composant d'autorité,
           le chemin ne peut pas commencer par deux caractères slash ("//").
         * En outre, une référence URI (Section 4.1) peut être une référence de chemin relatif,
           auquel cas le premier segment de chemin ne peut pas contenir un caractère de deux points (":").
         * L'ABNF requiert cinq règles distinctes pour désambiguiser ces cas,
           dont une seule correspondra à la sous-chaîne de chemin d'accès dans une référence URI donnée.
         * Nous utilisons le terme générique «composant du chemin»
           pour décrire la sous-chaîne URI associée par l'analyseur à l'une de ces règles.

                path          = path-abempty    ; begins with "/" or is empty
                                / path-absolute   ; begins with "/" but not "//"
                                / path-noscheme   ; begins with a non-colon segment
                                / path-rootless   ; begins with a segment
                                / path-empty      ; zero characters

                path-abempty  = *( "/" segment )
                path-absolute = "/" [ segment-nz *( "/" segment ) ]
                path-noscheme = segment-nz-nc *( "/" segment )
                path-rootless = segment-nz *( "/" segment )
                path-empty    = 0<pchar>
                segment       = *pchar
                segment-nz    = 1*pchar
                segment-nz-nc = 1*( unreserved / pct-encoded / sub-delims / "@" )
                ; non-zero-length segment without any colon ":"

                pchar         = unreserved / pct-encoded / sub-delims / ":" / "@"

         */
        // path abempty if authority
        $regex = '/(\/pchar)*/';

        // path absolute if no authority
        $regex = '/\/(pchar+(\/pchar)*)?/';

        // path noscheme if no authority no scheme
        $regex = '/pchar-:+(\/pchar)*/';

        // path rootless if no authority and scheme
        $regex = '/pchar+(\/pchar)*/';

        // path empty if no authority and no scheme
        $regex = '//';
    }
}