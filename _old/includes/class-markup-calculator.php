<?php
if (!defined('ABSPATH')) exit;

class NEBF_Markup_Calculator
{

    /**
     * Calculate final price from cost price + margin + rounding
     *
     * @param float $cost_price
     * @param array $margin ['type'=>'percent|fixed', 'value'=>float]
     * @param string $rounding 'none'|'99'|'9'|'whole'
     * @return float
     */
    public static function calculate($cost_price, $margin, $rounding = 'none')
    {

        $price = self::apply_markup($cost_price, $margin);
        $price = self::apply_rounding($price, $rounding);

        return round($price, 2);
    }

    /**
     * Apply markup
     */
    private static function apply_markup($cost_price, $margin)
    {
        $type  = $margin['type'] ?? 'percent';
        $value = floatval($margin['value'] ?? 0);

        if ($type === 'fixed') {
            return $cost_price + $value;
        }

        // percent
        return $cost_price * (1 + ($value / 100));
    }

    /**
     * Round price according to setting
     */
    private static function apply_rounding($price, $rounding)
    {
        switch ($rounding) {
            case '99':
                return ceil($price) - 0.01;
            case '9':
                return ceil($price / 10) * 10 - 1;
            case 'whole':
                return round($price);
            case 'none':
            default:
                return $price;
        }
    }
}
