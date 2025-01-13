<?php

declare(strict_types=1);

/*
 * This file is part of SAC Event Feedback.
 *
 * (c) Marko Cupic 2025 <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/sac-event-feedback
 */

namespace Markocupic\SacEventFeedback\Feedback;

use Contao\CalendarEventsModel;
use Contao\FormFieldModel;
use Contao\FormModel;
use Contao\StringUtil;
use Markocupic\SacEventFeedback\Model\EventFeedbackModel;

class Feedback
{
    private array $arrData = [];
    private bool $hasCache = false;

    public function __construct(
        private readonly CalendarEventsModel $event,
    ) {
        $this->arrData = [
            'event' => $event,
            'count' => 0,
            'dropdownFields' => [],
            'textareaFields' => [],
        ];
    }

    public function getDataAll(bool $blnUncached = true): array
    {
        return $this->get($blnUncached);
    }

    public function getEvent(bool $blnUncached = true): CalendarEventsModel
    {
        $arrData = $this->get($blnUncached);

        return $arrData['event'];
    }

    public function getTextareas(bool $blnUncached = true): array
    {
        $arrData = $this->get($blnUncached);

        return $arrData['textareaFields'];
    }

    public function getDropdowns(bool $blnUncached = true): array
    {
        $arrData = $this->get($blnUncached);

        return $arrData['dropdownFields'];
    }

    public function countFeedbacks(bool $blnUncached = true): int
    {
        $arrData = $this->get($blnUncached);

        return $arrData['count'];
    }

    /**
     * @throws \Exception
     */
    private function get(bool $blnUncached): array
    {
        if ($this->hasCache && !$blnUncached) {
            return $this->arrData;
        }

        $arrFormFields = [];
        $intFbCount = 0;
        $feedback = EventFeedbackModel::findByPid($this->event->id);

        while ($feedback->next()) {
            if (null === ($form = FormModel::findByPk($feedback->form))) {
                continue;
            }

            if (null === ($formFields = FormFieldModel::findByPid($form->id))) {
                continue;
            }

            ++$intFbCount;

            while ($formFields->next()) {
                if ($formFields->invisible) {
                    continue;
                }

                if (\in_array($formFields->type, ['select', 'checkbox', 'radio'], true)) {
                    $strBelongsTo = 'dropdownFields';
                } elseif ('textarea' === $formFields->type) {
                    $strBelongsTo = 'textareaFields';
                }

                if ($strBelongsTo && !isset($this->arrData[$strBelongsTo][$formFields->name])) {
                    $arrFormFields[] = $formFields->name;
                    $this->addFormFieldToDataArray($formFields->current(), $strBelongsTo);
                }
            }

            // Add values to the data array
            $arrData = $feedback->row();

            foreach ($arrData as $key => $value) {
                if (!\in_array($key, $arrFormFields, true)) {
                    continue;
                }

                if ('' !== trim((string) $value)) {
                    if (is_numeric($value) && isset($this->arrData['dropdownFields'][$key]['values'][$value])) {
                        ++$this->arrData['dropdownFields'][$key]['values'][$value]['count'];
                    } elseif (isset($this->arrData['textareaFields'][$key])) {
                        $this->arrData['textareaFields'][$key]['values'][] = htmlspecialchars_decode((string) $value);
                    }
                }
            }
        }

        $this->arrData['count'] = $intFbCount;

        $this->hasCache = true;

        return $this->arrData;
    }

    private function addFormFieldToDataArray(FormFieldModel $formField, $strBelongsTo = 'dropdownFields'): void
    {
        if ($formField->invisible || '' === $formField->name || isset($this->arrData[$strBelongsTo][$formField->name])) {
            return;
        }

        if ('dropdownFields' === $strBelongsTo) {
            $arrSub = [];
            $arrSub['values'] = [];
            $arrSub['label'] = $formField->label;

            $arrOptions = StringUtil::deserialize($formField->options, true);

            foreach ($arrOptions as $option) {
                if ('' !== $option['value']) {
                    $arrSub['values'][$option['value']]['count'] = 0;
                    $arrSub['values'][$option['value']]['label'] = $option['label'];
                }
            }
            $this->arrData[$strBelongsTo][$formField->name] = $arrSub;
        }

        if ('textareaFields' === $strBelongsTo) {
            $arrSub['values'] = [];
            $arrSub['label'] = $formField->label;
            $this->arrData[$strBelongsTo][$formField->name] = $arrSub;
        }
    }
}
