<?php declare(strict_types=1);

namespace Karser\PayumSaferpay\Tests\Unit\Action\Api;

use Karser\PayumSaferpay\Action\Api\DeleteAliasAction;
use Karser\PayumSaferpay\Request\Api\DeleteAlias;

class DeleteAliasActionTest extends BaseApiActionTest
{
    protected $actionClass = DeleteAliasAction::class;
    protected $requestClass = DeleteAlias::class;
}
