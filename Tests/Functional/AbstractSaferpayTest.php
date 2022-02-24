<?php declare(strict_types=1);

namespace Karser\PayumSaferpay\Tests\Functional;

use Goutte\Client;
use Karser\PayumSaferpay\Model\CardAlias;
use Karser\PayumSaferpay\Model\CardAliasInterface;
use Karser\PayumSaferpay\Request\InsertCardAlias;
use Karser\PayumSaferpay\SaferpayGatewayFactory;
use Payum\Core\Bridge\PlainPhp\Security\TokenFactory;
use Payum\Core\GatewayFactoryInterface;
use Payum\Core\GatewayInterface;
use Payum\Core\Model\Payment;
use Payum\Core\Model\PaymentInterface;
use Payum\Core\Payum;
use Payum\Core\PayumBuilder;
use Payum\Core\Registry\StorageRegistryInterface;
use Payum\Core\Reply\HttpRedirect;
use Payum\Core\Reply\ReplyInterface;
use Payum\Core\Request\Capture;
use Payum\Core\Request\GetHumanStatus;
use Payum\Core\Security\TokenInterface;
use Payum\Core\Storage\FilesystemStorage;
use Payum\Core\Storage\StorageInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\BrowserKit\Exception\BadMethodCallException;
use Symfony\Component\BrowserKit\Response;
use Symfony\Component\DomCrawler\Crawler;

abstract class AbstractSaferpayTest extends TestCase
{
    protected const GATEWAY_NAME = 'saferpay';
    protected const HOST = 'http://localhost';
    protected const AMOUNT = 123; // 1.23
    protected const CURRENCY = 'USD';
    protected const DESCRIPTION = 'A description';
    protected const ALIAS_LIFETIME = 1600;

    //LS: Liability shift; AUTH: Authenticated; 3D: 3D Secure;
    //All cards are from Mastercard
    protected const CARD_SUCCESS_LS_AUTH_3D = '9030100052000000';
    protected const CARD_SUCCESS = '9030101152000007';
    protected const CARD_FAILED = '9030100152000009'; //'9010100152000003';

    /** @var Payum */
    protected $payum;

    /** @var GatewayInterface */
    protected $gateway;

    /** @var StorageInterface */
    protected $storage;

    /** @var StorageInterface */
    protected $cardAliasStorage;

    /** @var Client */
    protected $client;

    public function setUp(): void
    {
        $builder = (new PayumBuilder())
            ->addDefaultStorages()
            ->addStorage(CardAlias::class, new FilesystemStorage(sys_get_temp_dir(), CardAlias::class))
            ->setTokenFactory(static function(StorageInterface $tokenStorage, StorageRegistryInterface $registry) {
                return new TokenFactory($tokenStorage, $registry, self::HOST);
            })
            ->addGatewayFactory('saferpay', static function(array $config, GatewayFactoryInterface $coreGatewayFactory) {
                return new SaferpayGatewayFactory($config, $coreGatewayFactory);
            })
            ->addGateway(self::GATEWAY_NAME, [
                'factory' => 'saferpay',
                'username' => 'API_401860_80003225',
                'password' => 'C-y*bv8346Ze5-T8',
                'customerId' => '401860',
                'terminalId' => '17795278',
                'sandbox' => true,
            ]);
        $payum = $builder->getPayum();

        $this->payum = $payum;
        $this->gateway = $payum->getGateway(self::GATEWAY_NAME);
        $this->storage = $this->payum->getStorage(Payment::class);
        $this->cardAliasStorage = $this->payum->getStorage(CardAlias::class);

        $client = new Client();
        $client->followRedirects(false);
        $this->client = $client;
    }

    protected function submitForm(string $buttonSel, array $fieldValues = [], string $method = 'POST', array $serverParameters = []): Crawler
    {
        $crawler = $this->client->getCrawler();
        if (null === $crawler) {
            throw new BadMethodCallException(sprintf('The "request()" method must be called before "%s()".', __METHOD__));
        }

        $buttonNode = $crawler->filter($buttonSel)->first();
        $form = $buttonNode->form($fieldValues, $method);

        return $this->client->submit($form, [], $serverParameters);
    }

    protected function clickLink(string $linkSelector): Crawler
    {
        $crawler = $this->client->getCrawler();
        if (null === $crawler) {
            throw new BadMethodCallException(sprintf('The "request()" method must be called before "%s()".', __METHOD__));
        }

        return $this->client->click($crawler->filter($linkSelector)->link());
    }

    protected function clickButton(string $buttonSelector): Crawler
    {
        $crawler = $this->client->getCrawler();
        if (null === $crawler) {
            throw new BadMethodCallException(sprintf('The "request()" method must be called before "%s()".', __METHOD__));
        }

        $buttonNode = $crawler->filter($buttonSelector)->first();
        $form = $buttonNode->form([], 'POST');

        return $this->client->submit($form);
    }

    protected function composeFormData(string $card, bool $cvc = true): array
    {
        $data = [
            'CardNumber' => $card,
            'Expiry' => sprintf('01/%d', date('Y', strtotime('+1 year'))),
            'HolderName' => 'John Doe',
        ];
        if ($cvc) {
            $data['VerificationCode'] = '111';
        }
        return $data;
    }

    protected function capture(TokenInterface $token, PaymentInterface $payment): ?ReplyInterface
    {
        $captureRequest = new Capture($token);
        $captureRequest->setModel($payment);
        return $this->gateway->execute($captureRequest, true);
    }

    protected function assertStatus(string $expected, PaymentInterface $payment): void
    {
        $status = new GetHumanStatus($payment);
        $this->gateway->execute($status);
        self::assertEquals($expected, $status->getValue());
    }

