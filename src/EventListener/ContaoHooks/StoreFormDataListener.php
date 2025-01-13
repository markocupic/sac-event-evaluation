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

namespace Markocupic\SacEventFeedback\EventListener\ContaoHooks;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Form;
use Contao\FrontendUser;
use Doctrine\DBAL\Connection;
use Markocupic\SacEventFeedback\EventFeedbackHelper;
use Markocupic\SacEventFeedback\Model\EventFeedbackModel;
use Markocupic\SacEventFeedback\Session\Attribute\ArrayAttributeBag;
use Markocupic\SacEventToolBundle\Model\CalendarEventsMemberModel;
use ReallySimpleJWT\Token;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

#[AsHook(StoreFormDataListener::HOOK, priority: 100)]
class StoreFormDataListener
{
    public const HOOK = 'storeFormData';

    public function __construct(
        private readonly Security $security,
        private readonly Connection $connection,
        private readonly EventFeedbackHelper $eventFeedbackHelper,
        private readonly RequestStack $requestStack,
        private readonly string $secret,
    ) {
    }

    /**
     * Add uuid,tstamp,dateAdded and pid to $arrData.
     *
     * @throws \Exception
     */
    public function __invoke(array $arrData, Form $form): array
    {
        // Get logged in user
        $user = $this->security->getUser();
        $request = $this->requestStack->getCurrentRequest();

        if (!$form->isSacEventFeedbackForm) {
            return $arrData;
        }

        if (!$request->query->has('token') || !$user instanceof FrontendUser) {
            throw new \Exception('The form is only accessible to logged in contao frontend users.');
        }

        $token = $request->query->get('token');

        if (!Token::validate($token, $this->secret)) {
            throw new \Exception('Invalid token.');
        }

        $arrPayload = Token::getPayload($token, $this->secret);

        if (null === ($objRegistration = CalendarEventsMemberModel::findByPk($arrPayload['user_id']))) {
            throw new \Exception('Could not find a registration that matches to the token.');
        }

        if (null !== EventFeedbackModel::findOneByUuid($objRegistration->uuid)) {
            throw new \Exception('The record with tl_event_feedback.uuid allready exists.');
        }

        $member = $this->eventFeedbackHelper->getFrontendUserFromUuid($objRegistration->uuid);

        if (null === $member || (int) $member->id !== (int) $user->id) {
            return $arrData;
        }

        $event = $this->eventFeedbackHelper->getEventFromUuid($objRegistration->uuid);

        // Augment $arrData
        $arrData['form'] = $form->id;
        $arrData['uuid'] = $objRegistration->uuid;
        $arrData['pid'] = $event->id;
        $arrData['dateAdded'] = time();
        $arrData['tstamp'] = time();

        // Delete no more used reminders
        $this->connection->delete('tl_event_feedback_reminder', ['uuid' => $objRegistration->uuid]);

        // Store new uuid in the session
        $session = $request->getSession();

        if ($session->isStarted()) {
            $bag = $session->getBag(ArrayAttributeBag::SESSION_BAG_NAME);
            $bag->set('sac_event_feedback_last_insert', $objRegistration->uuid);
        }

        return $arrData;
    }
}
