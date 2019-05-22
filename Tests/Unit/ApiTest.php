<?php declare(strict_types=1);

namespace Karser\PayumSaferpay\Tests\Unit;

use Http\Message\MessageFactory;
use Http\Message\MessageFactory\GuzzleMessageFactory;
use Karser\PayumSaferpay\Api;
use Karser\PayumSaferpay\Constants;
use Karser\PayumSaferpay\Exception\SaferpayHttpException;
use Payum\Core\HttpClientInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use GuzzleHttp\Psr7\Response;

class ApiTest extends TestCase
{
    private $options = [];

    public function setUp(): void
    {
        $this->options = array(
            'username' => 'test',
            'password' => 'test',
            'customerId' => 'test',
            'terminalId' => 'test',
            'sandbox' => true,
        );
    }

    /**
     * @test
     */
    public function couldBeConstructedWithHttpClientAndOptions(): void
    {
        $api = new Api($this->options, $this->createHttpClientMock(), $this->createHttpMessageFactory());

        self::assertNotNull($api);
    }

    /**
     * @test
     *
     * @expectedException \Payum\Core\Exception\LogicException
     * @expectedExceptionMessageRegExp /fields are required/
     */
    public function throwIfSandboxOptionNotSetInConstructor(): void
    {
        new Api(array(), $this->createHttpClientMock(), $this->createHttpMessageFactory());
    }

    /**
     * @test
     */
    public function shouldReturnSandboxIpnEndpointIfSandboxSetTrueInConstructor(): void
    {
        $api = new Api(
            array_merge($this->options, ['sandbox' => true]),
            $this->createHttpClientMock(),
            $this->createHttpMessageFactory()
        );
        $this->assertEquals('https://test.saferpay.com/api', $api->getApiEndpoint());
    }

    /**
     * @test
     */
    public function shouldReturnLiveIpnEndpointIfSandboxSetFalseInConstructor(): void
    {
        $api = new Api(
            array_merge($this->options, ['sandbox' => false]),
            $this->createHttpClientMock(),
            $this->createHttpMessageFactory()
        );
        $this->assertEquals('https://www.saferpay.com/api', $api->getApiEndpoint());
    }

    /**
     * @test
     */
    public function shouldComposeRequestHeader(): void
    {
        $api = new Api($this->options, $this->createSuccessHttpClientStub(), $this->createHttpMessageFactory());
        $result = $api->initTransaction([], [], []);
        $this->assertArraySubset([
            'RequestHeader' => [
                'SpecVersion' => '1.10',
                'CustomerId' => 'test',
            ],
            'TerminalId' => 'test',
        ], $result);
    }

    /**
     * @test
     */
    public function shouldComposeInitTransactionRequest(): void
    {
        $api = new Api($this->options, $this->createSuccessHttpClientStub(), $this->createHttpMessageFactory());
        $result = $api->initTransaction([
            'Amount' => [
                'value' => 123,
                'CurrencyCode' => 'USD'
            ],
        ], [
            'Success' => 'successUrl',
            'Fail' => 'failUrl',
        ], [
            'Alias' => ['Id' => 'aliasId'],
        ]);

        $this->assertArraySubset([
            'Payment' => [
                'Amount' => [
                    'value' => 123,
                    'CurrencyCode' => 'USD',
                ],
            ],
            'Payer' => [
                'LanguageCode' => 'en',
            ],
            'ReturnUrls' => [
                'Success' => 'successUrl',
                'Fail' => 'failUrl',
            ],
            'PaymentMeans' => [
                'Alias' => [
                    'Id' => 'aliasId',
                ],
            ],
        ], $result);
    }

    /**
     * @test
     */
    public function shouldComposeAuthorizeTransactionRequest(): void
    {
        $api = new Api($this->options, $this->createSuccessHttpClientStub(), $this->createHttpMessageFactory());
        $result = $api->authorizeTransaction('this-is-token', Constants::LS_WITH_LIABILITY_SHIFT, [
            'Alias' => ['Id' => 'aliasId'],
        ]);

        $this->assertArraySubset([
            'Token' => 'this-is-token',
            'Condition' => 'WITH_LIABILITY_SHIFT',
            'RegisterAlias' => [
                'IdGenerator' => 'RANDOM',
                'Alias' => [
                    'Id' => 'aliasId',
                ],
            ],
        ], $result);
    }

    /**
     * @test
     */
    public function shouldComposeCaptureTransactionRequest(): void
    {
        $api = new Api($this->options, $this->createSuccessHttpClientStub(), $this->createHttpMessageFactory());
        $result = $api->captureTransaction('transaction-id');

        $this->assertArraySubset([
            'TransactionReference' => [
                'TransactionId' => 'transaction-id',
            ],
        ], $result);
    }

    /**
     * @test
     */
    public function shouldComposeRefundTransactionRequest(): void
    {
        $api = new Api($this->options, $this->createSuccessHttpClientStub(), $this->createHttpMessageFactory());
        $result = $api->refundTransaction([
            'Amount' => [
                'value' => 123,
                'CurrencyCode' => 'USD'
            ],
        ], 'transaction-id');

        $this->assertArraySubset([
            'Refund' => [
                'Amount' => [
                    'value' => 123,
                    'CurrencyCode' => 'USD',
                ],
            ],
            'CaptureReference' => [
                'CaptureId' => 'transaction-id',
            ],
        ], $result);
    }

