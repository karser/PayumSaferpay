<?php declare(strict_types=1);

namespace Karser\PayumSaferpay\Action;

use Karser\PayumSaferpay\Constants;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Model\PaymentInterface;
use Payum\Core\Request\Convert;

class ConvertPaymentAction implements ActionInterface
{
    /**
     * {@inheritDoc}
     *
     * @param Convert $request
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        /** @var PaymentInterface $payment */
        $payment = $request->getSource();

        $model = ArrayObject::ensureArrayObject($payment->getDetails());

        $model['Payment'] = array_merge($model['Payment'] ?? [], [
            'Amount' => [
                'Value' => $payment->getTotalAmount(),
                'CurrencyCode' => $payment->getCurrencyCode(),
            ],
            'OrderId' => $payment->getNumber(),
            'Description' => $payment->getDescription(),
        ]);

        $model['Transaction'] = [
            'Type' => Constants::TYPE_PAYMENT,
            'Status' => null,
            'Amount' => [
                'Value' => $payment->getTotalAmount(),
                'CurrencyCode' => $payment->getCurrencyCode(),
            ],
            'OrderId' => $payment->getNumber(),
        ];

        $request->setResult((array) $model);
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request): bool
    {
        return
            $request instanceof Convert &&
            $request->getSource() instanceof PaymentInterface &&
            $request->getTo() === 'array'
        ;
    }
}
