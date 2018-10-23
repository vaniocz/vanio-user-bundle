<?php
namespace Vanio\UserBundle\Security;

use Vanio\Stdlib\Strings;
use Vanio\Stdlib\Uri;

class ApiClientTrustResolver
{
    /** @var string[] */
    private $trustedApiClientUrls;

    /**
     * @param string[] $trustedApiClientUrls
     */
    public function __construct(array $trustedApiClientUrls)
    {
        $this->trustedApiClientUrls = $trustedApiClientUrls;
    }

    public function isTrustedApiClientUrl(string $url): bool
    {
        $url = new Uri($url);

        foreach ($this->trustedApiClientUrls as $trustedApiUrl) {
            $trustedApiUrl = Strings::contains($trustedApiUrl, '//')
                ? $trustedApiUrl
                : sprintf('//%s', $trustedApiUrl);
            $trustedApiUrl = new Uri($trustedApiUrl);

            if (
                $trustedApiUrl->scheme() && $trustedApiUrl->scheme() !== $url->scheme()
                || $url->host() !== $trustedApiUrl->host()
            ) {
                continue;
            }

            $trustedApiUrlPath = rtrim($trustedApiUrl->path(), '/');
            $urlPath = rtrim($url->path(), '/');

            if ($urlPath === $trustedApiUrlPath || Strings::startsWith($urlPath, $trustedApiUrlPath . '/')) {
                return true;
            }
        }

        return false;
    }
}
