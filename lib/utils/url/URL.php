<?php
include_once("utils/url/URLParameter.php");
include_once("utils/url/DataParameter.php");
include_once("utils/url/PathParameter.php");

include_once("utils/Paginator.php");
include_once("utils/IGETConsumer.php");


class URL implements IGETConsumer, IDataResultProcessor, ISerializable
{

    protected bool $is_script = FALSE;

    //keep the original script_name using placeholders intact ie value is javascript:item(%key1%)
    protected string $script_name = "";
    //setData applied on script_name fills this variable ie value is javascript:item(123)
    protected string $script_name_data = "";

    protected string $domain = "";
    protected string $protocol = "";

    /**
     * @var array<string, URLParameter>  All url parameters name/value pairs
     */
    protected array $parameters = array();

    protected bool $clear_page_param = FALSE;
    protected array $clear_params = array();

    /**
     * Construct URL with value current request URL
     * Uses currentURL() internally
     * @return URL
     */
    public static function Current() : URL
    {
        $ret = $_SERVER["SCRIPT_NAME"];
        if ($_SERVER["QUERY_STRING"]) {
            $ret .= "?" . $_SERVER["QUERY_STRING"];
        }
        return new URL($ret);
    }

    public function __construct(string $url="")
    {
        if (strlen($url)>0) {
            $this->fromString($url);
        }
        else {
            $this->reset();
        }
    }

    public function reset() : void
    {
        //URL = > http://domain.com/some/other/script.php?param1=1&param2=2#atresource
        //protocol - http; domain - domain.com; script_name /some/other/script.php
        //parameters(param1=>1, param2=>2), resource = atresource
        //
        $this->script_name = "";

        $this->parameters = array();

        $this->clear_params = array();
        $this->clear_page_param = false;
        $this->is_script = false;

        $this->domain = "";
        $this->protocol = "";

    }

    /**
     * Remove Paginator defined parameters during toString() conversion
     * @param bool $mode
     * @return void
     */
    public function setClearPageParams(bool $mode) : void
    {
        $this->clear_page_param = $mode;
    }

    public function setClearParams(...$params) : void
    {
        $this->clear_params = $params;
    }

    /**
     * Add/Set query parameter
     * @param URLParameter $param
     */
    public function add(URLParameter $param) : void
    {
        $this->parameters[$param->name()] = $param;
    }

    /**
     * Remove the parameter from this url builder
     * @param string $name Name of parameter to remove
     */
    public function remove(string $name) : void
    {
        if (!$this->contains($name)) return;
        unset($this->parameters[$name]);
    }


    /**
     * Get URLParameter with name $name
     * @param string $name
     * @return URLParameter|null
     */
    public function get(string $name): ?URLParameter
    {
        if (isset($this->parameters[$name])) return $this->parameters[$name];
        return null;
    }

    /**
     * Check if this url contains query parameter named $name
     * @param string $name The query parameter to check for
     * @return bool True if this url string have parameter with name $name
     */
    public function contains(string $name): bool
    {
        return array_key_exists($name, $this->parameters);
    }

    /**
     * Return all query parameter names
     * @return array
     */
    public function getParameterNames(): array
    {
        return array_keys($this->parameters);
    }

    /**
     * Returns a filtered copy of parameters respecting clear_page_param and clear_params.
     * Used internally by toString() and getQueryString() to ensure consistent behavior.
     */
    protected function getFilteredParameters(): array
    {
        $filtered = $this->parameters;

        // Remove Paginator parameters if requested
        if ($this->clear_page_param) {
            foreach (Paginator::Instance()->getParameterNames() as $name) {
                unset($filtered[$name]);
            }
        }

        // Remove explicitly cleared parameters
        foreach ($this->clear_params as $key) {
            unset($filtered[$key]);
        }

        return $filtered;
    }

    /**
     * Returns the query string portion only (without the leading '?').
     * Respects clear_page_param and clear_params for consistency with toString().
     * Only includes regular parameters that have non-empty values.
     *
     * @return string Empty string if no query parameters remain after filtering
     */
    public function getQueryString(): string
    {
        $queryPairs = [];

        foreach ($this->getFilteredParameters() as $param) {
            // Skip PathParameter and Resource parameters
            if ($param instanceof PathParameter || $param->isResource()) {
                continue;
            }

            $value = $param->value(false);   // never quoted

            if ($value !== '') {
                $queryPairs[$param->name()] = $value;
            }
        }

        if (empty($queryPairs)) {
            return '';
        }

        return http_build_query(
            $queryPairs,
            '',
            '&',
            PHP_QUERY_RFC3986
        );
    }

