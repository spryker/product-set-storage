<?php

/**
 * (c) Spryker Systems GmbH copyright protected
 */

namespace Spryker\Zed\Cart\Dependency\Facade;

use Generated\Shared\Transfer\QuoteTransfer;
use Spryker\Zed\Calculation\Business\CalculationFacade;
use Spryker\Zed\Calculation\Business\CalculationFacadeInterface;

class CartToCalculationBridge implements CartToCalculationInterface
{

    /**
     * @var CalculationFacadeInterface
     */
    protected $calculationFacade;

    /**
     * @param CalculationFacadeInterface $calculationFacade
     */
    public function __construct($calculationFacade)
    {
        $this->calculationFacade = $calculationFacade;
    }

    /**
     * @param \Generated\Shared\Transfer\QuoteTransfer $quoteTransfer
     *
     * @return \Generated\Shared\Transfer\QuoteTransfer
     */
    public function recalculate(QuoteTransfer $quoteTransfer)
    {
        return $this->calculationFacade->recalculate($quoteTransfer);
    }

}
