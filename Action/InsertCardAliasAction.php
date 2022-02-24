<?php declare(strict_types=1);

namespace Karser\PayumSaferpay\Action;

use Karser\PayumSaferpay\Model\CardAliasInterface;
use Karser\PayumSaferpay\Request\Api\AssertInsertAlias;
use Karser\PayumSaferpay\Request\Api\InsertAlias;
use Karser\PayumSaferpay\Request\InsertCardAlias;
use League\Uri\Http as HttpUri;
use League\Uri\UriModifier;
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

    private function doExecute(InsertCardAlias $request, ArrayObject $details): void
    {
        if (empty($details['Token'])) {
            $this->insertAliasAction($request, $details);
            return; //throws redirect
        }
        $this->gateway->execute(new AssertInsertAlias($details));
    }

    private function insertAliasAction(InsertCardAlias $request, ArrayObject $model): void
    {
        if (empty($model['ReturnUrls'])) {
            $token = $request->getToken();

            $successUrl = UriModifier::mergeQuery(HttpUri::createFromString($token->getTargetUrl()), 'success=1');
            $failedUrl = UriModifier::mergeQuery(HttpUri::createFromString($token->getTargetUrl()), 'fail=1');
            $cancelUri = UriModifier::mergeQuery(HttpUri::createFromString($token->getTargetUrl()), 'abort=1');

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
