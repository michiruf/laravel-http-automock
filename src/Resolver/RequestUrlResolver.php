<?php

namespace HttpAutomock\Resolver;

use GuzzleHttp\Psr7\Uri;
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
        protected bool $removeScheme = true,
        protected bool $removeUserInfo = true,
        protected bool $sanitizeUserInfo = true,
        protected bool $removeHost = false,
        protected bool $removeSubdomainFromHost = false,
        protected bool $removePort = false,
        protected bool $removePath = false,
        protected bool $removeQuery = false,
        protected bool $removeFragment = false,
        protected bool $removeSlashes = false,
        protected bool $sanitizeUrlForFileSystems = true,
        protected array $customReplace = [],
        protected array $customTransformations = [],
        protected ?string $hashMethod = null,
        protected ?int $substring = null,
    ) {
    }

    public function resolve(Request $request, bool $forWriting, string $directory): string
    {
        $uri = $request->toPsrRequest()->getUri();

        $authority = str();

        if (! $this->removeUserInfo && ! empty($uri->getUserInfo())) {
            $userInfo = $this->sanitizeUserInfo
                ? str($uri->getUserInfo())->beforeLast(':')->value()
                : $uri->getUserInfo();
            $authority = $authority->append($userInfo)->append('@');
        }

        if (! $this->removeHost && empty($uri->getHost())) {
            $authority = $authority->append($this->removeSubdomainFromHost
                ? static::removeSubdomainFromHost($uri->getHost())
                : $uri->getHost());
        }

        if (! $this->removePort && $uri->getPort()) {
            $authority = $authority->append(':')->append($uri->getPort());
        }

        $url = str(Uri::composeComponents(
            $this->removeScheme ? null : $uri->getScheme(),
            $authority?->value() ?: null,
            $this->removePath ? null : $uri->getPath(),
            $this->removeQuery ? null : $uri->getQuery(),
            $this->removeFragment ? null : $uri->getFragment(),
        ));

        if ($this->removeSlashes) {
            $url = $url->remove('/');
        }

        if ($this->sanitizeUrlForFileSystems) {
            $url = $url->replace([':', '?', '*', '"', '<', '>', '|'], '-');
        }

        foreach ($this->customReplace as $search => $replace) {
            $url = $url->replace($search, $replace);
        }

        foreach ($this->customTransformations as $callback) {
            $url = $callback($url);
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
