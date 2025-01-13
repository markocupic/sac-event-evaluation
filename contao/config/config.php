<?php

declare(strict_types=1);

/*
 * This file is part of SAC Event Feedback.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/sac-event-feedback
 */

use Markocupic\SacEventFeedback\Contao\Controller\EventFeedbackController;
use Markocupic\SacEventFeedback\Model\EventFeedbackModel;
use Markocupic\SacEventFeedback\Model\EventFeedbackReminderModel;

/*
 * Backend modules
 */
$GLOBALS['BE_MOD']['event_feedback'] = [
    'event_feedback'          => [
        'tables' => ['tl_event_feedback'],
    ],
    'event_feedback_reminder' => [
        'tables' => ['tl_event_feedback_reminder'],
    ],
];

$GLOBALS['BE_MOD']['sac_be_modules']['calendar']['showEventFeedbacks'] = [EventFeedbackController::class, 'getEventFeedbackAction'];
$GLOBALS['BE_MOD']['sac_be_modules']['calendar']['showEventFeedbacksAsPdf'] = [EventFeedbackController::class, 'getEventFeedbackAsPdfAction'];

/*
 * Models
 */
$GLOBALS['TL_MODELS']['tl_event_feedback'] = EventFeedbackModel::class;
$GLOBALS['TL_MODELS']['tl_event_feedback_reminder'] = EventFeedbackReminderModel::class;
