<?php declare(strict_types=1);

namespace Karser\PayumSaferpay\Tests\Unit\Action\Api;

use Karser\PayumSaferpay\Action\Api\AssertPaymentPageAction;
use Karser\PayumSaferpay\Request\Api\AssertPaymentPage;

class AssertPaymentPageActionTest extends BaseApiActionTest
{
    protected $actionClass = AssertPaymentPageAction::class;
    protected $requestClass = AssertPaymentPage::class;
}
