<?php

declare(strict_types=1);

/*
 * This file is part of SAC Event Evaluation Bundle.
 *
 * (c) Marko Cupic 2021 <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/sac-event-evaluation
 */

namespace Markocupic\SacEventEvaluation\DataContainer;

use Contao\CalendarEventsModel;
use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\DataContainer;
use Contao\FormModel;
use NotificationCenter\Model\Notification;

class TlCalendarEvents
{
    /**
     * @var ContaoFramework
     */
    private $framework;

    /**
     * @var array
     */
    private $onlineEvaluationConfigs;

    public function __construct(ContaoFramework $framework, array $onlineEvaluationConfigs)
    {
        $this->framework = $framework;
        $this->onlineEvaluationConfigs = $onlineEvaluationConfigs;
    }

    /**
     * @Callback(table="tl_calendar_events", target="config.onload")
     */
    public function getOnlineEvaluationConfigurations(DataContainer $dc): void
    {
        $blnRemoveField = true;

        if ($dc->id) {
            if (null !== ($eventsModel = CalendarEventsModel::findByPk($dc->id))) {
                if (null !== ($calendarModel = $eventsModel->getRelated('pid'))) {
                    if ($calendarModel->enableOnlineEventEvaluation) {
                        $formModel = FormModel::findByPk($calendarModel->onlineEvaluationForm);
                        $notificationModel = Notification::findByPk($calendarModel->onlineEvaluationNotification);
                        $hasConfig = $calendarModel->onlineEvaluationConfiguration && ($this->onlineEvaluationConfigs[$calendarModel->onlineEvaluationConfiguration] ?? null);

                        if (null !== $formModel && null !== $notificationModel && $hasConfig) {
                            $blnRemoveField = false;
                        }
                    }
                }
            }

            if ($blnRemoveField) {
                PaletteManipulator::create()
                    ->removeField('enableOnlineEventEvaluation')
                    ->applyToPalette('default', 'tl_calendar_events')
                    ->applyToPalette('tour', 'tl_calendar_events')
                    ->applyToPalette('lastMinuteTour', 'tl_calendar_events')
                    ->applyToPalette('course', 'tl_calendar_events')
                    ->applyToPalette('generalEvent', 'tl_calendar_events')
                ;
            }
        }
    }
}