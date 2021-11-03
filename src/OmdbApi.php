<?php
namespace App;

use InvalidArgumentException;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * token: 28c5b7b1 | host: https://www.omdbapi.com
 * BBA client class consuming http://www.omdbapi.com/ API. Several methods are availables.
 */
final class OmdbApi
{
    /** @var string Api key for OMDb api. */
    private $token;

    /** @var string OMDb host. */
    private $host;

    /** @var array Optional parameters to complete a request. */
    private const OPTIONAL_PARAMS = [
        'type',
        'y',
        'plot',
        'r',
        'page',
        'callback',
        'v',
    ];

    /** @var HttpClientInterface */
    private $httpClient;

    public function __construct(HttpClientInterface $omdbClient, string $omdbToken, string $omdbHost)
    {
        $this->token = $omdbToken;
        $this->host = $omdbHost;
        $this->httpClient = $omdbClient;
    }

    public function requestOneById($mediaId, array $options = []): array
    {
        $options = array_merge([$mediaId], $options);

        return $this->requestBy(
            $this->validQueryParameters('i', $options)
        );
    }

    public function requestOneByTitle($mediaTitle, array $options = []): array
    {
        $options = array_merge([$mediaTitle], $options);

        return $this->requestBy(
            $this->validQueryParameters('t', $options)
        );
    }

    public function requestAllBySearch($mediaTitle, array $options = []): array
    {
        $options = array_merge([$mediaTitle], $options);

        return $this->requestBy(
            $this->validQueryParameters('s', $options)
        );
    }

    /**
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    private function requestBy(array $queryParameters): array
    {
        // Making the request with HTTPClient
        $response = $this->httpClient
            ->request('GET', $this->host, ['query' => $queryParameters])
            ->toArray()
        ;

        // Normally, you should throw a ClientException with the response object
        // But the OMDd Api does not return an error code when you do a bad request
        // It returns an array with ['Response' => false, 'Error' => '...']
        if (\in_array('False', $response, true)) {
            throw new TransportException($response['Response'].' : '.$response['Error']);
        }

        return $response;
    }

    private function validQueryParameters(string $requestParameter, array $arguments): array
    {
        // If no optional parameters were passed, returns an api token and a required query string parameter
        if (!\array_key_exists(1, $arguments)) {
            return ['apikey' => $this->token, $requestParameter => $arguments[0]];
        }

        // Check if all optional parameters are valid
        foreach ($arguments[1] as $key => $value) {
            if (!\in_array($key, self::OPTIONAL_PARAMS, true)) {
                throw new InvalidArgumentException('Invalid query string parameters were passed to the request.');
            }
        }

        // Return a complete 'query' array for HTTPClient
        // @see Symfony\Component\HttpClient\HttpClientTrait
        return array_merge(['apikey' => $this->token, $requestParameter => $arguments[0]], $arguments[1]);
    }
}