    /**
     * @test
     */
    public function shouldComposeAuthorizeReferencedTransactionRequest(): void
    {
        $api = new Api($this->options, $this->createSuccessHttpClientStub(), $this->createHttpMessageFactory());
        $result = $api->authorizeReferencedTransaction([
            'Amount' => [
                'value' => 123,
                'CurrencyCode' => 'USD'
            ],
        ], 'transaction-id');

        $this->assertArraySubset([
            'Payment' => [
                'Amount' => [
                    'value' => 123,
                    'CurrencyCode' => 'USD',
                ],
            ],
            'TransactionReference' => [
                'TransactionId' => 'transaction-id',
            ],
        ], $result);
    }

    /**
     * @test
     */
    public function shouldComposeInitPaymentPageRequest(): void
    {
        $api = new Api($this->options, $this->createSuccessHttpClientStub(), $this->createHttpMessageFactory());
        $result = $api->initPaymentPage([
            'Amount' => [
                'value' => 123,
                'CurrencyCode' => 'USD'
            ],
        ], [
            'Success' => 'successUrl',
            'Fail' => 'failUrl',
        ], [
            'NotifyUrl' => 'notifyUrl',
        ]);

        $this->assertArraySubset([
            'Payment' => [
                'Amount' => [
                    'value' => 123,
                    'CurrencyCode' => 'USD',
                ],
            ],
            'Payer' => [
                'LanguageCode' => 'en',
            ],
            'ReturnUrls' => [
                'Success' => 'successUrl',
                'Fail' => 'failUrl',
            ],
            'Notification' => [
                'NotifyUrl' => 'notifyUrl',
            ],
        ], $result);
    }

    /**
     * @test
     */
    public function shouldComposeAssertPaymentPageRequest(): void
    {
        $api = new Api($this->options, $this->createSuccessHttpClientStub(), $this->createHttpMessageFactory());
        $result = $api->assertPaymentPage('token');

        $this->assertArraySubset([
            'Token' => 'token',
        ], $result);
    }

    /**
     * @test
     */
    public function shouldComposeInsertAliasRequest(): void
    {
        $api = new Api($this->options, $this->createSuccessHttpClientStub(), $this->createHttpMessageFactory());
        $result = $api->insertAlias([
            'Success' => 'successUrl',
            'Fail' => 'failUrl',
        ], [
            'IdGenerator' => 'RANDOM',
        ], 'CARD');

        $this->assertArraySubset([
            'RegisterAlias' => [
                'IdGenerator' => 'RANDOM',
            ],
            'Type' => 'CARD',
            'ReturnUrls' => [
                'Success' => 'successUrl',
                'Fail' => 'failUrl',
            ],
            'LanguageCode' => 'en',
        ], $result);
    }

    /**
     * @test
     */
    public function shouldComposeAssertInsertAliasRequest(): void
    {
        $api = new Api($this->options, $this->createSuccessHttpClientStub(), $this->createHttpMessageFactory());
        $result = $api->assertInsertAlias('token');

        $this->assertArraySubset([
            'Token' => 'token',
        ], $result);
    }

    /**
     * @test
     */
    public function shouldComposeDeleteAliasRequest(): void
    {
        $api = new Api($this->options, $this->createSuccessHttpClientStub(), $this->createHttpMessageFactory());
        $result = $api->deleteAlias('alias-id');

        $this->assertArraySubset([
            'AliasId' => 'alias-id',
        ], $result);
    }

    /**
     * @test
     *
     * @expectedException \Karser\PayumSaferpay\Exception\SaferpayHttpException
     * @expectedExceptionMessage Condition 'WITH_LIABILITY_SHIFT' not satisfied
     */
    public function throwIfResponseStatusNotOk(): void
    {
        $clientMock = $this->createHttpClientMock();
        $clientMock
            ->expects($this->once())
            ->method('send')
            ->willReturnCallback(static function () {
                $body = <<<EOF
{"ResponseHeader":{"SpecVersion":"1.10","RequestId":"5cd2b290c5f39"},"Behavior":"ABORT","ErrorName":"CONDITION_NOT_SATISFIED","ErrorMessage":"Condition 'WITH_LIABILITY_SHIFT' not satisfied"}
EOF;
                return new Response(402, [], $body);
            })
        ;
        $api = new Api($this->options, $clientMock, $this->createHttpMessageFactory());
        try {
            $api->captureTransaction('test-id');
        } catch (SaferpayHttpException $e) {
            self::assertSame([
                'ResponseHeader' => [
                    'SpecVersion' => '1.10',
                    'RequestId' => '5cd2b290c5f39',
                ],
                'Behavior' => 'ABORT',
                'ErrorName' => 'CONDITION_NOT_SATISFIED',
                'ErrorMessage' => 'Condition \'WITH_LIABILITY_SHIFT\' not satisfied',
            ], $e->getData());
            throw $e;
        }
    }

    /**
     * @return MockObject|HttpClientInterface
     */
    protected function createSuccessHttpClientStub()
    {
        $clientMock = $this->createHttpClientMock();
        $clientMock
            ->method('send')
            ->willReturnCallback(function (RequestInterface $request) {
                return new Response(200, [], $request->getBody());
            })
        ;
        return $clientMock;
    }

    /**
     * @return MockObject|HttpClientInterface
     */
    protected function createHttpClientMock()
    {
        return $this->getMockBuilder(HttpClientInterface::class)->getMock();
    }

    protected function createHttpMessageFactory(): MessageFactory
    {
        return new GuzzleMessageFactory();
    }
}
