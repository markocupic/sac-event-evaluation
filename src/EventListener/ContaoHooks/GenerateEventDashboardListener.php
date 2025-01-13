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

use Contao\BackendUser;
use Contao\CalendarEventsModel;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\System;
use Knp\Menu\MenuItem;
use Markocupic\SacEventFeedback\Model\EventFeedbackModel;
use Markocupic\SacEventToolBundle\Security\Voter\CalendarEventsVoter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\RouterInterface;

#[AsHook(GenerateEventDashboardListener::HOOK, priority: 100)]
class GenerateEventDashboardListener
{
    public const HOOK = 'generateEventDashboard';

    public function __construct(
        private readonly Security $security,
        private readonly RouterInterface $router,
    ) {
    }

    public function __invoke(MenuItem $menu, CalendarEventsModel $objEvent): void
    {
        if (null === EventFeedbackModel::findByPid($objEvent->id)) {
            return;
        }

        $user = $this->security->getUser();

        if (!$user instanceof BackendUser) {
            return;
        }

        // Apply same permission policy as "teilnehmerliste"
        if (!$this->security->isGranted(CalendarEventsVoter::CAN_WRITE_EVENT, $objEvent->id) && (int) $objEvent->registrationGoesTo !== (int) $user->id) {
            return;
        }

        $container = System::getContainer();
        $requestToken = $container
            ->get('contao.csrf.token_manager')
            ->getToken($container->getParameter('contao.csrf_token_name'))
            ->getValue()
        ;

        // Get the refererId
        $refererId = System::getContainer()
            ->get('request_stack')
            ->getCurrentRequest()
            ->get('_contao_referer_id')
        ;

        // "Download event feedbacks" button
        $href = $this->router->generate('contao_backend', [
            'do' => 'calendar',
            'key' => 'showEventFeedbacks',
            'id' => $objEvent->id,
            'rt' => $requestToken,
            'ref' => $refererId,
        ]);

        $menu->addChild('Event Auswertungen', ['uri' => $href])
            ->setLinkAttribute('role', 'button')
            ->setLinkAttribute('class', 'tl_submit')
            ->setLinkAttribute('target', '_blank')
            ->setLinkAttribute('rel', 'noopener')
            ->setLinkAttribute('title', 'Event Auswertungen herunterladen')
        ;
    }
}
