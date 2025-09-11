<?php

/**
 * Checkout.com
 * Authorized and regulated as an electronic money institution
 * by the UK Financial Conduct Authority (FCA) under number 900816.
 *
 * PHP version 7
 *
 * @category  Magento2
 * @package   Checkout.com
 * @author    Platforms Development Team <platforms@checkout.com>
 * @copyright 2010-present Checkout.com all rights reserved
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

declare(strict_types=1);

namespace CheckoutCom\Magento2\Model\Config\Backend\Source;

use Magento\Framework\Data\OptionSourceInterface;

class ConfigFlowPredefinedDesign implements OptionSourceInterface
{

    public const PREDEFINED_DESIGN_DEFAULT_CONFIG_VALUE = '0';
    public const PREDEFINED_DESIGN_SIMPLICITY_CONFIG_VALUE = '1';
    public const PREDEFINED_DESIGN_MIDNIGHT_CONFIG_VALUE = '2';
    public const PREDEFINED_DESIGN_GRAPEFRUIT_CONFIG_VALUE = '3';

    public const PREDEFINED_DESIGN_DEFAULT_CONFIG_LABEL = 'Default';
    public const PREDEFINED_DESIGN_SIMPLICITY_CONFIG_LABEL = 'Simplicity';
    public const PREDEFINED_DESIGN_MIDNIGHT_CONFIG_LABEL = 'Midnight';
    public const PREDEFINED_DESIGN_GRAPEFRUIT_CONFIG_LABEL = 'Grapefruit';

    public const PREDEFINED_DESIGN_DEFAULT_CONTENT = '';
    public const PREDEFINED_DESIGN_GRAPEFRUIT_CONTENT = '{"colorBackground":"#F7F7F5","colorBorder":"#F2F2F2","colorPrimary":"#000000","colorSecondary":"#000000","colorAction":"#E05650","colorOutline":"#E1AAA8","colorSuccess":"#06DDB2","colorError":"#ff0000","colorDisabled":"#BABABA","colorInverse":"#F2F2F2","colorFormBackground":"#FFFFFF","colorFormBorder":"#DFDFDF","borderRadius":["8px","50px"],"subheading":{"fontFamily":"Lato, -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Oxygen, Ubuntu, Cantarell, Fira Sans, Droid Sans, Helvetica Neue, Noto Sans, Liberation Sans, Arial, sans-serif;","fontSize":"16px","lineHeight":"24px","fontWeight":400,"letterSpacing":0},"footnote":{"fontFamily":"Lato, -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Oxygen, Ubuntu, Cantarell, Fira Sans, Droid Sans, Helvetica Neue, Noto Sans, Liberation Sans, Arial, sans-serif;","fontSize":"14px","lineHeight":"20px","fontWeight":400,"letterSpacing":0},"button":{"fontFamily":"Lato, -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Oxygen, Ubuntu, Cantarell, Fira Sans, Droid Sans, Helvetica Neue, Noto Sans, Liberation Sans, Arial, sans-serif;","fontSize":"16px","lineHeight":"24px","fontWeight":700,"letterSpacing":0},"input":{"fontFamily":"-apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Oxygen, Ubuntu, Cantarell, Fira Sans, Droid Sans, Helvetica Neue, Noto Sans, Liberation Sans, Arial, sans-serif;","fontSize":"16px","lineHeight":"20px","fontWeight":400,"letterSpacing":0},"label":{"fontFamily":"Lato, -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Oxygen, Ubuntu, Cantarell, Fira Sans, Droid Sans, Helvetica Neue, Noto Sans, Liberation Sans, Arial, sans-serif;","fontSize":"14px","lineHeight":"20px","fontWeight":400,"letterSpacing":0}}';
    public const PREDEFINED_DESIGN_MIDNIGHT_CONTENT = '{"colorBackground":"#0A0A0C","colorBorder":"#68686C","colorPrimary":"#F9F9FB","colorSecondary":"#828388","colorAction":"#5E48FC","colorOutline":"#ADA4EC","colorSuccess":"#2ECC71","colorError":"#FF3300","colorDisabled":"#64646E","colorInverse":"#F9F9FB","colorFormBackground":"#1F1F1F","colorFormBorder":"#1F1F1F","borderRadius":["8px","8px"],"subheading":{"fontFamily":"Roboto Mono, -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Oxygen, Ubuntu, Cantarell, Fira Sans, Droid Sans, Helvetica Neue, Noto Sans, Liberation Sans, Arial, sans-serif;","fontSize":"16px","lineHeight":"24px","fontWeight":700,"letterSpacing":0},"footnote":{"fontFamily":"PT Sans, -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Oxygen, Ubuntu, Cantarell, Fira Sans, Droid Sans, Helvetica Neue, Noto Sans, Liberation Sans, Arial, sans-serif;","fontSize":"14px","lineHeight":"20px","fontWeight":400,"letterSpacing":0},"button":{"fontFamily":"Roboto Mono, -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Oxygen, Ubuntu, Cantarell, Fira Sans, Droid Sans, Helvetica Neue, Noto Sans, Liberation Sans, Arial, sans-serif;","fontSize":"16px","lineHeight":"24px","fontWeight":700,"letterSpacing":0},"input":{"fontFamily":"-apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Oxygen, Ubuntu, Cantarell, Fira Sans, Droid Sans, Helvetica Neue, Noto Sans, Liberation Sans, Arial, sans-serif;","fontSize":"16px","lineHeight":"20px","fontWeight":400,"letterSpacing":0},"label":{"fontFamily":"Roboto Mono, -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Oxygen, Ubuntu, Cantarell, Fira Sans, Droid Sans, Helvetica Neue, Noto Sans, Liberation Sans, Arial, sans-serif;","fontSize":"14px","lineHeight":"20px","fontWeight":400,"letterSpacing":0}}';
    public const PREDEFINED_DESIGN_SIMPLICITY_CONTENT = '{"colorBackground":"#ffffff","colorBorder":"#CED0D1","colorPrimary":"#09182B","colorSecondary":"#828687","colorAction":"#000000","colorOutline":"#FFFFFF","colorSuccess":"#3CB628","colorError":"#8B3232","colorDisabled":"#AAAAAA","colorInverse":"#ffffff","colorFormBackground":"#F5F5F5","colorFormBorder":"#F5F5F5","borderRadius":["0px","0px"],"subheading":{"fontFamily":"Work Sans, -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Oxygen, Ubuntu,Cantarell, Fira Sans, Droid Sans, Helvetica Neue, Noto Sans, Liberation Sans, Arial, sans-serif;","fontSize":"16px","lineHeight":"24px","fontWeight":500,"letterSpacing":0},"footnote":{"fontFamily":"Work Sans, -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Oxygen, Ubuntu, Cantarell, Fira Sans, Droid Sans, Helvetica Neue, Noto Sans, Liberation Sans, Arial, sans-serif;","fontSize":"14px","lineHeight":"20px","fontWeight":400,"letterSpacing":0},"button":{"fontFamily":"Work Sans, -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Oxygen, Ubuntu, Cantarell, Fira Sans, Droid Sans, Helvetica Neue, Noto Sans, Liberation Sans, Arial, sans-serif;","fontSize":"16px","lineHeight":"24px","fontWeight":500,"letterSpacing":0},"input":{"fontFamily":"-apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Oxygen, Ubuntu, Cantarell, Fira Sans, Droid Sans, Helvetica Neue, Noto Sans, Liberation Sans, Arial, sans-serif;","fontSize":"16px","lineHeight":"20px","fontWeight":400,"letterSpacing":0},"label":{"fontFamily":"Work Sans, -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Oxygen, Ubuntu, Cantarell, Fira Sans, Droid Sans, Helvetica Neue, Noto Sans, Liberation Sans, Arial, sans-serif;","fontSize":"14px","lineHeight":"20px","fontWeight":500,"letterSpacing":0}}';

    /**
     * Options getter
     *
     * @return string[][]
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => self::PREDEFINED_DESIGN_DEFAULT_CONFIG_VALUE,
                'label' => __(self::PREDEFINED_DESIGN_DEFAULT_CONFIG_LABEL)
            ],
            [
                'value' => self::PREDEFINED_DESIGN_SIMPLICITY_CONFIG_VALUE,
                'label' => __(self::PREDEFINED_DESIGN_SIMPLICITY_CONFIG_LABEL)
            ],
            [
                'value' => self::PREDEFINED_DESIGN_MIDNIGHT_CONFIG_VALUE,
                'label' => __(self::PREDEFINED_DESIGN_MIDNIGHT_CONFIG_LABEL)
            ],
            [
                'value' => self::PREDEFINED_DESIGN_GRAPEFRUIT_CONFIG_VALUE,
                'label' => __(self::PREDEFINED_DESIGN_GRAPEFRUIT_CONFIG_LABEL)
            ],
        ];
    }
}
