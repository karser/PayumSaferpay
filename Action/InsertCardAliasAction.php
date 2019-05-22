<?php declare(strict_types=1);

namespace Karser\PayumSaferpay\Action;

use Karser\PayumSaferpay\Model\CardAliasInterface;
use Karser\PayumSaferpay\Request\Api\AssertInsertAlias;
use Karser\PayumSaferpay\Request\Api\InsertAlias;
use Karser\PayumSaferpay\Request\InsertCardAlias;
use League\Uri\Http as HttpUri;
use League\Uri\Modifiers\MergeQuery;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Security\TokenInterface;

class InsertCardAliasAction implements ActionInterface, GatewayAwareInterface
{
    use GatewayAwareTrait;

    /**
     * @param InsertCardAlias $request
     */
    public function execute($request): void
    {

        RequestNotSupportedException::assertSupports($this, $request);

        /** @var $cardAlias CardAliasInterface */
        $cardAlias = $request->getModel();

        $details = ArrayObject::ensureArrayObject($cardAlias->getDetails());

        try {
            $this->doExecute($request, $details);
        } finally {
            $cardAlias->setDetails($details);
        }
    }

    private function doExecute(InsertCardAlias $request, ArrayObject $details)
    {
        if (empty($details['Token'])) {
            $this->insertAliasAction($request, $details);
            return; //throws redirect
        }
        $this->gateway->execute(new AssertInsertAlias($details));
    }

    private function insertAliasAction(InsertCardAlias $request, ArrayObject $model)
    {
        if (empty($model['ReturnUrls'])) {
            $token = $request->getToken();

            $successUrl = HttpUri::createFromString($token->getTargetUrl());
            $modifier = new MergeQuery('success=1');
            $successUrl = $modifier->process($successUrl);

            $failedUrl = HttpUri::createFromString($token->getTargetUrl());
            $modifier = new MergeQuery('fail=1');
            $failedUrl = $modifier->process($failedUrl);

            $cancelUri = HttpUri::createFromString($token->getTargetUrl());
            $modifier = new MergeQuery('abort=1');
            $cancelUri = $modifier->process($cancelUri);

            $model['ReturnUrls'] = [
                'Success' => (string) $successUrl,
                'Fail' => (string) $failedUrl,
                'Abort' => (string) $cancelUri,
            ];
        }

        $this->gateway->execute(new InsertAlias($model));
    }

    public function supports($request): bool
    {
        return
            $request instanceof InsertCardAlias &&
            $request->getModel() instanceof CardAliasInterface &&
            $request->getToken() instanceof TokenInterface
            ;
    }
}
