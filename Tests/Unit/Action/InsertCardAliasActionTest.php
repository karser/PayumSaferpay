<?php declare(strict_types=1);

namespace Karser\PayumSaferpay\Tests\Unit\Action;

use Karser\PayumSaferpay\Action\InsertCardAliasAction;
use Karser\PayumSaferpay\Model\CardAlias;
use Karser\PayumSaferpay\Request\InsertCardAlias;
use Payum\Core\Action\ActionInterface;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\Model\Token;
use Payum\Core\Tests\GenericActionTest;

class InsertCardAliasActionTest extends GenericActionTest
{
    protected $requestClass = InsertCardAlias::class;
    protected $actionClass = InsertCardAliasAction::class;

    /**
     * @test
     */
    public function shouldImplementActionInterface(): void
    {
        $rc = new \ReflectionClass(InsertCardAliasAction::class);
        $this->assertTrue($rc->implementsInterface(ActionInterface::class));
    }

    /**
     * @test
     */
    public function shouldImplementGatewayAwareInterface(): void
    {
        $rc = new \ReflectionClass(InsertCardAliasAction::class);
        $this->assertTrue($rc->implementsInterface(GatewayAwareInterface::class));
    }

    /**
     * @test
     */
    public function couldBeConstructedWithoutAnyArguments(): void
    {
        self::assertNotNull(new $this->actionClass());
    }

    public function provideSupportedRequests(): \Iterator
    {
        function getRequest($details): InsertCardAlias
        {
            $alias = new CardAlias();
            $alias->setDetails($details);
            $request = new InsertCardAlias(new Token());
            $request->setModel($alias);
            return $request;
        }

        yield [getRequest([])];
        yield [getRequest(new \ArrayObject())];
    }
}
