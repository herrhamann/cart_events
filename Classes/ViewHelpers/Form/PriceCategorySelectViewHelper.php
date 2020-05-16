<?php

namespace Extcode\CartEvents\ViewHelpers\Form;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * PriceCategory Select ViewHelper
 */
class PriceCategorySelectViewHelper extends \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper
{
    /**
     * Output is escaped already. We must not escape children, to avoid double encoding.
     *
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * @var \Extcode\CartEvents\Domain\Model\EventDate
     */
    protected $eventDate = null;

    /**
     * Initialize arguments.
     *
     * @api
     */
    public function initializeArguments()
    {
        parent::initializeArguments();

        $this->registerArgument(
            'eventDate',
            \Extcode\CartEvents\Domain\Model\EventDate::class,
            'event date for select options',
            true
        );
        $this->registerArgument('id', 'string', 'id for select');
        $this->registerArgument('class', 'string', 'class for select');
        $this->registerArgument('name', 'string', 'name for select');
        $this->registerArgument('blank', 'string', 'blank adds blank option');
        $this->registerArgument('required', 'bool', 'required adds html5 required', false, true);
    }

    /**
     * render
     *
     * @return string
     */
    public function render()
    {
        $this->eventDate = $this->arguments['eventDate'];

        $select = [];

        if ($this->hasArgument('id')) {
            $select[] = 'id="' . $this->arguments['id'] . '" ';
        }
        if ($this->hasArgument('class')) {
            $select[] = 'class="' . $this->arguments['class'] . '" ';
        }
        if ($this->hasArgument('name')) {
            $select[] = 'name="' . $this->arguments['name'] . '" ';
        }
        if ($this->hasArgument('required')) {
            $select[] = 'required ';
        }

        $out = '<select ' . implode(' ', $select) . '>';

        if ($this->hasArgument('blank')) {
            $out .= '<option value="">' . $this->arguments['blank'] . '</option>';
        }

        $options = $this->getOptions();

        foreach ($options as $option) {
            $out .= $option;
        }

        $out .= '</select>';

        return $out;
    }

    /**
     * @return array
     */
    protected function getOptions()
    {
        $options = [];

        $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            \TYPO3\CMS\Extbase\Object\ObjectManager::class
        );
        $currencyViewHelper = $objectManager->get(
            \Extcode\Cart\ViewHelpers\Format\CurrencyViewHelper::class
        );
        $currencyViewHelper->initialize();
        $currencyViewHelper->setRenderingContext($this->renderingContext);

        foreach ($this->eventDate->getPriceCategories() as $priceCategory) {
            /**
             * @var \Extcode\CartEvents\Domain\Model\PriceCategory $priceCategory
             */
            $currencyViewHelper->setRenderChildrenClosure(
                function () use ($priceCategory) {
                    return $priceCategory->getPrice();
                }
            );
            $regularPrice = $currencyViewHelper->render();

            $value = 'value="' . $priceCategory->getUid() . '"';
            $data = 'data-regular-price="' . $regularPrice . '"';

            $specialPrice = $priceCategory->getBestSpecialPrice();
            if ($specialPrice) {
                $currencyViewHelper->setRenderChildrenClosure(
                    function () use ($priceCategory) {
                        return $priceCategory->getBestPrice();
                    }
                );
                $specialPricePrice = $currencyViewHelper->render();

                $specialPricePercentageDiscount = number_format($priceCategory->getBestSpecialPricePercentageDiscount(), 2);

                $data .= ' data-title="' . $specialPrice->getTitle() . '"';
                $data .= ' data-special-price="' . $specialPricePrice . '"';
                $data .= ' data-discount="' . $specialPricePercentageDiscount . '"';
            }

            $disabled = '';
            if (!$priceCategory->isAvailable() && $priceCategory->getEventDate() && $priceCategory->getEventDate()->isHandleSeatsInPriceCategory()) {
                $disabled = 'disabled';
            }

            $option = '<option ' . $value . ' ' . $data . ' ' . $disabled . '>' . $priceCategory->getTitle() . '</option>';
            $options[$priceCategory->getSku()] = $option;
        }

        return $options;
    }
}