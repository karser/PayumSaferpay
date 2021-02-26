<?php declare(strict_types=1);

namespace Karser\PayumSaferpay\Tests\Unit\Action;

use Karser\PayumSaferpay\Action\CaptureReferencedAction;
use Karser\PayumSaferpay\Request\CaptureReferenced;
use Payum\Core\Action\ActionInterface;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\Model\Payment;
use Payum\Core\Tests\GenericActionTest;

class CaptureReferencedActionTest extends GenericActionTest
{
    protected $requestClass = CaptureReferenced::class;
    protected $actionClass = CaptureReferencedAction::class;


    /**
     * @test
     */
    public function shouldImplementActionInterface(): void
    {
        $rc = new \ReflectionClass(CaptureReferencedAction::class);
        $this->assertTrue($rc->implementsInterface(ActionInterface::class));
    }

    /**
     * @test
     */
    public function shouldImplementGatewayAwareInterface(): void
    {
        $rc = new \ReflectionClass(CaptureReferencedAction::class);
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
     * Overridden because CaptureAction requires request to have TOKEN
     */
    public function provideSupportedRequests(): \Iterator
    {
        yield [new CaptureReferenced(new Payment())];
    }

}
