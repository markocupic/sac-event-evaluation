<?php

declare(strict_types=1);

namespace Markocupic\SacEventFeedback\Controller\FrontendModule;

use Contao\CalendarEventsModel;
use Contao\CalendarModel;
use Contao\Controller;
use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\FormFieldModel;
use Contao\FormModel;
use Contao\FrontendTemplate;
use Contao\FrontendUser;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\Template;
use Markocupic\SacEventFeedback\EventFeedbackHelper;
use Markocupic\SacEventFeedback\Model\EventFeedbackModel;
use Markocupic\SacEventFeedback\Session\Attribute\ArrayAttributeBag;
use Markocupic\SacEventToolBundle\Model\CalendarEventsMemberModel;
use ReallySimpleJWT\Token;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;
use Terminal42\MultipageFormsBundle\FormManagerFactory;

#[AsFrontendModule(EventFeedbackFormController::TYPE, category:'event_feedback', template:'mod_event_feedback_form')]
class EventFeedbackFormController extends AbstractFrontendModuleController
{
    public const TYPE = 'event_feedback_form';
    public const MODE_WARNING = 'has_warning';
    public const MODE_SHOW_FORM = 'show_form';
    public const MODE_CHECKOUT = 'checkout';
    public const MODE_SHOW_FORM_ALREADY_FILLED_OUT = 'form_already_filled_out';

    private FrontendUser|null $user = null;
    private string $mode;

    public function __construct(
        private readonly Security $security,
        private readonly ScopeMatcher $scopeMatcher,
        private readonly TranslatorInterface $translator,
        private readonly EventFeedbackHelper $eventFeedbackHelper,
        private readonly FormManagerFactory $formManagerFactory,
        private readonly string $secret,
    ) {
    }

    public function __invoke(Request $request, ModuleModel $model, string $section, array|null $classes = null, PageModel|null $page = null): Response
    {
        if ($this->scopeMatcher->isFrontendRequest($request)) {
            // Get logged in user
            $this->user = $this->security->getUser();

            if (!$this->user instanceof FrontendUser) {
                return new Response('', Response::HTTP_NO_CONTENT);
            }
        }

        return parent::__invoke($request, $model, $section, $classes);
    }

    protected function getResponse(Template $template, ModuleModel $model, Request $request): Response
    {
        $this->template = $template;
        $this->mode = self::MODE_SHOW_FORM;
        $this->template->mode = $this->mode;

        $token = $request->query->get('token', '');

        if (!$token || !Token::validate($token, $this->secret)) {
            if (!Token::validateExpiration($token, $this->secret)) {
                return $this->returnWithWarning($this->translator->trans('ERR.sacEvFb.tokenExpiredOrInvalid', [], 'contao_default'));
            }

            return $this->returnWithWarning($this->translator->trans('ERR.sacEvFb.tokenExpiredOrInvalid', [], 'contao_default'));
        }

        // Get the user id (tl_calendar_events_member.id) from JWT
        $arrPayload = Token::getPayload($token, $this->secret);
        $registration = CalendarEventsMemberModel::findByPk($arrPayload['user_id']);

        if (null === $registration) {
            return $this->returnWithWarning($this->translator->trans('ERR.sacEvFb.eventRegistrationNotFound', [], 'contao_default'));
        }

        $session = $request->getSession();
        $bag = $session->getBag(ArrayAttributeBag::SESSION_BAG_NAME);

        if ($session->isStarted()) {
            if ($bag->has('sac_event_feedback_last_insert')) {
                if ($registration->uuid === $bag->get('sac_event_feedback_last_insert')) {
                    $this->mode = self::MODE_CHECKOUT;
                    $salutation = $this->translator->trans('MSC.sacEvFb.salutation'.ucfirst($registration->gender), [], 'contao_default');
                    $this->template->checkoutMsg = $this->translator->trans('MSC.sacEvFb.checkoutMsg', [$salutation, $registration->firstname], 'contao_default');
                }
            }
        }

        /* Check if member exists */
        if (trim((string) $registration->sacMemberId) !== trim((string) $this->user->sacMemberId)) {
            return $this->returnWithWarning($this->translator->trans('ERR.sacEvFb.invalidUuidForLoggedInUser', [], 'contao_default'));
        }

        /* Check if the event exists */
        if (null === ($event = CalendarEventsModel::findByPk($registration->eventId))) {
            return $this->returnWithWarning($this->translator->trans('ERR.sacEvFb.eventMatchingUuidNotFound', [], 'contao_default'));
        }

        /* Check if the calendar exists */
        if (null === CalendarModel::findByPk($event->pid)) {
            return $this->returnWithWarning($this->translator->trans('ERR.sacEvFb.calendarMatchingUuidNotFound', [], 'contao_default'));
        }

        /* Check if the form exists */
        if (null === ($form = $this->eventFeedbackHelper->getForm($event))) {
            return $this->returnWithWarning($this->translator->trans('ERR.sacEvFb.formMatchingUuidNotFound', [], 'contao_default'));
        }

        /* Form has already been filled out */
        if (self::MODE_CHECKOUT !== $this->mode && null !== EventFeedbackModel::findOneByUuid($registration->uuid)) {
            $this->mode = self::MODE_SHOW_FORM_ALREADY_FILLED_OUT;
            $salutation = $this->translator->trans('MSC.sacEvFb.salutation'.ucfirst($registration->gender), [], 'contao_default');
            $this->template->formAlreadyFilledOutMsg = $this->translator->trans('MSC.sacEvFb.formAlreadyFilledOutMsg', [$salutation, $registration->firstname, $event->title], 'contao_default');
        }

        /* Show the form */
        if (self::MODE_SHOW_FORM === $this->mode) {
            $this->template->formManager = $this->formManagerFactory->forFormId($form->id);
            $this->template->form = Controller::getForm($form->id);
            $this->template->formModel = $form;
            $this->template->formLabels = json_encode($this->getFormLabels($form));
        }

        $this->template->mode = $this->mode;

        $partial = new FrontendTemplate('partial_event_feedback_details');
        $partial->member = $registration->row();
        $partial->event = $event->row();
        $this->template->details = $partial->parse();

        return $this->template->getResponse();
    }

    private function returnWithWarning(string $errMsg): Response
    {
        $this->mode = self::MODE_WARNING;
        $this->template->mode = $this->mode;
        $this->template->warningMsg = $errMsg;

        return $this->template->getResponse();
    }

    private function getFormlabels(FormModel $form): array
    {
        $ff = FormFieldModel::findByPid($form->id);
        $arrOpt = [];

        while ($ff->next()) {
            if (!\in_array($ff->type, ['select', 'radio', 'checkbox'], true)) {
                continue;
            }
            $ffOpt = StringUtil::deserialize($ff->options, true);

            if (!empty($ffOpt)) {
                foreach ($ffOpt as $opt) {
                    if ('' === $opt['value']) {
                        continue;
                    }
                    $arrOpt[$ff->name][$opt['value']] = $opt['label'];
                }
            }
        }

        return $arrOpt;
    }
}
