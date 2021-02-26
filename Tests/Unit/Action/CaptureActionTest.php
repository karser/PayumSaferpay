<?php declare(strict_types=1);

namespace Karser\PayumSaferpay\Tests\Unit\Action;

use Karser\PayumSaferpay\Action\CaptureAction;
use Payum\Core\Action\ActionInterface;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\Model\Token;
use Payum\Core\Request\Capture;
use Payum\Core\Tests\GenericActionTest;

class CaptureActionTest extends GenericActionTest
{
    protected $requestClass = Capture::class;
    protected $actionClass = CaptureAction::class;

    /**
     * @test
     */
    public function shouldImplementActionInterface(): void
    {
        $rc = new \ReflectionClass(CaptureAction::class);
        $this->assertTrue($rc->implementsInterface(ActionInterface::class));
    }

    /**
     * @test
     */
    public function shouldImplementGatewayAwareInterface(): void
    {
        $rc = new \ReflectionClass(CaptureAction::class);
        $this->assertTrue($rc->implementsInterface(GatewayAwareInterface::class));
    }

    public function provideSupportedRequests(): \Iterator
    {
        ($r1 = new Capture(new Token()))->setModel(array());
        ($r2 = new Capture(new Token()))->setModel(new \ArrayObject());

        yield [[$r1], [$r2]];
    }

    /**
     * @test
     */
    public function couldBeConstructedWithoutAnyArguments(): void
    {
        self::assertNotNull(new $this->actionClass());
    }
}
