<?php declare(strict_types=1);

namespace Karser\PayumSaferpay\Tests\Unit\Action\Api;

use Karser\PayumSaferpay\Api;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

abstract class BaseApiActionTest extends TestCase
{
    protected $actionClass;
    protected $requestClass;

    protected $model = [
        'Payment' => [
            'Amount' => [
                'Value' => 123,
                'CurrencyCode' => 'USD',
            ],
            'OrderId' => '5cd2bc8f26268',
            'Description' => 'A description',
        ],
        'Transaction' => [
            'Type' => 'PAYMENT',
            'Status' => NULL,
            'Amount' => [
                'Value' => 123,
                'CurrencyCode' => 'USD',
            ],
            'OrderId' => '5cd2bc8f26268',
        ],
        'ReturnUrls' => [
            'Success' => 'http://localhost/capture.php?payum_token=vDVHCidymg7JaCsHoggk2bKEJSln1rS86o1qjiBpfw0&success=1',
            'Fail' => 'http://localhost/capture.php?payum_token=vDVHCidymg7JaCsHoggk2bKEJSln1rS86o1qjiBpfw0&fail=1',
            'Abort' => 'http://localhost/capture.php?payum_token=vDVHCidymg7JaCsHoggk2bKEJSln1rS86o1qjiBpfw0&abort=1',
        ],
    ];

    /**
     * @test
     */
    public function shouldImplementActionInterface(): void
    {
        $rc = new \ReflectionClass($this->actionClass);
        $this->assertTrue($rc->implementsInterface(ActionInterface::class));
    }

    /**
     * @test
     */
    public function shouldImplementApoAwareInterface(): void
    {
        $rc = new \ReflectionClass($this->actionClass);
        $this->assertTrue($rc->implementsInterface(ApiAwareInterface::class));
    }
    /**
     * @test
     */
    public function shouldImplementsGatewayAwareInterface(): void
    {
        $rc = new \ReflectionClass($this->actionClass);
        $this->assertTrue($rc->implementsInterface(GatewayAwareInterface::class));
    }
    /**
     * @test
     */
    public function couldBeConstructedWithoutAnyArguments(): void
    {
        self::assertNotNull(new $this->actionClass());
    }
    /**
     * @test
     */
    public function shouldSupportDoCaptureRequestAndArrayAccessAsModel(): void
    {
        $action = new $this->actionClass();
        $this->assertTrue($action->supports(new $this->requestClass(new \ArrayObject(), 0)));
    }
    /**
     * @test
     */
    public function shouldNotSupportAnythingNotDoCaptureRequest(): void
    {
        $action = new $this->actionClass();
        $this->assertFalse($action->supports(new \stdClass()));
    }
    /**
     * @test
     */
    public function throwIfNotSupportedRequestGivenAsArgumentForExecute(): void
    {
        $this->expectException(\Payum\Core\Exception\RequestNotSupportedException::class);
        $action = new $this->actionClass();
        $action->execute(new \stdClass());
    }

    /**
     * @return MockObject|Api
     */
    protected function createApiMock()
    {
        return $this->getMockBuilder(Api::class)->disableOriginalConstructor()->getMock();
    }
    /**
     * @return MockObject|GatewayInterface
     */
    protected function createGatewayMock()
    {
        return $this->createMock(GatewayInterface::class);
    }
}
