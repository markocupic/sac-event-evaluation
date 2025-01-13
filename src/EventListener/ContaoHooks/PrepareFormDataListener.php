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

namespace Markocupic\SacEventFeedback\EventListener\ContaoHooks;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Form;

#[AsHook(PrepareFormDataListener::HOOK, priority: 100)]
class PrepareFormDataListener
{
    public const HOOK = 'prepareFormData';

    public function __invoke($arrSubmitted, $arrLabels, $arrFields, Form $form): void
    {
        if ($form->isSacEventFeedbackForm) {
            $form->storeValues = '1';
            $form->targetTable = 'tl_event_feedback';
        }
    }
}
