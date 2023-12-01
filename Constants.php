<?php declare(strict_types=1);

namespace Karser\PayumSaferpay;

interface Constants
{
    public const INTERFACE_PAYMENT_PAGE = 'PAYMENT_PAGE';
    public const INTERFACE_TRANSACTION = 'TRANSACTION';

    public const STATUS_PENDING = 'PENDING';
    public const STATUS_AUTHORIZED = 'AUTHORIZED';
    public const STATUS_CAPTURED = 'CAPTURED';
    public const STATUS_CANCELED = 'CANCELED';
    public const STATUS_ABORTED = 'ABORTED';
    public const STATUS_FAILED = 'FAILED';

    public const TYPE_PURCHASE = 'PURCHASE';
    public const TYPE_PAYMENT = 'PAYMENT';
    public const TYPE_REFUND = 'REFUND';

    public const ALIAS_TYPE_CARD = 'CARD';
    public const ALIAS_TYPE_BANK_ACCOUNT = 'BANK_ACCOUNT';
    public const ALIAS_TYPE_POSTFINANCE = 'POSTFINANCE';
    public const ALIAS_TYPE_TWINT = 'TWINT';

    public const ALIAS_ID_GENERATOR_MANUAL = 'MANUAL';
    public const ALIAS_ID_GENERATOR_RANDOM = 'RANDOM';
    public const ALIAS_ID_GENERATOR_RANDOM_UNIQUE = 'RANDOM_UNIQUE';

    public const LS_IF_ALLOWED_BY_SCHEME = 'IF_ALLOWED_BY_SCHEME';
    public const LS_WITH_LIABILITY_SHIFT = 'WITH_LIABILITY_SHIFT';

    public const ERROR_NAME_TRANSACTION_ALREADY_CAPTURED = 'TRANSACTION_ALREADY_CAPTURED';
}
