<?php declare(strict_types=1);

namespace Karser\PayumSaferpay\Model;

use Payum\Core\Model\CreditCardInterface;
use Payum\Core\Model\DetailsAggregateInterface;
use Payum\Core\Model\DetailsAwareInterface;

interface CardAliasInterface extends CreditCardInterface, DetailsAwareInterface, DetailsAggregateInterface
{

}
