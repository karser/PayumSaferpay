<?php declare(strict_types=1);

namespace Karser\PayumSaferpay\Tests\Unit\Action\Api;

use Karser\PayumSaferpay\Action\Api\InsertAliasAction;
use Karser\PayumSaferpay\Request\Api\InsertAlias;

class InsertAliasActionTest extends BaseApiActionTest
{
    protected string $actionClass = InsertAliasAction::class;
    protected string $requestClass = InsertAlias::class;
}
