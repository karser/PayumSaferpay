<?php declare(strict_types=1);

namespace Karser\PayumSaferpay\Tests\Unit\Action\Api;

use Karser\PayumSaferpay\Action\Api\RefundTransactionAction;
use Karser\PayumSaferpay\Request\Api\RefundTransaction;

class RefundTransactionActionTest extends BaseApiActionTest
{
    protected $actionClass = RefundTransactionAction::class;
    protected $requestClass = RefundTransaction::class;

}
