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

use Doctrine\DBAL\Connection;
use Markocupic\SacEventFeedback\Model\EventFeedbackReminderModel;
use Markocupic\SacEventToolBundle\Model\CalendarEventsMemberModel;

class FeedbackReminder
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function deleteFeedbackReminderByEventMember(CalendarEventsMemberModel $eventMember): void
    {
        $this->connection->delete('tl_event_feedback_reminder', ['uuid' => $eventMember->uuid]);
    }

    public function deleteReminder(EventFeedbackReminderModel $objReminder): void
    {
        $objReminder->delete();
    }
}
