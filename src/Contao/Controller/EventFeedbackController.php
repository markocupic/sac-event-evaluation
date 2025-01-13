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

namespace Markocupic\SacEventFeedback\Contao\Controller;

use Contao\Backend;
use Contao\BackendUser;
use Contao\CalendarEventsModel;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Exception\InvalidResourceException;
use Contao\CoreBundle\Exception\ResponseException;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\DataContainer;
use Contao\StringUtil;
use Markocupic\CloudconvertBundle\Conversion\ConvertFile;
use Markocupic\PhpOffice\PhpWord\MsWordTemplateProcessor;
use Markocupic\SacEventFeedback\Feedback\Feedback;
use Markocupic\SacEventToolBundle\Model\CalendarEventsMemberModel;
use Markocupic\SacEventToolBundle\Security\Voter\CalendarEventsVoter;
use Markocupic\SacEventToolBundle\Util\CalendarEventsUtil;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\String\UnicodeString;
use Twig\Environment as TwigEnvironment;

class EventFeedbackController
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly Security $security,
        private readonly RequestStack $requestStack,
        private readonly TwigEnvironment $twig,
        private readonly ConvertFile $convertFile,
        private readonly string $docxTemplate,
        private readonly string $projectDir,
    ) {
    }

    public function getEventFeedbackAction(DataContainer $dc): Response
    {
        $request = $this->requestStack->getCurrentRequest();

        $id = $request->query->get('id');
        $event = CalendarEventsModel::findByPk($id);

        if (null === $event) {
            throw new InvalidResourceException(sprintf('Event with id %s not found.', $id));
        }

        if (!$this->isAllowed($event)) {
            throw new AccessDeniedException('User is not allowed to access the backend module "calendar".');
        }

        $objFeedback = new Feedback($event);

        $backend = $this->framework->getAdapter(Backend::class);
        $pdfHref = $backend->addToUrl('key=showEventFeedbacksAsPdf');

        $arrEvent = $objFeedback->getEvent(false)->row();
        $arrEvent = array_map(static fn ($val) => StringUtil::revertInputEncoding((string) $val), $arrEvent);

        return new Response($this->twig->render(
            '@MarkocupicSacEventFeedback/sac_event_feedback.html.twig',
            [
                'event' => $arrEvent,
                'has_feedbacks' => $objFeedback->countFeedbacks(false) > 0,
                'feedbacks' => $objFeedback->getDataAll(false),
                'feedback_count' => $objFeedback->countFeedbacks(false),
                'pdf_link' => $pdfHref,
            ]
        ));
    }

    public function getEventFeedbackAsPdfAction(DataContainer $dc): void
    {
        $request = $this->requestStack->getCurrentRequest();

        $id = $request->query->get('id');
        $event = CalendarEventsModel::findByPk($id);

        if (null === $event) {
            throw new InvalidResourceException(sprintf('Event with id %s not found.', $id));
        }

        if (!$this->isAllowed($event)) {
            throw new AccessDeniedException('User is not allowed to access the backend module "calendar".');
        }

        $objFeedback = new Feedback($event);

        $docxTemplateSrc = Path::makeAbsolute($this->docxTemplate, $this->projectDir);
        $targetSrc = sprintf('system/tmp/event_feedback_%s_%s.docx', $event->id, time());
        $targetSrc = Path::makeAbsolute($targetSrc, $this->projectDir);

        // Create phpword instance
        $objPhpWord = new MsWordTemplateProcessor($docxTemplateSrc, $targetSrc);
        $objPhpWord->replace('event_title', htmlspecialchars(html_entity_decode($event->title)));
        $objPhpWord->replace('event_type', $event->eventType);
        $objPhpWord->replace('event_id', $event->id);
        $objPhpWord->replace('event_instructor', CalendarEventsUtil::getMainInstructorName($event));

        $arrEventDates = array_map(static fn ($tstamp) => date('d.m.Y', (int) $tstamp), CalendarEventsUtil::getEventTimestamps($event));
        $objPhpWord->replace('event_date', implode("\r\n", $arrEventDates), ['multiline' => true]);

        $objPhpWord->replace('date', date('d.m.Y'));
        $objPhpWord->replace('count_fb', $objFeedback->countFeedbacks());

        $countReg = CalendarEventsMemberModel::countBy(['hasParticipated = ?', 'eventId = ?'], ['1', $event->id]);
        $objPhpWord->replace('count_reg', (string) $countReg);

        // Dropdowns
        foreach ($objFeedback->getDropdowns() as $arrDropdown) {
            $objPhpWord->createClone('dropdown_label');
            $label = htmlspecialchars(html_entity_decode((string) $arrDropdown['label']));
            $objPhpWord->addToClone('dropdown_label', 'dropdown_label', $label, ['multiline' => true]);

            $dropdownText = '';

            foreach ($arrDropdown['values'] as $value) {
                $dropdownText .= sprintf('%sx %s'."\r\n", $value['count'], $value['label']);
            }

            $dropdownText = htmlspecialchars(html_entity_decode((string) $dropdownText));
            $objPhpWord->addToClone('dropdown_label', 'dropdown_feedback', $dropdownText, ['multiline' => true]);
        }

        // Textareas
        foreach ($objFeedback->getTextareas() as $arrFeedback) {
            $objPhpWord->createClone('text_label');
            $label = htmlspecialchars(html_entity_decode((string) $arrFeedback['label']));

            $objPhpWord->addToClone('text_label', 'text_label', $label, ['multiline' => true]);
            $text = implode("\r\n\r\n", $arrFeedback['values']);
            $text = htmlspecialchars(html_entity_decode((string) $text));

            $objPhpWord->addToClone('text_label', 'text_feedback', $text, ['multiline' => true]);
        }

        $objSplFileDocx = $objPhpWord->generate();

        $objSplFilePdf = $this->convertFile
            ->file($objSplFileDocx->getRealPath())
            ->uncached(true)
            ->convertTo('pdf')
        ;

        $response = new BinaryFileResponse($objSplFilePdf);

        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            '',
            (new UnicodeString(basename($objSplFilePdf->getRealPath())))->ascii()->toString(),
        );

        throw new ResponseException($response);
    }

    private function isAllowed(CalendarEventsModel $event): bool
    {
        $user = $this->security->getUser();

        if ($user instanceof BackendUser) {
            if ($this->security->isGranted('ROLE_ADMIN')) {
                return true;
            }

            // Apply same permissions as "teilnehmerliste"
            $canReadFeedbacks = true;

            if (!$this->security->isGranted(CalendarEventsVoter::CAN_WRITE_EVENT, $event->id) && (int) $event->registrationGoesTo !== (int) $user->id) {
                $canReadFeedbacks = false;
            }

            $canAccessModule = $this->security->isGranted(
                ContaoCorePermissions::USER_CAN_ACCESS_MODULE,
                'calendar'
            );

            if ($canAccessModule && $canReadFeedbacks) {
                return true;
            }
        }

        return false;
    }
}
