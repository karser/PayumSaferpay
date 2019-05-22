<?php declare(strict_types=1);

namespace Karser\PayumSaferpay\Model;

use Payum\Core\Model\CreditCard;

class CardAlias extends CreditCard implements CardAliasInterface
{
    /**
     * @var array
     */
    protected $details;


    public function __construct()
    {
        parent::__construct();
        $this->details = [];
    }

    /**
     * @return array
     */
    public function getDetails(): array
    {
        return $this->details;
    }

    /**
     * @param array $details
     */
    public function setDetails($details): void
    {
        if ($details instanceof \Traversable) {
            $details = iterator_to_array($details);
        }

        $this->details = $details;
    }
}
