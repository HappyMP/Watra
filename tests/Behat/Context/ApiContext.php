<?php

declare(strict_types=1);

namespace App\Tests\Behat\Context;

use Behat\MinkExtension\Context\RawMinkContext;
use Webmozart\Assert\Assert;

final class ApiContext extends RawMinkContext
{
    /** @var array<mixed>|null */
    private ?array $jsonResponse = null;

    /**
     * @When I send a GET request to :url
     */
    public function iSendAGetRequestTo(string $url): void
    {
        $this->getSession()->visit($url);
        $content = $this->getSession()->getPage()->getContent();
        $decoded = json_decode($content, true);
        Assert::isArray($decoded, sprintf('Response is not valid JSON. Got: %s', substr($content, 0, 200)));
        $this->jsonResponse = $decoded;
    }

    /**
     * @Then the JSON response has key :key
     */
    public function theJsonResponseHasKey(string $key): void
    {
        Assert::isArray($this->jsonResponse, 'No JSON response captured. Call "I send a GET request to" first.');
        Assert::keyExists(
            $this->jsonResponse,
            $key,
            sprintf('JSON response missing key "%s". Got keys: %s', $key, implode(', ', array_keys($this->jsonResponse))),
        );
    }

    /**
     * @Then the JSON response :key is at least :min
     */
    public function theJsonResponseKeyIsAtLeast(string $key, int $min): void
    {
        Assert::isArray($this->jsonResponse, 'No JSON response captured.');
        Assert::keyExists($this->jsonResponse, $key);
        Assert::greaterThanEq(
            $this->jsonResponse[$key],
            $min,
            sprintf('Expected "%s" >= %d, got %d', $key, $min, $this->jsonResponse[$key]),
        );
    }
}
