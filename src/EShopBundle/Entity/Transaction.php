<?php
/**
 * Created by PhpStorm.
 * User: Marto
 * Date: 07/01/19
 * Time: 11:42
 */

namespace EShopBundle\Entity;


class Transaction
{
    /**
     * @var float
     */
    private $amount;

    /**
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param float $amount
     */
    public function setAmount($amount)
    {   if($amount<0){
        throw new \Exception("Transaction with negative numbers is not allowed!");
    }
        $this->amount = $amount;
    }


}