    public function toString(): string
    {

        if ($this->is_script) {
            return $this->script_name_data ?: $this->script_name;
        }

        // Get filtered parameters once (respects clear_page_param and clear_params)
        $filteredParams = $this->getFilteredParameters();

        // Build path segments and fragment
        $pathParts = [];
        $fragment  = '';

        foreach ($filteredParams as $param) {
            $name  = $param->name();
            $value = $param->value(false);

            if ($param instanceof PathParameter) {
                $encoded = rawurlencode($value);
                $pathParts[] = $encoded;
                if ($param->isAppendPathSeparator()) {
                    $pathParts[] = '/';
                }
            } elseif ($param->isResource()) {
                $fragmentValue = ($value === '') ? $name : $value;
                $fragment = '#' . rawurlencode(ltrim($fragmentValue, '#'));
            }
        }

        // Assemble the final URL
        $result = rtrim($this->script_name, '/');

        if (!empty($pathParts)) {
            $result .= '/' . implode('', $pathParts);
        }

        // Use the shared getQueryString() method
        $query = $this->getQueryString();
        if ($query !== '') {
            $result .= '?' . $query;
        }

        if ($fragment !== '') {
            $result .= $fragment;
        }



        if ($this->isAbsolute()) {

            //normalize path
            if ($result === '' || !str_starts_with($result, '/')) {
                $result = '/' . ltrim($result, '/');
            }

            $authority = $this->protocol . '://' . $this->domain;
            if (str_starts_with($result, $authority)) {
                return $result;
            }
            return $authority . ($result[0] === '/' ? '' : '/') . $result;
        }

        return $result;
    }

    public function __toString() : string
    {
        return $this->toString();
    }

    /**
     * Returns a new URL instance representing the full absolute URL.
     *
     * If the original input contained a scheme and host, those are preserved.
     * Otherwise, the site's configured protocol and domain (from Spark/Config) are usws.
     *
     * @return URL
     */
    public function fullURL(): URL
    {

        $result = new URL($this->toString());

        if (!$this->isAbsolute()) {
            // Case 2: Relative URL → use site configuration
            $proto = Spark::Get(Config::SITE_PROTOCOL, 'https://');  // default fallback
            $domain = Spark::Get(Config::SITE_DOMAIN, 'localhost');

            $domain = trim($domain, ' :/');

            // Normalize protocol (ensure it ends with ://)
            if (!str_ends_with($proto, '://')) {
                $proto = rtrim($proto, '/') . '://';
            }

            $result->setProtocol($proto);
            $result->setDomain($domain);
        }

        return $result;
    }

    /**
     * Returns the protocol part of the URL (e.g. "https", "http").
     * Returns empty string if the URL is relative or protocol was not present.
     */
    public function getProtocol(): string
    {
        return $this->protocol;
    }

    /**
     * Returns the domain/host part of the URL (e.g. "example.com", "cdn.jsdelivr.net").
     * May include port if present (e.g. "localhost:8080").
     * Returns empty string for relative URLs.
     */
    public function getDomain(): string
    {
        return $this->domain;
    }

    /**
     * Sets the protocol (e.g. "https", "http").
     * Does NOT include the "://" part.
     */
    public function setProtocol(string $protocol): void
    {
        $this->protocol = trim($protocol, ':/');
    }

    /**
     * Sets the domain/host part (e.g. "example.com", "sub.domain:8080").
     */
    public function setDomain(string $domain): void
    {
        $this->domain = trim($domain);
    }

    /**
     * Returns the full authority part (scheme://host[:port]).
     * Returns empty string for relative URLs.
     */
    public function getAuthority(): string
    {
        if ($this->protocol === '' && $this->domain === '') {
            return '';
        }

        $authority = $this->protocol;
        if ($authority !== '') {
            $authority .= '://';
        }

        $authority .= $this->domain;

        return $authority;
    }

    /**
     * Returns true if this URL contains scheme and authority (host) part.
     * Treats protocol-relative URLs (//example.com/...) as absolute.
     */
    public function isAbsolute(): bool
    {
        return $this->protocol !== '' || $this->domain !== '';
    }

    /**
     * Returns true if this URL has no scheme and no host (path-relative or root-relative).
     */
    public function isRelative(): bool
    {
        return !$this->isAbsolute();
    }

    /**
     * Returns the path portion only (script_name), normalized with leading slash.
     * For absolute URLs this is the path after the authority.
     * For relative URLs this is the main content.
     */
    public function getPath(): string
    {
        $path = $this->script_name ?: '/';

        if (!str_starts_with($path, '/')) {
            $path = '/' . $path;
        }

        return $path;
    }

    public function getScriptName() : string
    {
        return $this->script_name;
    }

    public function setScriptName(string $value) : void
    {
        $this->script_name = $value;
    }

    public function getScriptPath() : string
    {
        return dirname($this->script_name);
    }

