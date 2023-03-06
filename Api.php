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
        'interface' => null,
        'optionalParameters' => null,
    );

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

    protected function doRequest(string $path, array $fields): array
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

    public function initTransaction(array $model): array
    {
        $payload = [
            'TerminalId' => $this->options['terminalId'],
            'Payment' => $model['Payment'],
            'Payer' => $model['Payer'] ?? [
                'LanguageCode' => 'en',
            ],
            'ReturnUrls' => $model['ReturnUrls'],
        ];

        $payload = $this->addOptionalInterfaceParams(Constants::INTERFACE_TRANSACTION, $payload);

        $paymentMeans = $model['PaymentMeans'] ?? null;

        if (null !== $paymentMeans) {
            $payload['PaymentMeans'] = $paymentMeans;
        }

        return $this->doRequest(self::TRANSACTION_INIT_PATH, $payload);
    }

    public function initPaymentPage(array $model): array
    {
        $payload = [
            'TerminalId' => $this->options['terminalId'],
            'Payment' => $model['Payment'],
            'Payer' => $model['Payer'] ?? [
                'LanguageCode' => 'en',
            ],
            'ReturnUrls' => $model['ReturnUrls'],
        ];

        $payload = $this->addOptionalInterfaceParams(Constants::INTERFACE_PAYMENT_PAGE, $payload);

        $notification = $model['Notification'] ?? null;

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

    public function getApiEndpoint(): string
    {
        return $this->options['sandbox'] ? 'https://test.saferpay.com/api' : 'https://www.saferpay.com/api';
    }

    public function doInstantCapturing(): bool
    {
        return $this->options['instantCapturing'] === true;
    }

    public function getCaptureStrategy(): string
    {
        if (isset($this->options['interface']) && is_string($this->options['interface'])) {
            return $this->options['interface'];
        }

        return Constants::INTERFACE_TRANSACTION;
    }

    protected function addOptionalInterfaceParams(string $interface, array $payload): array
    {
        $allowedOptions = [
            Constants::INTERFACE_PAYMENT_PAGE => [
                'config_set',
                'payment_methods',
                'wallets',
                'notification_merchant_email',
                'notification_payer_email',
                'styling_css_url',
                'styling_content_security_enabled',
                'styling_theme',
                'payer_note',
            ],
            Constants::INTERFACE_TRANSACTION => [
                'config_set',
                'payment_methods',
                'styling_css_url', // deprecated
                'styling_content_security_enabled',
                'styling_theme',
                'payer_note',
            ]
        ];

        $optionalInterfaceOptions = $this->options['optionalParameters'] ?? [];

        foreach ($optionalInterfaceOptions as $optionName => $optionValue) {

            if (empty($optionValue)) {
                continue;
            }

            if (!in_array($optionName, $allowedOptions[$interface])) {
                continue;
            }

            switch($optionName) {
                case 'config_set':
                    $payload['ConfigSet'] = (string) $optionValue;
                    break;
                case 'payment_methods':
                    $payload['PaymentMethods'] = $this->trimExplode($optionValue);
                    break;
                case 'wallets':
                    $payload['Wallets'] = $this->trimExplode($optionValue);
                    break;
                case 'notification_merchant_email':
                    $payload['Notification'] = $payload['Notification'] ?? [];
                    $payload['Notification']['MerchantEmails'] = $this->trimExplode($optionValue);
                    break;
                case 'notification_payer_email':
                    $payload['Notification'] = $payload['Notification'] ?? [];
                    $payload['Notification']['PayerEmail'] = (string) $optionValue;
                    break;
                case 'styling_css_url':
                    $payload['Styling'] = $payload['Styling'] ?? [];
                    $payload['Styling']['CssUrl'] = $optionValue;
                    break;
                case 'styling_content_security_enabled':
                    $payload['Styling'] = $payload['Styling'] ?? [];
                    $payload['Styling']['ContentSecurityEnabled'] = $optionValue;
                    break;
                case 'styling_theme':
                    $payload['Styling'] = $payload['Styling'] ?? [];
                    $payload['Styling']['Theme'] = $optionValue;
                    break;
                 case 'payer_note':
                    $payload['PayerNote'] = $optionValue;
                    break;
            }
        }

        return $payload;
    }

    protected function trimExplode(string $data, string $delimiter = ','): array {
        return array_map('trim', explode($delimiter, $data));
    }
}
