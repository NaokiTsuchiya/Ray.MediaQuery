<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Ray\Di\Di\Named;
use Ray\MediaQuery\Exception\WebApiRequestException;

use function json_decode;
use function uri_template;

final class WebApiQuery implements WebApiQueryInterface
{
    /** @var ClientInterface */
    private $client;

    /** @var MediaQueryLoggerInterface  */
    private $logger;

    /** @var array<string, string>  */
    private $domainBindings;

    /**
     * @param array<string, string> $domainBindings
     *
     * @Named("'domainBindings=web_api_query_domain")
     */
    #[Named('domainBindings=web_api_query_domain')]
    public function __construct(ClientInterface $client, MediaQueryLoggerInterface $logger, array $domainBindings)
    {
        $this->client = $client;
        $this->logger = $logger;
        $this->domainBindings = $domainBindings;
    }

    /**
     * {@inheritDoc}
     */
    public function request(string $method, string $uri, array $query): array
    {
        try {
            $this->logger->start();
            $boundUri = uri_template($uri, $this->domainBindings + $query);
            $response = $this->client->request($method, $boundUri, $query);
            $json = $response->getBody()->getContents();
            /** @var array<string, mixed> $body */
            $body = json_decode($json, true);
            $this->logger->log($boundUri, $query);

            return $body;
        } catch (GuzzleException $e) {
            throw new WebApiRequestException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }
}