    /**
     * Returns the fragment portion only (without the leading '#').
     */
    public function getFragment(): string
    {
        foreach ($this->parameters as $param) {
            if ($param->isResource()) {
                $value = $param->value();
                return ($value === '') ? ltrim($param->name(), '#') : ltrim($value, '#');
            }
        }
        return '';
    }

    /**
     * Parses a URL string and populates the internal state of this URL object.
     *
     * Supports:
     * - Relative paths: "/products/view/123" or "edit.php?id=5"
     * - Absolute URLs: "https://example.com/path"
     * - Protocol-relative URLs: "//cdn.jsdelivr.net/npm/tinymce@latest/tinymce.min.js"
     * - JavaScript pseudo-URLs: "javascript:..."
     * - URLs with query strings and fragments
     */
    public function fromString(string $build_string): void
    {
        $this->reset();
        $build_string = trim($build_string);

        if (strlen($build_string) < 1) {
            return;
        }

        // Handle JavaScript pseudo-URLs
        if (str_starts_with($build_string, 'javascript:')) {
            $this->is_script = true;
            $this->script_name = $build_string;
            return;
        }

        // Parse the URL using PHP's native parser
        $parsed = parse_url($build_string);
        if ($parsed === false) {
            throw new \InvalidArgumentException("Cannot parse URL: " . $build_string);
        }

        // === Protocol & Domain / Authority Handling ===

        // Standard absolute URL with scheme (https://, http://, etc.)
        if (isset($parsed['scheme'])) {
            $this->protocol = $parsed['scheme'];
        }

        // Protocol-relative URL: "//cdn.example.com/path"
        // parse_url() puts the host in 'path' in this case, so we need special handling
        if ($this->protocol === '' && str_starts_with($build_string, '//')) {
            $this->domain = $parsed['host'] ?? '';
            if (isset($parsed['port'])) {
                $this->domain .= ':' . $parsed['port'];
            }
            // For protocol-relative, the path starts after the host
            $this->script_name = $parsed['path'] ?? '/';
        }
        // Normal absolute or relative URL
        else {
            $this->domain = $parsed['host'] ?? '';

            if (isset($parsed['port'])) {
                $this->domain .= ':' . $parsed['port'];
            }

            // Path becomes script_name
            $this->script_name = $parsed['path'] ?? '/';
        }

        // === Query Parameters ===
        if (isset($parsed['query']) && $parsed['query'] !== '') {
            parse_str($parsed['query'], $params);
            foreach ($params as $k => $v) {
                $this->add(new URLParameter($k, (string)$v));
            }
        }

        // === Fragment / Resource ===
        if (isset($parsed['fragment']) && $parsed['fragment'] !== '') {
            $this->add(new URLParameter('#' . $parsed['fragment']));
        }
    }

    /**
     * @param array $data Parametrise this URL parameter values using $data associative array as source.
     * JavaScript code is replaced using %parameter_name% as a match.
     * $data[$parameter_name] value is used as a replacement.
     *
     */
    public function setData(array $data) : void
    {
        //
        if ($this->is_script) {
            if (str_contains($this->script_name, "%")) {
                $this->script_name_data = "";
                $from = $this->script_name;
                $names = array_keys($data);
                foreach ($names as $idx => $name) {
                    $replace = array("%" . $name . "%" => $data[$name]);
                    $from = strtr($from, $replace);
                }
                $this->script_name_data = $from;
            }
            return;
        }

        $names = array_keys($this->parameters);
        foreach ($names as $name) {
            $param = $this->get($name);
            $param->setData($data);
        }

    }

    public function copyParametersTo(URL $url, bool $overwrite = true) : void
    {
        $parameters = $this->getParameterNames();
        foreach ($parameters as $idx=>$name) {
            if ($url->contains($name) && !$overwrite) continue;
            $url->add($this->get($name));
        }

    }

    public function copyParametersFrom(URL $url, bool $overwrite = true) : void
    {
        $parameters = $url->getParameterNames();
        foreach ($parameters as $idx=>$name) {
            if ($this->contains($name) && !$overwrite) continue;
            $this->add($url->get($name));
        }

    }
    public function clearParameters() : void
    {
        $this->parameters = array();
    }

    /**
     * Remove all parameters from '$pageURL' not present in '$supported_params' array
     * @param URL $pageURL The URL object to clean parameters
     * @param array $supported_params array containing the names of the parameters that should be kept
     * @return void
     */
    public static function Clean(URL $pageURL, array $supported_params) : void
    {
        //static url parameter names from the current page
        $page_params = $pageURL->getParameterNames();
        //cleanup non supported names
        foreach ($page_params as $idx=>$name) {
            $param = $pageURL->get($name);
            if (!in_array($name, $supported_params) || !$param->value()) {
                $pageURL->remove($name);
            }
        }
    }

    public function __serialize(): array
    {
        return get_object_vars($this);
    }

}