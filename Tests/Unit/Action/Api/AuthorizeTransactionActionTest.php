<?php declare(strict_types=1);

namespace Karser\PayumSaferpay\Tests\Unit\Action\Api;

use Karser\PayumSaferpay\Action\Api\AuthorizeTransactionAction;
use Karser\PayumSaferpay\Constants;
use Karser\PayumSaferpay\Exception\SaferpayHttpException;
use Karser\PayumSaferpay\Request\Api\AuthorizeTransaction;

class AuthorizeTransactionActionTest extends BaseApiActionTest
{
    protected $actionClass = AuthorizeTransactionAction::class;
    protected $requestClass = AuthorizeTransaction::class;

    /**
     * @test
     */
    public function throwIfStatusIncorrect(): void
    {
        $this->expectExceptionMessage("Cannot authorize transaction with status: \"FAILED\"");
        $this->expectException(\Payum\Core\Exception\LogicException::class);
        $action = new AuthorizeTransactionAction();
        $action->execute(new AuthorizeTransaction(['Transaction' => ['Status' => Constants::STATUS_FAILED]]));
    }

    /**
     * @test
     */
    public function throwIfTokenNotSetInModel(): void
    {
        $this->expectExceptionMessage("Token is missing");
        $this->expectException(\Payum\Core\Exception\LogicException::class);
        $action = new AuthorizeTransactionAction();
        $action->execute(new AuthorizeTransaction(['Transaction' => ['Status' => Constants::STATUS_PENDING]]));
    }


    /**
     * @test
     */
    public function shouldNotCallApi_ifStatusIsAuthorized(): void
    {
        $apiMock = $this->createApiMock();
        $apiMock
            ->expects($this->never())
            ->method('authorizeTransaction')
        ;
        $action = new AuthorizeTransactionAction();
        $action->setApi($apiMock);
        $action->setGateway($this->createGatewayMock());
        $model = array_merge($this->model, [
            'Transaction' => ['Status' => Constants::STATUS_AUTHORIZED],
        ]);
        $request = new AuthorizeTransaction($model);
        $action->execute($request);
    }

    /**
     * @test
     */
    public function shouldCallApi_ifStatusIsPending(): void
    {
        $apiMock = $this->createApiMock();
        $apiMock
            ->expects($this->once())
            ->method('authorizeTransaction')
            ->willReturnCallback(function (string $token, ?string $codition, ?array $alias) {
                $this->assertSame('token', $token);
                return [
                    'Transaction' => 'transaction',
                    'PaymentMeans' => 'paymentMeans',
                    'Liability' => 'liability',
                ];
            })
        ;
        $action = new AuthorizeTransactionAction();
        $action->setApi($apiMock);
        $action->setGateway($this->createGatewayMock());
        $model = array_merge($this->model, [
            'Transaction' => ['Status' => Constants::STATUS_PENDING],
            'Token' => 'token',
        ]);
        $request = new AuthorizeTransaction($model);
        $action->execute($request);
    }

    /**
     * @test
     */
    public function shouldCallApi_withRegisteredAlias(): void
    {
        $apiMock = $this->createApiMock();
        $apiMock
            ->expects($this->once())
            ->method('authorizeTransaction')
            ->willReturnCallback(function (string $token, ?string $codition, ?array $alias) {
                $this->assertSame('token', $token);
                $this->assertSame([
                    'IdGenerator' => Constants::ALIAS_ID_GENERATOR_RANDOM,
                ], $alias);
                return [
                    'Transaction' => 'transaction',
                    'PaymentMeans' => 'paymentMeans',
                    'Liability' => 'liability',
                    'RegistrationResult' => [
                        'Success' => true,
                        'Alias' => [
                            'Id' => 'id',
                        ],
                    ]
                ];
            })
        ;
        $action = new AuthorizeTransactionAction();
        $action->setApi($apiMock);
        $action->setGateway($this->createGatewayMock());
        $model = array_merge($this->model, [
            'Transaction' => ['Status' => Constants::STATUS_PENDING],
            'Token' => 'token',
            'Alias' => [
                'IdGenerator' => Constants::ALIAS_ID_GENERATOR_RANDOM,
            ],
        ]);
        $request = new AuthorizeTransaction($model);
        $action->execute($request);
        $result = iterator_to_array($request->getModel());
        self::assertSame(['Id' => 'id'], $result['Alias']);
    }

    /**
     * @test
     */
    public function shouldSetError_ifExceptionThrown(): void
    {
        $apiMock = $this->createApiMock();
        $apiMock
            ->expects($this->once())
            ->method('authorizeTransaction')
            ->willThrowException(new SaferpayHttpException('ERROR'))
        ;
        $action = new AuthorizeTransactionAction();
        $action->setApi($apiMock);
        $action->setGateway($this->createGatewayMock());
        $model = array_merge($this->model, [
            'Transaction' => ['Status' => Constants::STATUS_PENDING],
            'Token' => 'token',
        ]);
        $request = new AuthorizeTransaction($model);
        $action->execute($request);
        $result = iterator_to_array($request->getModel());
        self::assertSame('ERROR', $result['Error']['Message']);
    }
}
