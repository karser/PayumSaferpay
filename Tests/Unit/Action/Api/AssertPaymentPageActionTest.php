<?php declare(strict_types=1);

namespace Karser\PayumSaferpay\Tests\Unit\Action\Api;

use Karser\PayumSaferpay\Action\Api\AssertPaymentPageAction;
use Karser\PayumSaferpay\Request\Api\AssertPaymentPage;

class AssertPaymentPageActionTest extends BaseApiActionTest
{
    protected string $actionClass = AssertPaymentPageAction::class;
    protected string $requestClass = AssertPaymentPage::class;
}
