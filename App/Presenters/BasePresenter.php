<?php declare(strict_types=1);
namespace InstruktoriBrno\TMOU\Presenters;

use InstruktoriBrno\TMOU\Enums\UserRole;
use InstruktoriBrno\TMOU\Facades\Teams\MaintainSSOSession;
use InstruktoriBrno\TMOU\Model\Page;
use InstruktoriBrno\TMOU\Services\Events\FindEventsService;
use Nette\Security\Identity;
use function count;
use InstruktoriBrno\TMOU\Enums\Action;
use InstruktoriBrno\TMOU\Enums\Flash;
use InstruktoriBrno\TMOU\Enums\PrivilegeEnforceMethod;
use InstruktoriBrno\TMOU\Enums\Resource;
use InstruktoriBrno\TMOU\Forms\GameClockFormFactory;
use InstruktoriBrno\TMOU\Services\System\GameClockService;
use InstruktoriBrno\TMOU\Utils\Helpers;
use Nette\Application\UI\ComponentReflection;
use Nette\Application\UI\Form;
use Nette\Application\UI\MethodReflection;
use Nette\Application\UI\Presenter;
use Nette\Forms\Controls\SubmitButton;
use Nette\Utils\ArrayHash;

abstract class BasePresenter extends Presenter
{

    /** @var GameClockFormFactory @inject */
    public $gameClockFormFactory;

    /** @var GameClockService @inject */
    public $gameClockService;

    /** @var FindEventsService @inject */
    public $findEventsService;

    /** @var MaintainSSOSession @inject */
    public $maintainSSOSession;

    protected function beforeRender()
    {
        parent::beforeRender();
        if ($this->user->isAllowed(Resource::ADMIN_COMMON, Action::VIEW)) {
            $this->template->hasDatagrid = true;
            $this->template->hasDatetimepicker = true;
        }

        $this->template->currentTime = $this->gameClockService->get();
        if ($this->user->isAllowed(Resource::ADMIN_COMMON, Action::CHANGE_GAME_CLOCK) || $this->isImpersonated()) {
            $this->template->gameClockChange = true;
            $this->template->hasDatetimepicker = true;
        }

        if ($this->user->isLoggedIn() && $this->user->isInRole(UserRole::TEAM()->toScalar())) {
            ($this->maintainSSOSession)($this->user->getId());
        }

        $this->template->events = ($this->findEventsService)();
    }

    public function isImpersonated(): bool
    {
        $identity = $this->user->getIdentity();
        return $identity instanceof Identity
            && $this->user->isLoggedIn()
            && $this->user->isInRole(UserRole::TEAM)
            && isset($identity->getData()['impersonated'])
            && $identity->getData()['impersonated'] === true;
    }

    public static function isPageCurrentlySelected(?Page $page, ?string $slug, ?int $eventNumber): bool
    {
        return $page !== null
            && ($page->getSlug() === $slug || ($page->isDefault() && $slug === null))
            && (($eventNumber === null && $page->getEvent() === null) || ($page->getEvent() !== null && $page->getEvent()->getNumber() === $eventNumber));
    }

    /** @param mixed $element */
    public function checkRequirements($element): void
    {
        // Ugly hack for Error4xxPresenter to have current event
        Error4xxPresenter::$eventNumber = $this->getParameter('eventNumber');


        if ($element instanceof MethodReflection) {
            $privilege = ComponentReflection::parseAnnotation($element, 'privilege');
            if ($privilege !== false && count($privilege) >= 2) {
                $this->requirePrivilege(
                    Helpers::stringToConstant($privilege[0]),
                    Helpers::stringToConstant($privilege[1]),
                    isset($privilege[2])
                        ? PrivilegeEnforceMethod::from(Helpers::stringToConstant($privilege[2]))
                        : PrivilegeEnforceMethod::ACCESS_DENIED
                );
            } else {
                throw new \Nette\Application\ForbiddenRequestException("Annotation @privilege of method {$element->getName()} has invalid count of parameters.");
            }
        } else {
            parent::checkRequirements($element);
        }
    }

    /**
     * @param string $resource
     * @param null $action
     * @param int $method
     *
     * @throws \Nette\Application\AbortException
     */
    public function requirePrivilege($resource, $action = null, $method = PrivilegeEnforceMethod::ACCESS_DENIED): void
    {
        if (!$this->user->isAllowed($resource, $action)) {
            if (!$this->user->isLoggedIn()) {
                $this->flashMessage('Nejprve se přihlaste.', Flash::INFO);
                $this->redirect('Admin:login', ['backlink' => $this->storeRequest()]);
            } else {
                $this->flashMessage('Nejste oprávněni k použití této funkcionality. Pokud věříte, že jde o chybu kontaktujte správce', Flash::DANGER);
                if ($method === PrivilegeEnforceMethod::NOT_AVAILABLE) {
                    $this->redirect('Homepage:', $this->user->getId());
                } else {
                    $this->forward('Error4xx:', [new \Nette\Application\ForbiddenRequestException()]);
                }
            }
        }
    }

    public function createComponentGameClock(): Form
    {
        return $this->gameClockFormFactory->create(function (Form $form, ArrayHash $values) {
            if (!$this->user->isAllowed(Resource::ADMIN_COMMON, Action::CHANGE_GAME_CLOCK) && !$this->isImpersonated()) {
                $form->addError('Nejste oprávněni provádět tuto operaci. Pokud věříte, že jde o chybu, kontaktujte správce.');
                return;
            }
            /** @var SubmitButton $resetButton */
            $resetButton = $form['reset'];
            if ($resetButton->isSubmittedBy()) {
                $this->gameClockService->reset();
            } else {
                if ($values['newNow'] !== null) {
                    $this->gameClockService->set($values['newNow']);
                }
            }
            $this->redirect('this');
        });
    }
}
