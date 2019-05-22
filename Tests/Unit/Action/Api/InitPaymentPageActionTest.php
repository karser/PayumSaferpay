<?php declare(strict_types=1);

namespace Karser\PayumSaferpay\Tests\Unit\Action\Api;

use Karser\PayumSaferpay\Action\Api\InitPaymentPageAction;
use Karser\PayumSaferpay\Request\Api\InitPaymentPage;

class InitPaymentPageActionTest extends BaseApiActionTest
{
    protected $actionClass = InitPaymentPageAction::class;
    protected $requestClass = InitPaymentPage::class;
}
