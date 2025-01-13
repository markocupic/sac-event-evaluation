<?php

declare(strict_types=1);

/*
 * This file is part of SAC Event Feedback.
 *
 * (c) Marko Cupic 2024 <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/sac-event-feedback
 */

namespace Markocupic\SacEventFeedback\FeedbackReminder;

use Contao\CalendarEventsModel;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\PageModel;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Markocupic\SacEventFeedback\EventFeedbackHelper;
use Markocupic\SacEventFeedback\Model\EventFeedbackReminderModel;
use Markocupic\SacEventToolBundle\Model\CalendarEventsMemberModel;
use Markocupic\SacEventToolBundle\Util\CalendarEventsUtil;
use Psr\Log\LoggerInterface;
use ReallySimpleJWT\Token;
use Terminal42\NotificationCenterBundle\NotificationCenter;

readonly class SendFeedbackReminder
{
    public function __construct(
        private Connection $connection,
        private EventFeedbackHelper $eventFeedbackHelper,
        private FeedbackReminder $feedbackReminder,
        private NotificationCenter $notificationCenter,
        private array $feedbackConfig,
        private string $secret,
        private LoggerInterface|null $contaoGeneralLogger = null,
        private LoggerInterface|null $contaoErrorLogger = null,
    ) {
    }

    public function sendReminder(EventFeedbackReminderModel $objReminder): void
    {
        /** @var PageModel $objPage */
        global $objPage;

        if (null !== ($objRegistration = CalendarEventsMemberModel::findOneByUuid($objReminder->uuid))) {
            $event = CalendarEventsModel::findByPk($objRegistration->eventId);

            if (null !== $event) {
                if (true !== ($errorCode = $this->eventFeedbackHelper->eventHasValidFeedbackConfiguration($event))) {
                    $this->writeErrorToContaoLog($errorCode, $event, $objReminder);

                    return;
                }

                // The notification has already been checked for existence. See EventFeedbackHelper::eventHasValidFeedbackConfiguration()
                $notificationId = $this->eventFeedbackHelper->getNotificationId($event);

                $arrTokens = $this->getNotificationTokens($objRegistration, $event, $objReminder);

                $receiptCollection = $this->notificationCenter->sendNotification($notificationId, $arrTokens, $objPage->language);

                if ($receiptCollection->count()) {
                    ++$objRegistration->countOnlineEventFeedbackNotifications;
                    $objRegistration->save();

                    if ($this->contaoGeneralLogger) {
                        $message = sprintf(
                            'An event feedback reminder for event "%s" ID %d has been sent to frontend user "%s %s" (event registration ID %d).',
                            $event->title,
                            $event->id,
                            $objRegistration->firstname,
                            $objRegistration->lastname,
                            $objRegistration->id,
                        );

                        $this->contaoGeneralLogger->info(
                            $message,
                            ['contao' => new ContaoContext(__METHOD__, 'SEND_EVENT_FEEDBACK_REMINDER')],
                        );
                    }
                }
            }
        }

        // Delete reminder
        $this->feedbackReminder->deleteReminder($objReminder);
    }

    /**
     * @throws Exception
     */
    public function sendRemindersByExecutionDate(int $tstamp, int $limit = 20): void
    {
        try {
            $this->connection->beginTransaction();

            // Delete already dispatched or expired records.
            $this->connection->executeStatement(
                'DELETE FROM tl_event_feedback_reminder WHERE expiration < ? OR (dispatchTime > ? AND dispatchTime < ?)',
                [
                    $tstamp,
                    0,
                    $tstamp - 60,
                ],
            );

            // Queue competing queries/requests on table "tl_event_feedback_reminder" with "FOR UPDATE" until the transaction is completed.
            // This should prevent competing queries and double emailing
            $result = $this->connection->executeQuery(
                sprintf('SELECT id FROM tl_event_feedback_reminder WHERE expiration > ? AND dispatched = ? LIMIT 0,%d FOR UPDATE', $limit),
                [
                    $tstamp,
                    '',
                ]
            );

            $arrIds = $result->fetchFirstColumn();

            if (!empty($arrIds)) {
                foreach ($arrIds as $id) {
                    $reminderModel = EventFeedbackReminderModel::findByPk($id);

                    if (null !== $reminderModel) {
                        $configuration = $this->getConfiguration($reminderModel);

                        $delay = 0;

                        if (null !== $configuration) {
                            $delay = $configuration['send_reminder_execution_delay'] ?? 0;
                        }

                        if ($reminderModel->executionDate > $tstamp - $delay) {
                            continue;
                        }

                        $set = [
                            'dispatched' => true,
                            'dispatchTime' => time(),
                        ];

                        $this->connection->update('tl_event_feedback_reminder', $set, ['id' => $id]);

                        // Send notification
                        $this->sendReminder($reminderModel);
                    }
                }
            }

            $this->connection->commit();
        } catch (\Exception $e) {
            $this->connection->rollBack();
        }
    }

    private function getConfiguration(EventFeedbackReminderModel $eventFeedbackReminderModel): array|null
    {
        try {
            $calendar = CalendarEventsModel::findByPk($eventFeedbackReminderModel->getRelated('pid')->eventId)->getRelated('pid');

            if (null === $calendar) {
                return null;
            }

            return $this->feedbackConfig[$calendar->onlineFeedbackConfiguration];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @throws \Exception
     */
    private function getNotificationTokens(CalendarEventsMemberModel $member, CalendarEventsModel $event, EventFeedbackReminderModel $reminder): array
    {
        $page = $this->eventFeedbackHelper->getPage($event);
        $token = $this->generateJwt($member, $reminder);

        $objInstructor = CalendarEventsUtil::getMainInstructor($event);
        $arrTokens = [];
        $arrTokens['instructor_name'] = CalendarEventsUtil::getMainInstructorName($event);
        $arrTokens['instructor_email'] = $objInstructor ? $objInstructor->email : '';
        $arrTokens['admin_email'] = $GLOBALS['TL_ADMIN_EMAIL'];
        $arrTokens['participant_firstname'] = $member->firstname;
        $arrTokens['participant_lastname'] = $member->lastname;
        $arrTokens['participant_email'] = $member->email;
        $arrTokens['participant_uuid'] = $member->uuid;
        $arrTokens['event_name'] = StringUtil::revertInputEncoding($event->title);
        $arrTokens['feedback_url'] = sprintf('%s?token=%s', $page->getAbsoluteUrl(), $token);

        return $arrTokens;
    }

    private function writeErrorToContaoLog(string $errorCode, CalendarEventsModel $event, EventFeedbackReminderModel $objReminder): void
    {
        $errorMsg = sprintf(
            'Could not send event feedback reminder due to misconfiguration. Error code: "%s". Event ID: "%d". Reminder-UUID: "%s".',
            $errorCode,
            $event->id,
            $objReminder->uuid,
        );

        $this->contaoErrorLogger->error($errorMsg);
    }

    private function generateJwt(CalendarEventsMemberModel $member, EventFeedbackReminderModel $reminder): string
    {
        $userId = $member->id;
        $expiration = (int) $reminder->expiration;
        $issuer = 'localhost';

        return Token::create($userId, $this->secret, $expiration, $issuer);
    }
}
