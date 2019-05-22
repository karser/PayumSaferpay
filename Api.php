<?php declare(strict_types=1);

namespace Karser\PayumSaferpay;

use Http\Message\MessageFactory;
use Karser\PayumSaferpay\Exception\SaferpayHttpException;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\InvalidArgumentException;
use Payum\Core\HttpClientInterface;

class Api
{
    const SPEC_VERSION = '1.10';
    const PAYMENT_PAGE_INIT_PATH = '/Payment/v1/PaymentPage/Initialize';
    const PAYMENT_PAGE_ASSERT_PATH = '/Payment/v1/PaymentPage/Assert';
    const TRANSACTION_INIT_PATH = '/Payment/v1/Transaction/Initialize';
    const TRANSACTION_AUTHORIZE_PATH = '/Payment/v1/Transaction/Authorize';
    const TRANSACTION_AUTHORIZE_REFERENCED_PATH = '/Payment/v1/Transaction/AuthorizeReferenced';
    const TRANSACTION_CAPTURE_PATH = '/Payment/v1/Transaction/Capture';
    const TRANSACTION_REFUND_PATH = '/Payment/v1/Transaction/Refund';
    const ALIAS_INSERT_PATH = '/Payment/v1/Alias/Insert';
    const ALIAS_ASSERT_INSERT_PATH = '/Payment/v1/Alias/AssertInsert';
    const ALIAS_DELETE_PATH = '/Payment/v1/Alias/Delete';

    /**
     * @var HttpClientInterface
     */
    protected $client;

    /**
     * @var MessageFactory
     */
    protected $messageFactory;

    /**
     * @var array
     */
    protected $options = array(
        'username' => null,
        'password' => null,
        'customerId' => null,
        'terminalId' => null,
        'sandbox' => null,
        'iframeCssUrl' => null,
    );

    /**
     * @param array               $options
     * @param HttpClientInterface $client
     * @param MessageFactory      $messageFactory
     */
    public function __construct(array $options, HttpClientInterface $client, MessageFactory $messageFactory)
    {
        $options = ArrayObject::ensureArrayObject($options);
        $options->defaults($this->options);
        $options->validateNotEmpty([
            'username', 'password', 'customerId', 'terminalId',
        ]);
        if (!is_bool($options['sandbox'])) {
            throw new InvalidArgumentException('The boolean sandbox option must be set.');
        }

        $this->options = $options;
        $this->client = $client;
        $this->messageFactory = $messageFactory;
    }

    /**
     * @param array $fields
     *
     * @return array
     */
    protected function doRequest($path, array $fields)
    {
        $headers = [
            'Authorization' => 'Basic ' . base64_encode($this->options['username'] . ':' . $this->options['password']),
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
        $fields = array_merge([
            'RequestHeader' => [
                'SpecVersion' => self::SPEC_VERSION,
                'CustomerId' => $this->options['customerId'],
                'RequestId' => uniqid(),
                'RetryIndicator' => 0,
            ],
        ], $fields);

        $request = $this->messageFactory->createRequest(
            'POST',
            $this->getApiEndpoint() . $path,
            $headers,
            json_encode($fields)
        );

        $response = $this->client->send($request);

        if (!($response->getStatusCode() >= 200 && $response->getStatusCode() < 300)) {
            throw SaferpayHttpException::factory($request, $response);
        }

        return $this->parseResponse(
            $response->getBody()->getContents()
        );
    }

    private function parseResponse($content)
    {
        return json_decode($content, true);
    }

    public function initTransaction(array $payment, array $returnUrls, ?array $paymentMeans): array
    {
        $payload = [
            'TerminalId' => $this->options['terminalId'],
            'Payment' => $payment,
            'Payer' => [
                'LanguageCode' => 'en',
            ],
            'ReturnUrls' => $returnUrls,
        ];
        if (null !== $this->options['iframeCssUrl']) {
            $payload['Styling'] = [
                'CssUrl' => $this->options['iframeCssUrl'],
            ];
        }
        if (null !== $paymentMeans) {
            $payload['PaymentMeans'] = $paymentMeans;
        }
        return $this->doRequest(self::TRANSACTION_INIT_PATH, $payload);
    }

    public function initPaymentPage(array $payment, array $returnUrls, ?array $notification): array
    {
        $payload = [
            'TerminalId' => $this->options['terminalId'],
            'Payment' => $payment,
            'Payer' => [
                'LanguageCode' => 'en',
            ],
            'ReturnUrls' => $returnUrls,
        ];
        if (null !== $this->options['iframeCssUrl']) {
            $payload['Styling'] = [
                'CssUrl' => $this->options['iframeCssUrl'],
            ];
        }
        if (null !== $notification) {
            $payload['Notification'] = $notification;
        }
        return $this->doRequest(self::PAYMENT_PAGE_INIT_PATH, $payload);
    }

    public function authorizeTransaction(string $token, ?string $condition = null, ?array $alias = null): array
    {
        $payload = [
            'Token' => $token,
        ];
        if (null !== $condition) {
            $payload['Condition'] = $condition;
        }
        if (null !== $alias) {
            $payload['RegisterAlias'] = array_merge(['IdGenerator' => Constants::ALIAS_ID_GENERATOR_RANDOM], $alias);
        }
        return $this->doRequest(self::TRANSACTION_AUTHORIZE_PATH, $payload);
    }

    public function authorizeReferencedTransaction(array $payment, string $transactionReferenceId): array
    {
        $payload = [
            'TerminalId' => $this->options['terminalId'],
            'Payment' => $payment,
            'TransactionReference' => ['TransactionId' => $transactionReferenceId],
        ];
        return $this->doRequest(self::TRANSACTION_AUTHORIZE_REFERENCED_PATH, $payload);
    }

    public function captureTransaction(string $transactionId): array
    {
        $payload = [
            'TransactionReference' => [
                'TransactionId' => $transactionId,
            ],
        ];
        return $this->doRequest(self::TRANSACTION_CAPTURE_PATH, $payload);
    }

    public function refundTransaction(array $refund, string $captureId): array
    {
        $payload = [
            'Refund' => $refund,
            'CaptureReference' => [
                'CaptureId' => $captureId,
            ],
        ];
        return $this->doRequest(self::TRANSACTION_REFUND_PATH, $payload);
    }

    public function assertPaymentPage(string $token): array
    {
        $payload = [
            'Token' => $token,
        ];
        return $this->doRequest(self::PAYMENT_PAGE_ASSERT_PATH, $payload);
    }

    public function insertAlias(array $returnUrls, array $alias, string $type): array
    {
        $payload = [
            'RegisterAlias' => $alias,
            'Type' => $type ?? Constants::ALIAS_TYPE_CARD,
            'ReturnUrls' => $returnUrls,
            'LanguageCode' => 'en',
        ];
        return $this->doRequest(self::ALIAS_INSERT_PATH, $payload);
    }

    public function assertInsertAlias(string $token): array
    {
        $payload = [
            'Token' => $token,
        ];
        return $this->doRequest(self::ALIAS_ASSERT_INSERT_PATH, $payload);
    }

    public function deleteAlias(string $id): array
    {
        $payload = [
            'AliasId' => $id,
        ];
        return $this->doRequest(self::ALIAS_DELETE_PATH, $payload);
    }

    /**
     * @return string
     */
    public function getApiEndpoint()
    {
        return $this->options['sandbox'] ? 'https://test.saferpay.com/api' : 'https://www.saferpay.com/api';
    }
}
