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

namespace Markocupic\SacEventFeedback\DataContainer;

use Contao\CalendarEventsModel;
use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\FormModel;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Markocupic\SacEventToolBundle\Config\EventType;

class CalendarEvents
{
    public function __construct(
        private readonly Connection $connection,
        private readonly array $feedbackConfig,
    ) {
    }

    #[AsCallback(table: 'tl_calendar_events', target: 'config.onload')]
    public function getOnlineFeedbackConfigurations(DataContainer $dc): void
    {
        $blnRemoveField = true;

        if ($dc->id) {
            if (null !== ($eventsModel = CalendarEventsModel::findByPk($dc->id))) {
                if (null !== ($calendarModel = $eventsModel->getRelated('pid'))) {
                    if ($calendarModel->enableOnlineEventFeedback) {
                        $formModel = FormModel::findByPk($calendarModel->onlineFeedbackForm);

                        $notificationId = $this->connection->fetchOne(
                            'SELECT id FROM tl_nc_notification WHERE id = :id',
                            [
                                'id' => (int) $calendarModel->onlineFeedbackNotification,
                            ],
                            [
                                'id' => Types::INTEGER,
                            ]
                        );

                        $hasConfig = $calendarModel->onlineFeedbackConfiguration && ($this->feedbackConfig[$calendarModel->onlineFeedbackConfiguration] ?? null);

                        if (null !== $formModel && false !== $notificationId && $hasConfig) {
                            $blnRemoveField = false;
                        }
                    }
                }
            }

            if ($blnRemoveField) {
                PaletteManipulator::create()
                    ->removeField('enableOnlineEventFeedback')
                    ->applyToPalette('default', 'tl_calendar_events')
                    ->applyToPalette(EventType::TOUR, 'tl_calendar_events')
                    ->applyToPalette(EventType::LAST_MINUTE_TOUR, 'tl_calendar_events')
                    ->applyToPalette(EventType::COURSE, 'tl_calendar_events')
                    ->applyToPalette(EventType::GENERAL_EVENT, 'tl_calendar_events')
                ;
            }
        }
    }
}
