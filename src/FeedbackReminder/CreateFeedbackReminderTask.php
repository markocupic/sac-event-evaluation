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

namespace Markocupic\SacEventFeedback\FeedbackReminder;

use Contao\CalendarEventsModel;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Markocupic\SacEventFeedback\EventFeedbackHelper;
use Markocupic\SacEventToolBundle\Model\CalendarEventsMemberModel;

class CreateFeedbackReminderTask
{
    public function __construct(
        private readonly Connection $connection,
        private readonly EventFeedbackHelper $eventFeedbackHelper,
    ) {
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    public function create(CalendarEventsMemberModel $eventMember): void
    {
        if (null === ($event = CalendarEventsModel::findByPk($eventMember->eventId))) {
            return;
        }

        $arrConfig = $this->eventFeedbackHelper->getOnlineFeedbackConfiguration($event);

        if (empty($arrConfig['send_reminder_after_days'])) {
            throw new \Exception('Array "send_reminder_after_days" should not be empty!');
        }

        if (!isset($arrConfig['feedback_expiration_time'])) {
            throw new \Exception('Key "feedback_expiration_time" should not be empty!');
        }

        // Do not send reminders before the event end date is reached.
        $timeStart = max(time(), (int) $event->endDate);
        $objDateStartReminding = new \DateTimeImmutable(date('Y-m-d H:i:s', $timeStart));

        $objDateExpiration = $objDateStartReminding->modify('+'.$arrConfig['feedback_expiration_time'].' day');

        foreach ($arrConfig['send_reminder_after_days'] as $intDays) {
            $objDateSendReminder = $objDateStartReminding->modify('+'.$intDays.' day');

            $set = [
                'pid' => $eventMember->id,
                'uuid' => $eventMember->uuid,
                'executionDate' => $objDateSendReminder->getTimestamp(),
                'dateAdded' => time(),
                'tstamp' => time(),
                'expiration' => $objDateExpiration->getTimestamp(),
            ];

            // Prevent inserting duplicate records
            // See $GLOBALS['TL_DCA']['tl_event_feedback_reminder']['config']['sql']['keys']['uuid,executionDate'] = 'unique'
            $sql = 'INSERT INTO tl_event_feedback_reminder (%s) VALUES (%s) ON DUPLICATE KEY UPDATE dateAdded=VALUES(dateAdded), tstamp=VALUES(tstamp)';
            $stmt = sprintf(
                $sql,
                implode(',', array_keys($set)),
                implode(',', array_fill(0, \count($set), '?')),
            );

            $this->connection->executeStatement($stmt, array_values($set));
        }
    }
}
