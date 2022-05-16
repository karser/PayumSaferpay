<?php declare(strict_types=1);

namespace Karser\PayumSaferpay;

use Karser\PayumSaferpay\Action\Api\AssertInsertAliasAction;
use Karser\PayumSaferpay\Action\Api\AssertPaymentPageAction;
use Karser\PayumSaferpay\Action\Api\AuthorizeReferencedTransactionAction;
use Karser\PayumSaferpay\Action\Api\AuthorizeTransactionAction;
use Karser\PayumSaferpay\Action\Api\CaptureTransactionAction;
use Karser\PayumSaferpay\Action\Api\DeleteAliasAction;
use Karser\PayumSaferpay\Action\Api\InitPaymentPageAction;
use Karser\PayumSaferpay\Action\Api\InitTransactionAction;
use Karser\PayumSaferpay\Action\Api\InsertAliasAction;
use Karser\PayumSaferpay\Action\Api\RefundTransactionAction;
use Karser\PayumSaferpay\Action\AuthorizeAction;
use Karser\PayumSaferpay\Action\CancelAction;
use Karser\PayumSaferpay\Action\CaptureReferencedAction;
use Karser\PayumSaferpay\Action\ConvertPaymentAction;
use Karser\PayumSaferpay\Action\CaptureAction;
use Karser\PayumSaferpay\Action\InsertCardAliasAction;
use Karser\PayumSaferpay\Action\NotifyAction;
use Karser\PayumSaferpay\Action\RefundAction;
use Karser\PayumSaferpay\Action\StatusAction;
use Karser\PayumSaferpay\Action\SyncAction;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayFactory;

class SaferpayGatewayFactory extends GatewayFactory
{
    /**
     * {@inheritDoc}
     */
    protected function populateConfig(ArrayObject $config): void
    {
        $config->defaults([
            'payum.factory_name' => 'saferpay',
            'payum.factory_title' => 'saferpay',

            'payum.action.capture' => new CaptureAction(),
            'payum.action.insert_card_alias' => new InsertCardAliasAction(),
            'payum.action.capture_referenced' => new CaptureReferencedAction(),
            'payum.action.authorize' => new AuthorizeAction(),
            'payum.action.refund' => new RefundAction(),
            'payum.action.cancel' => new CancelAction(),
            'payum.action.notify' => new NotifyAction(),
            'payum.action.status' => new StatusAction(),
            'payum.action.sync'   => new SyncAction(),
            'payum.action.convert_payment' => new ConvertPaymentAction(),

            'payum.action.api.init_payment_page' => new InitPaymentPageAction(),
            'payum.action.api.assert_payment_page' => new AssertPaymentPageAction(),
            'payum.action.api.init_transaction' => new InitTransactionAction(),
            'payum.action.api.authorize_transaction' => new AuthorizeTransactionAction(),
            'payum.action.api.capture_transaction' => new CaptureTransactionAction(),
            'payum.action.api.authorize_referenced_transaction' => new AuthorizeReferencedTransactionAction(),
            'payum.action.api.refund_transaction' => new RefundTransactionAction(),
            'payum.action.api.insert_alias' => new InsertAliasAction(),
            'payum.action.api.assert_insert_alias' => new AssertInsertAliasAction(),
            'payum.action.api.delete_alias' => new DeleteAliasAction(),
        ]);

        $prependActions = $config['payum.prepend_actions'] ?? [];
        $prependActions[] = 'payum.action.capture_referenced';
        $prependActions[] = 'payum.action.insert_card_alias';
        $config['payum.prepend_actions'] = $prependActions;

        if (false == $config['payum.api']) {
            $config['payum.default_options'] = [
                'sandbox' => true,
            ];
            $config->defaults($config['payum.default_options']);
            $config['payum.required_options'] = ['username', 'password', 'customerId', 'terminalId'];

            $config['payum.api'] = static function (ArrayObject $config) {
                $config->validateNotEmpty($config['payum.required_options']);

                return new Api((array) $config, $config['payum.http_client'], $config['httplug.message_factory']);
            };
        }
    }
}