    protected function createPayment(array $details = []): Payment
    {
        /** @var Payment $payment */
        $payment = $this->storage->create();
        $payment->setNumber(uniqid());
        $payment->setCurrencyCode(self::CURRENCY);
        $payment->setTotalAmount(self::AMOUNT);
        $payment->setDescription(self::DESCRIPTION);
        $payment->setDetails($details);
        $this->storage->update($payment);
        return $payment;
    }

    protected function getThroughCheckout(string $url, array $formData, string $action = 'submit'): string
    {
        $this->client->request('GET', $url);
        if (false !== strpos($url, '/vt2/api/PaymentPage')) {
            $this->client->followRedirect();
            $this->client->submitForm('MasterCard');
            $this->client->followRedirect();
        }

        if (
            false !== strpos($this->client->getCrawler()->getUri(), '/VT2/mpp/PaymentDataEntry/Index')
            || false !== strpos($this->client->getCrawler()->getUri(), '/vt2/Api/Post')
            || false !== strpos($this->client->getCrawler()->getUri(), '/vt2/api/register/card')
        ) {
            if ($action === 'abort') {
                $location = $this->client->getCrawler()->filter('button.btn-abort')->attr('formaction');
                if (0 === strpos($location, self::HOST)) {
                    return $location;
                }
                $this->clickButton('button.btn-abort');
            } else {
                $this->client->submitForm('SubmitToNext', $formData);
            }
            /** @var Response $response */
            $response = $this->client->getResponse();
            if ($response->getStatusCode() === 302) {
                $location = $response->getHeader('Location');
                if (0 === strpos($location, self::HOST)) {
                    return $location;
                }
                $this->client->followRedirect();
            }
        }

        if (false !== strpos($this->client->getCrawler()->getUri(), '/VT2/mpp/PaymentDataEntry/Index')) {
            self::assertSame(200, $this->client->getResponse()->getStatusCode());
            $this->client->submitForm( $action === 'submit' ? 'Buy' : 'Cancel');
            self::assertSame(302, $this->client->getResponse()->getStatusCode());
            $this->client->followRedirect();
        }
        if (
            false !== strpos($this->client->getCrawler()->getUri(), '/VT2/mpp/ThreeDS/Index')
            || false !== strpos($this->client->getCrawler()->getUri(), '/VT2/api/ThreeDs')
        ) {
            self::assertSame(200, $this->client->getResponse()->getStatusCode());
            $this->submitForm('[type="submit"]');
            self::assertSame(200, $this->client->getResponse()->getStatusCode());

            $this->client->submitForm($action === 'submit' ? 'Submit' : 'Cancel');
            self::assertSame(200, $this->client->getResponse()->getStatusCode());

            $this->client->submitForm('Submit');
            self::assertSame(200, $this->client->getResponse()->getStatusCode());
            $this->clickLink('a.btn-next');

            $response = $this->client->getResponse();
            self::assertSame(302, $response->getStatusCode());
            $location = $response->getHeader('Location');
            if (0 === strpos($location, self::HOST)) {
                return $location;
            }
            $this->client->followRedirect();
        }
        if (false !== strpos($this->client->getCrawler()->getUri(), '/VT2/mpp/Error/System')) {
            $this->client->submitForm('Cancel');

            $response = $this->client->getResponse();
            self::assertSame(302, $response->getStatusCode());
            $location = $response->getHeader('Location');
            if (0 === strpos($location, self::HOST)) {
                return $location;
            }
            $this->client->followRedirect();
        }
        return $this->client->getCrawler()->filter('a.btn-next')->first()->attr('href');
    }

    protected function createCardAlias(array $details): CardAliasInterface
    {
        /** @var CardAlias $alias */
        $alias = $this->cardAliasStorage->create();
        $alias->setDetails($details);
        $this->cardAliasStorage->update($alias);
        return $alias;
    }

    protected function createCapturedPayment(array $options): PaymentInterface
    {
        $payment = $this->createPayment($options);

        $token = $this->payum->getTokenFactory()->createCaptureToken(self::GATEWAY_NAME, $payment, 'done.php');
        $this->payum->getHttpRequestVerifier()->invalidate($token); //no need to store token

        # INIT transaction
        $reply = $this->capture($token, $payment);
        if ($reply instanceof HttpRedirect) {
            # submit form
            $iframeRedirect = $this->getThroughCheckout($reply->getUrl(), $this->composeFormData(self::CARD_SUCCESS));
            parse_str(parse_url($iframeRedirect, PHP_URL_QUERY), $_GET);

            # AUTHORIZE AND CAPTURE
            $this->capture($token, $payment);
        }
        return $payment;
    }

    protected function createInsertedCardAlias(array $options): CardAliasInterface
    {
        $cardAlias = $this->createCardAlias($options);

        $token = $this->payum->getTokenFactory()->createCaptureToken(self::GATEWAY_NAME, $cardAlias, 'done.php');
        $this->payum->getHttpRequestVerifier()->invalidate($token); //no need to store token

        $reply = $this->insertCardAlias($token, $cardAlias);

        # submit form
        $iframeRedirect = $this->getThroughCheckout($reply->getUrl(), $this->composeFormData(self::CARD_SUCCESS, $cvc = false));
        parse_str(parse_url($iframeRedirect, PHP_URL_QUERY), $_GET);

        $this->insertCardAlias($token, $cardAlias);

        return $cardAlias;
    }

    protected function insertCardAlias(TokenInterface $token, CardAliasInterface $cardAlias): ?ReplyInterface
    {
        $insertCardAliasRequest = new InsertCardAlias($token);
        $insertCardAliasRequest->setModel($cardAlias);
        return $this->gateway->execute($insertCardAliasRequest, true);
    }
}
