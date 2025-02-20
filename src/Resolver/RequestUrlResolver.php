<?php

namespace HttpAutomock\Resolver;

use GuzzleHttp\Psr7\Uri;
use HttpAutomock\Helper\FileNameSanitizer;
use Illuminate\Http\Client\Request;

/**
 * Generates a filename from a request's URL by selectively including/excluding URL components.
 *
 * This resolver creates unique filenames based on request URLs, which can be used to store
 * and retrieve mock responses. The class provides granular control over which URL components
 * (scheme, host, path, etc.) should be included in the generated filename.
 *
 * Features:
 * - Selective URL component inclusion/exclusion
 * - Optional subdomain removal
 * - URL sanitization for filesystem compatibility
 * - Custom string replacements
 * - Optional URL hashing with configurable algorithm
 * - Configurable output length via substring
 *
 * Example usage:
 * A request to "https://api.example.com/users?id=123" might generate:
 * - "api-example-com-users-id-123" (with sanitization)
 * - "example-com-users-id-123" (with subdomain removal)
 * - "a7d8e9f..." (with hashing enabled)
 */
class RequestUrlResolver implements FileNameResolverInterface
{
    /**
     * @param  array<string, string>  $customReplace
     * @param  array<callable(string): string>  $customTransformations
     */
    public function __construct(
        protected bool $scheme = false,
        protected bool $userInfo = false,
        protected bool $host = true,
        protected bool $port = true,
        protected bool $path = true,
        protected bool $query = true,
        protected bool $fragment = true,
        protected bool $sanitizeUserInfo = true,
        protected bool $removeSubdomainFromHost = false,
        protected bool $removeSlashes = false,
        protected bool $sanitizeForFileSystems = true,
        protected array $customReplace = [],
        protected array $customTransformations = [],
        protected ?string $hashMethod = null,
        protected ?int $substring = null,
    ) {
    }

    public function resolve(Request $request, bool $forWriting, string $directory): string
    {
        $uri = $request->toPsrRequest()->getUri();

        $authority = str('');

        if ($this->userInfo && ! empty($uri->getUserInfo())) {
            $userInfo = $this->sanitizeUserInfo
                ? str($uri->getUserInfo())->beforeLast(':')->value()
                : $uri->getUserInfo();
            $authority = $authority->append($userInfo)->append('@');
        }

        if ($this->host && ! empty($uri->getHost())) {
            $authority = $authority->append($this->removeSubdomainFromHost
                ? static::removeSubdomainFromHost($uri->getHost())
                : $uri->getHost());
        }

        // Since the default port may be returned by getPort(), we omit this
        if ($this->port && $uri->getPort() !== null && $uri->getPort() !== 80) {
            $authority = $authority->append(':')->append($uri->getPort());
        }

        $url = str(Uri::composeComponents(
            $this->scheme ? $uri->getScheme() : null,
            $authority?->value() ?: null,
            $this->path ? $uri->getPath() : null,
            $this->query ? $uri->getQuery() : null,
            $this->fragment ? $uri->getFragment() : null,
        ));

        if ($this->removeSlashes) {
            $url = $url->remove('/');
        }

        foreach ($this->customReplace as $search => $replace) {
            $url = $url->replace($search, $replace);
        }

        foreach ($this->customTransformations as $callback) {
            $url = str($callback($url));
        }

        if ($this->sanitizeForFileSystems) {
            $url = FileNameSanitizer::sanitize($url);
        }

        if ($this->hashMethod) {
            $url = str(hash($this->hashMethod, $url->value()));
        }

        if ($this->substring) {
            $url = $url->substr(0, $this->substring);
        }

        return $url->value();
    }

    protected static function removeSubdomainFromHost(string $host): string
    {
        $host = str($host);

        while ($host->substrCount('.') > 1) {
            $host = $host->after('.');
        }

        return $host;
    }
}
